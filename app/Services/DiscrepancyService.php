<?php

namespace App\Services;

use App\Models\DiscrepancyMaterials;
use App\Models\Materials;
use App\Models\DailyInput;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

/**
 * Service responsible for discrepancy management business logic
 * Single Responsibility: Handle discrepancy calculations and Excel imports
 */
class DiscrepancyService
{
    /**
     * Get all discrepancy data with calculations
     */
    public function getDiscrepancyData(array $filters = [], int $perPage = 50): array
    {
        $query = DiscrepancyMaterials::with(['material.vendor'])
            ->leftJoin('daily_inputs as latest_input', function ($join) {
                $join->on('discrepancy_materials.material_id', '=', 'latest_input.material_id')
                    ->whereIn('latest_input.id', function ($subQuery) {
                        $subQuery->select(DB::raw('MAX(id)'))
                            ->from('daily_inputs as di2')
                            ->whereColumn('di2.material_id', 'latest_input.material_id')
                            ->groupBy('di2.material_id');
                    });
            })
            ->select([
                'discrepancy_materials.*',
                DB::raw('COALESCE(latest_input.daily_stock, 0) as qty_actual'),
                DB::raw('(COALESCE(latest_input.daily_stock, 0) - discrepancy_materials.soh + discrepancy_materials.outstanding_gr + discrepancy_materials.outstanding_gi + discrepancy_materials.error_moving) as final_discrepancy'),
                DB::raw('((COALESCE(latest_input.daily_stock, 0) - discrepancy_materials.soh + discrepancy_materials.outstanding_gr + discrepancy_materials.outstanding_gi + discrepancy_materials.error_moving) * discrepancy_materials.price) as final_amount')
            ]);

        // Apply location filter
        if (!empty($filters['location'])) {
            $query->whereHas('material', function ($q) use ($filters) {
                $q->where('location', $filters['location']);
            });
        }

        // Apply search filter
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->whereHas('material', function ($q) use ($searchTerm) {
                $q->where('material_number', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('description', 'LIKE', "%{$searchTerm}%");
            });
        }


        // Apply sorting
        $sortBy = $filters['sort_by'] ?? null;
        $sortOrder = $filters['sort_order'] ?? 'asc';

        if ($sortBy === 'final_qty') {
            $query->orderBy('final_discrepancy', $sortOrder);
        } elseif ($sortBy === 'final_amount') {
            $query->orderBy('final_amount', $sortOrder);
        }

        // Paginate
        $paginatedData = $query->paginate($perPage);

        $discrepancies = $paginatedData->map(function ($discrepancy) {
            $material = $discrepancy->material;
            $latestInput = DailyInput::getLatestForMaterial($discrepancy->material_id);

            $qtyActual = $latestInput ? $latestInput->daily_stock : 0;
            $qtyActualTimestamp = $latestInput ? $latestInput->updated_at->format('Y-m-d H:i') : null;
            $sohTimestamp = $discrepancy->last_synced_at ? $discrepancy->last_synced_at->format('Y-m-d H:i') : null;

            // Calculate time difference in hours
            $timeDiff = 0;
            if ($latestInput && $discrepancy->last_synced_at) {
                $timeDiff = round(
                    abs($discrepancy->last_synced_at->diffInHours($latestInput->updated_at)),
                    1
                );
            }

            $initialDiscrepancy = $qtyActual - $discrepancy->soh;
            $explained = $discrepancy->outstanding_gr + $discrepancy->outstanding_gi + $discrepancy->error_moving;
            $finalDiscrepancy = $initialDiscrepancy + $explained;

            return [
                'id' => $discrepancy->id,
                'materialNo' => $material->material_number,
                'name' => $material->description,
                'uom' => $material->unit_of_measure,
                'location' => $material->location,
                'price' => (float) $discrepancy->price,
                'soh' => $discrepancy->soh,
                'sohTimestamp' => $sohTimestamp,
                'qtyActual' => $qtyActual,
                'qtyActualTimestamp' => $qtyActualTimestamp,
                'timeDiff' => $timeDiff,
                'outGR' => $discrepancy->outstanding_gr,
                'outGI' => $discrepancy->outstanding_gi,
                'errorMvmt' => $discrepancy->error_moving,
                'initialDiscrepancy' => $initialDiscrepancy,
                'finalDiscrepancy' => $finalDiscrepancy,
                'finalDiscrepancyAmount' => $finalDiscrepancy * $discrepancy->price,
            ];
        });

        // Get all items for statistics (not just current page)
        $allQuery = DiscrepancyMaterials::with(['material.vendor']);
        if (!empty($filters['location'])) {
            $allQuery->whereHas('material', function ($q) use ($filters) {
                $q->where('location', $filters['location']);
            });
        }
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $allQuery->whereHas('material', function ($q) use ($searchTerm) {
                $q->where('material_number', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('description', 'LIKE', "%{$searchTerm}%");
            });
        }
        $allItems = $allQuery->get()->map(function ($discrepancy) {
            $material = $discrepancy->material;
            $latestInput = DailyInput::getLatestForMaterial($discrepancy->material_id);
            $qtyActual = $latestInput ? $latestInput->daily_stock : 0;
            $initialDiscrepancy = $qtyActual - $discrepancy->soh;
            $explained = $discrepancy->outstanding_gr + $discrepancy->outstanding_gi + $discrepancy->error_moving;
            $finalDiscrepancy = $initialDiscrepancy + $explained;

            return [
                'finalDiscrepancy' => $finalDiscrepancy,
                'finalDiscrepancyAmount' => $finalDiscrepancy * $discrepancy->price,
            ];
        });

        return [
            'items' => $discrepancies->values()->all(),
            'statistics' => $this->calculateStatistics($allItems),
            'pagination' => [
                'current_page' => $paginatedData->currentPage(),
                'per_page' => $paginatedData->perPage(),
                'total' => $paginatedData->total(),
                'last_page' => $paginatedData->lastPage(),
            ],
        ];
    }

    /**
     * Get unique locations from materials with discrepancy data
     */
    public function getLocations(): array
    {
        return Materials::whereHas('discrepancyMaterial')
            ->distinct()
            ->pluck('location')
            ->filter()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Calculate statistics from discrepancy data
     */
    private function calculateStatistics($discrepancies): array
    {
        $surplusCount = 0;
        $discrepancyCount = 0;
        $surplusAmount = 0;
        $discrepancyAmount = 0;

        foreach ($discrepancies as $item) {
            $finalDiscrepancy = $item['finalDiscrepancy'];
            $amount = $item['finalDiscrepancyAmount'];

            if ($finalDiscrepancy > 0) {
                $surplusCount++;
                $surplusAmount += $amount;
            } elseif ($finalDiscrepancy < 0) {
                $discrepancyCount++;
                $discrepancyAmount += $amount;
            }
        }

        return [
            'surplusCount' => $surplusCount,
            'discrepancyCount' => $discrepancyCount,
            'surplusAmount' => $surplusAmount,
            'discrepancyAmount' => abs($discrepancyAmount),
        ];
    }

    /**
     * Check if a cell value is truly empty (null, empty string, or whitespace only)
     */
    private function isEmptyCell($value): bool
    {
        // Consider empty: null, empty string, whitespace only
        return $value === null || $value === '' || trim((string) $value) === '';
    }

    /**
     * Import external system data from Excel
     * Expected columns: Material Number, SoH, Outstanding GR, Outstanding GI, Error Moving, Price
     */
    public function importFromExcel($filePath): array
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Skip header row
            $header = array_shift($rows);

            $imported = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 because we skipped header and arrays are 0-indexed

                try {
                    // Extract material number
                    $materialNumber = trim($row[0] ?? '');

                    if (empty($materialNumber)) {
                        continue; // Skip empty rows
                    }

                    // Extract raw values without default coercion
                    $sohRaw = $row[1] ?? null;
                    $outstandingGRRaw = $row[2] ?? null;
                    $outstandingGIRaw = $row[3] ?? null;
                    $errorMovingRaw = $row[4] ?? null;
                    $priceRaw = $row[5] ?? null;

                    // Build update array - only include non-empty values
                    $updateData = [];

                    if (!$this->isEmptyCell($sohRaw)) {
                        $updateData['soh'] = (int) $sohRaw;
                    }
                    if (!$this->isEmptyCell($outstandingGRRaw)) {
                        $updateData['outstanding_gr'] = (int) $outstandingGRRaw;
                    }
                    if (!$this->isEmptyCell($outstandingGIRaw)) {
                        $updateData['outstanding_gi'] = (int) $outstandingGIRaw;
                    }
                    if (!$this->isEmptyCell($errorMovingRaw)) {
                        $updateData['error_moving'] = (int) $errorMovingRaw;
                    }
                    if (!$this->isEmptyCell($priceRaw)) {
                        $updateData['price'] = (float) $priceRaw;
                    }

                    // Skip row if no data fields provided (only material number)
                    if (empty($updateData)) {
                        $skipped++;
                        $errors[] = "Row {$rowNumber}: Skipped - no data provided for material '{$materialNumber}'";
                        continue;
                    }

                    // Always update last_synced_at when any field is updated
                    $updateData['last_synced_at'] = now();

                    // Find material
                    $material = Materials::where('material_number', $materialNumber)->first();

                    if (!$material) {
                        $errors[] = "Row {$rowNumber}: Material '{$materialNumber}' not found";
                        continue;
                    }

                    // Handle create vs update differently
                    $discrepancy = DiscrepancyMaterials::where('material_id', $material->id)->first();

                    if ($discrepancy) {
                        // EXISTING RECORD: Partial update (only provided fields)
                        $discrepancy->update($updateData);
                        $updated++;
                    } else {
                        // NEW RECORD: Need all required fields with defaults
                        $createData = array_merge([
                            'material_id' => $material->id,
                            'price' => 0,
                            'soh' => 0,
                            'outstanding_gr' => 0,
                            'outstanding_gi' => 0,
                            'error_moving' => 0,
                            'last_synced_at' => now(),
                        ], $updateData);

                        DiscrepancyMaterials::create($createData);
                        $imported++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Row {$rowNumber}: {$e->getMessage()}";
                }
            }

            DB::commit();

            return [
                'success' => true,
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Failed to import Excel: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Sync discrepancy data with latest daily inputs
     * This ensures all materials with daily inputs have discrepancy records
     */
    public function syncWithDailyInputs(): array
    {
        try {
            DB::beginTransaction();

            // Get all materials with daily inputs
            $materialsWithInputs = DailyInput::select('material_id')
                ->distinct()
                ->pluck('material_id');

            $created = 0;

            foreach ($materialsWithInputs as $materialId) {
                $exists = DiscrepancyMaterials::where('material_id', $materialId)->exists();

                if (!$exists) {
                    DiscrepancyMaterials::create([
                        'material_id' => $materialId,
                        'price' => 0,
                        'soh' => 0,
                        'outstanding_gr' => 0,
                        'outstanding_gi' => 0,
                        'error_moving' => 0,
                    ]);

                    $created++;
                }
            }

            DB::commit();

            return [
                'success' => true,
                'created' => $created,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Update discrepancy fields for a specific material
     */
    public function updateDiscrepancy(int $materialId, array $data): array
    {
        try {
            $discrepancy = DiscrepancyMaterials::where('material_id', $materialId)->first();

            if (!$discrepancy) {
                return [
                    'success' => false,
                    'message' => 'Discrepancy record not found',
                ];
            }

            $discrepancy->update(array_filter([
                'price' => $data['price'] ?? null,
                'soh' => $data['soh'] ?? null,
                'outstanding_gr' => $data['outstanding_gr'] ?? null,
                'outstanding_gi' => $data['outstanding_gi'] ?? null,
                'error_moving' => $data['error_moving'] ?? null,
            ], fn($value) => $value !== null));

            return [
                'success' => true,
                'data' => $discrepancy->fresh(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage(),
            ];
        }
    }
}
