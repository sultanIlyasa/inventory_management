<?php

namespace App\Services;

use App\Models\AnnualInventory;
use App\Models\AnnualInventoryItems;
use App\Models\Materials;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use AnourValar\Office\SheetsService;
use AnourValar\Office\Format;
use ZipArchive;

class AnnualInventoryService
{
    const MAX_ITEMS_PER_PID = 400;

    /**
     * Search PID Number
     */
    public function searchByPID(string $search, array $filters = [], int $perPage = 20): array
    {
        $query = AnnualInventory::query();

        // Search by PID or PIC
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('pid', 'LIKE', "%{$search}%")
                    ->orWhere('pic_name', 'LIKE', "%{$search}%");
            });
        }

        // Location filter
        if (!empty($filters['location'])) {
            $query->where('location', $filters['location']);
        }

        // Status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $query->orderBy('created_at', 'desc');
        $paginated = $query->paginate($perPage);

        return $this->formatPaginatedPIDs($paginated);
    }

    /**
     * Get PID by exact match
     */
    public function getByPID(string $pid): ?array
    {
        $inventory = AnnualInventory::where('pid', $pid)->first();

        if (!$inventory) {
            return null;
        }

        return $this->formatInventoryWithItems($inventory);
    }

    /**
     * Get all PIDs with pagination and filters
     */
    public function getAllPIDs(array $filters = [], int $perPage = 20): array
    {
        $query = AnnualInventory::query();

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('pid', 'LIKE', "%{$search}%")
                    ->orWhere('pic_name', 'LIKE', "%{$search}%");
            });
        }

        // Location filter
        if (!empty($filters['location'])) {
            $query->where('location', $filters['location']);
        }

        // Status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $query->orderBy('created_at', 'desc');
        $paginated = $query->paginate($perPage);

        return $this->formatPaginatedPIDs($paginated);
    }

    /**
     * Format paginated PIDs response
     */
    private function formatPaginatedPIDs($paginated): array
    {
        return [
            'items' => $paginated->map(function ($inventory) {
                $itemsCount = $inventory->items()->count();
                $countedCount = $inventory->items()->where('status', '!=', 'PENDING')->count();

                return [
                    'id' => $inventory->id,
                    'pid' => $inventory->pid,
                    'date' => $inventory->date?->format('Y-m-d'),
                    'status' => $inventory->status,
                    'pic_name' => $inventory->pic_name,
                    'location' => $inventory->location,
                    'items_count' => $itemsCount,
                    'counted_count' => $countedCount,
                    'progress' => $itemsCount > 0 ? round(($countedCount / $itemsCount) * 100, 1) : 0,
                ];
            })->all(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
        ];
    }

    /**
     * Get single PID with all items for counting page
     */
    public function getPIDWithItems(int $id): ?array
    {
        $inventory = AnnualInventory::find($id);

        if (!$inventory) {
            return null;
        }

        return $this->formatInventoryWithItems($inventory);
    }

    /**
     * Format inventory with items for response
     */
    private function formatInventoryWithItems(AnnualInventory $inventory): array
    {
        $items = $inventory->items()
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'material_number' => $item->material_number,
                    'description' => $item->description,
                    'rack_address' => $item->rack_address,
                    'unit_of_measure' => $item->unit_of_measure,
                    'system_qty' => (float) $item->system_qty,
                    'soh' => $item->soh !== null ? (float) $item->soh : null,
                    'actual_qty' => $item->actual_qty !== null ? (float) $item->actual_qty : null,
                    'price' => (float) $item->price,
                    'outstanding_gr' => $item->outstanding_gr !== null ? (float) $item->outstanding_gr : null,
                    'outstanding_gi' => $item->outstanding_gi !== null ? (float) $item->outstanding_gi : null,
                    'error_movement' => $item->error_movement !== null ? (float) $item->error_movement : null,
                    'final_discrepancy' => $item->final_discrepancy !== null ? (float) $item->final_discrepancy : null,
                    'final_discrepancy_amount' => $item->final_discrepancy_amount !== null ? (float) $item->final_discrepancy_amount : null,
                    'status' => $item->status,
                    'counted_by' => $item->counted_by,
                    'counted_at' => $item->counted_at?->format('Y-m-d H:i:s'),
                    'notes' => $item->notes,
                    'actual_qty_history' => $item->actual_qty_history?->all(),
                ];
            });

        $countedCount = $items->where('status', '!=', 'PENDING')->count();

        return [
            'id' => $inventory->id,
            'pid' => $inventory->pid,
            'date' => $inventory->date?->format('Y-m-d'),
            'status' => $inventory->status,
            'pic_name' => $inventory->pic_name,
            'location' => $inventory->location,
            'items_count' => $items->count(),
            'counted_count' => $countedCount,
            'progress' => $items->count() > 0 ? round(($countedCount / $items->count()) * 100, 1) : 0,
            'items' => $items->all(),
        ];
    }

    /**
     * Update single item
     */
    public function updateItem(int $itemId, array $data): array
    {
        try {
            $item = AnnualInventoryItems::find($itemId);

            if (!$item) {
                return [
                    'success' => false,
                    'message' => 'Item not found',
                ];
            }

            DB::beginTransaction();

            // Update item with provided data
            $item->update($data);

            // Update parent PID status
            $this->updatePIDStatus($item->annual_inventory_id);

            DB::commit();

            return [
                'success' => true,
                'data' => $item->fresh(),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Insert/Update Actual Qty for an item
     */
    public function insertActualQty(int $itemId, array $data, ?object $imageFile = null): array
    {
        try {
            $item = AnnualInventoryItems::find($itemId);

            if (!$item) {
                return [
                    'success' => false,
                    'message' => 'Item not found',
                ];
            }

            DB::beginTransaction();

            // Handle image upload to S3
            if ($imageFile) {
                // Delete old image if exists
                if ($item->image_path) {
                    Storage::disk('s3')->delete($item->image_path);
                }

                $path = $imageFile->store(
                    'annual-inventory/' . $item->annual_inventory_id,
                    's3'
                );
                $data['image_path'] = $path;
            }

            // Calculate final discrepancy if actual_qty is provided
            if (isset($data['actual_qty'])) {
                $actualQty = (float) $data['actual_qty'];
                $soh = (float) ($data['soh'] ?? $item->soh ?? 0);
                $outstandingGR = (float) ($data['outstanding_gr'] ?? $item->outstanding_gr ?? 0);
                $outstandingGI = (float) ($data['outstanding_gi'] ?? $item->outstanding_gi ?? 0);
                $errorMovement = (float) ($data['error_movement'] ?? $item->error_movement ?? 0);

                // Formula: actual - soh + outstanding_gr + outstanding_gi + error_movement
                $finalDiscrepancy = ($actualQty - $soh) - ($outstandingGR + $outstandingGI + $errorMovement);
                $data['final_discrepancy'] = $finalDiscrepancy;
                $data['final_discrepancy_amount'] = $finalDiscrepancy * (float) $item->price;
            }


            // Set status to COUNTED, timestamp, and build history
            if (isset($data['actual_qty']) && $data['actual_qty'] !== null) {
                $data['status'] = $data['status'] ?? 'COUNTED';
                if (!$item->counted_at) {
                    $data['counted_at'] = now();
                }

                // Add to actual_qty_history
                $history = $item->actual_qty_history ?? [];
                $history[] = [
                    'actual_qty' => $data['actual_qty'],
                    'counted_at' => now()->format('Y-m-d H:i:s'),
                ];
                $data['actual_qty_history'] = $history;
            }

            $item->update($data);

            // Update parent PID status
            $this->updatePIDStatus($item->annual_inventory_id);

            DB::commit();

            return [
                'success' => true,
                'data' => $item->fresh(),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Bulk insert/update items
     */
    public function bulkUpdateItems(array $items): array
    {
        try {
            DB::beginTransaction();

            $updated = 0;
            $errors = [];

            foreach ($items as $index => $itemData) {
                $itemId = $itemData['id'] ?? null;

                if (!$itemId) {
                    $errors[] = "Item at index {$index}: Missing ID";
                    continue;
                }

                unset($itemData['id']); // Remove id from update data
                $result = $this->insertActualQty($itemId, $itemData);

                if ($result['success']) {
                    $updated++;
                } else {
                    $errors[] = "Item ID {$itemId}: {$result['message']}";
                }
            }

            DB::commit();

            return [
                'success' => true,
                'updated' => $updated,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Bulk update failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Update PID status based on items status
     */
    public function updatePIDStatus(int $inventoryId): void
    {
        $inventory = AnnualInventory::find($inventoryId);

        if (!$inventory) {
            return;
        }

        $totalItems = $inventory->items()->count();
        $countedItems = $inventory->items()->where('status', '!=', 'PENDING')->count();

        if ($totalItems === 0) {
            $inventory->update(['status' => 'Not Checked']);
        } elseif ($countedItems === $totalItems) {
            // All items have been counted (status is not PENDING)
            $inventory->update(['status' => 'Completed']);
        } elseif ($countedItems > 0) {
            $inventory->update(['status' => 'In Progress']);
        } else {
            $inventory->update(['status' => 'Not Checked']);
        }
    }

    /**
     * Import PIDs from Excel
     * Expected columns: location, pid, material_number
     */
    public function importExcel($filePath): array
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();

            // Extract metadata from specific cells
            $pid = trim($worksheet->getCell('D13')->getValue() ?? ''); // PID No. is in C13
            $location = trim($worksheet->getCell('D12')->getValue() ?? ''); // Sloc in C12
            $date = trim($worksheet->getCell('D14')->getValue() ?? '');

            if (empty($pid)) {
                return [
                    'success' => false,
                    'message' => 'PID No. not found in cell C13',
                ];
            }

            // Get all rows as array
            $rows = $worksheet->toArray();

            // Find the data table header row (row 22, index 21)
            // Look for "NO." and "NOMOR MATERIAL" to confirm header position
            $dataStartRow = null;
            foreach ($rows as $index => $row) {
                if (
                    isset($row[1]) && trim($row[1]) === 'NO.' &&
                    isset($row[2]) && str_contains(strtoupper(trim($row[2])), 'NOMOR')
                ) {
                    $dataStartRow = $index + 1; // Data starts after header
                    break;
                }
            }

            if ($dataStartRow === null) {
                return [
                    'success' => false,
                    'message' => 'Could not find data table header',
                ];
            }

            // Extract materials from data rows
            $materials = [];
            for ($i = $dataStartRow; $i < count($rows); $i++) {
                $row = $rows[$i];
                $materialNumber = trim($row[2] ?? ''); // Column B - NOMOR MATERIAL

                if (empty($materialNumber)) {
                    continue; // Skip empty rows
                }

                $materials[] = [
                    'material_number' => $materialNumber,
                    'description' => trim($row[3] ?? ''),     // Column D - NAMA BARANG
                    'rack_address' => trim($row[5] ?? ''),    // Column F - ALAMAT RACK
                    'unit_of_measure' => trim($row[6] ?? ''), // Column G - SATUAN
                ];
            }

            // Check item limit
            if (count($materials) > self::MAX_ITEMS_PER_PID) {
                return [
                    'success' => false,
                    'message' => "PID '{$pid}': Exceeds maximum " . self::MAX_ITEMS_PER_PID . " items limit",
                ];
            }

            DB::beginTransaction();

            $pidsCreated = 0;
            $pidsUpdated = 0;
            $itemsCreated = 0;
            $errors = [];

            // Find or create PID
            $inventory = AnnualInventory::where('pid', $pid)->first();

            if (!$inventory) {
                $inventory = AnnualInventory::create([
                    'pid' => $pid,
                    'date' => $date,
                    'status' => 'Not Checked',
                    'pic_name' => '',
                    'location' => $location,
                ]);
                $pidsCreated++;
            } else {
                $pidsUpdated++;
            }

            // Add materials
            foreach ($materials as $mat) {
                // Check if material already exists in this PID
                $exists = AnnualInventoryItems::where('annual_inventory_id', $inventory->id)
                    ->where('material_number', $mat['material_number'])
                    ->exists();

                if ($exists) {
                    continue;
                }

                // Check current items count
                $currentCount = AnnualInventoryItems::where('annual_inventory_id', $inventory->id)->count();

                if ($currentCount >= self::MAX_ITEMS_PER_PID) {
                    $errors[] = "PID '{$pid}': Cannot add more items, limit reached";
                    break;
                }

                // Use data from Excel directly (or lookup from Materials table if needed)
                AnnualInventoryItems::create([
                    'annual_inventory_id' => $inventory->id,
                    'material_number' => $mat['material_number'],
                    'description' => $mat['description'],
                    'rack_address' => $mat['rack_address'],
                    'unit_of_measure' => $mat['unit_of_measure'],
                    'system_qty' => 0,
                    'price' => 0,
                    'status' => 'PENDING',
                    'counted_by' => '',
                    'counted_at' => date('Y-m-d H:i:s')
                ]);

                $itemsCreated++;
            }

            DB::commit();

            return [
                'success' => true,
                'pids_created' => $pidsCreated,
                'pids_updated' => $pidsUpdated,
                'items_created' => $itemsCreated,
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
     * Get statistics for dashboard
     */
    public function getStatistics(array $filters = []): array
    {
        $pidQuery = AnnualInventory::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $pidQuery->where(function ($q) use ($search) {
                $q->where('pid', 'LIKE', "%{$search}%")
                  ->orWhere('pic_name', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($filters['location'])) {
            $pidQuery->where('location', $filters['location']);
        }

        if (!empty($filters['status'])) {
            $pidQuery->where('status', $filters['status']);
        }

        $filteredPidIds = $pidQuery->pluck('id');

        $totalPIDs = $filteredPidIds->count();
        $completedPIDs = (clone $pidQuery)->where('status', 'Completed')->count();
        $inProgressPIDs = (clone $pidQuery)->where('status', 'In Progress')->count();
        $notCheckedPIDs = (clone $pidQuery)->where('status', 'Not Checked')->count();

        $itemQuery = AnnualInventoryItems::whereIn('annual_inventory_id', $filteredPidIds);
        $totalItems = (clone $itemQuery)->count();
        $countedItems = (clone $itemQuery)->where('status', '!=', 'PENDING')->count();
        $pendingItems = (clone $itemQuery)->where('status', 'PENDING')->count();

        return [
            'pids' => [
                'total' => $totalPIDs,
                'completed' => $completedPIDs,
                'in_progress' => $inProgressPIDs,
                'not_checked' => $notCheckedPIDs,
                'completion_rate' => $totalPIDs > 0 ? round(($completedPIDs / $totalPIDs) * 100, 1) : 0,
            ],
            'items' => [
                'total' => $totalItems,
                'counted' => $countedItems,
                'pending' => $pendingItems,
                'completion_rate' => $totalItems > 0 ? round(($countedItems / $totalItems) * 100, 1) : 0,
            ],
        ];
    }

    /**
     * Delete PID and all its items
     */
    public function deletePID(int $id): array
    {
        try {
            $inventory = AnnualInventory::find($id);

            if (!$inventory) {
                return [
                    'success' => false,
                    'message' => 'PID not found',
                ];
            }

            DB::beginTransaction();

            // Delete all images from S3
            $items = $inventory->items()->whereNotNull('image_path')->get();
            foreach ($items as $item) {
                if ($item->image_path) {
                    Storage::disk('s3')->delete($item->image_path);
                }
            }

            // Delete items and inventory
            $inventory->items()->delete();
            $inventory->delete();

            DB::commit();

            return [
                'success' => true,
                'message' => 'PID deleted successfully',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get unique locations
     */
    public function getLocations(): array
    {
        return AnnualInventory::distinct()
            ->pluck('location')
            ->filter()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Upload image for item
     */
    public function uploadItemImage(int $itemId, object $imageFile): array
    {
        try {
            $item = AnnualInventoryItems::find($itemId);

            if (!$item) {
                return [
                    'success' => false,
                    'message' => 'Item not found',
                ];
            }

            // Delete old image if exists
            if ($item->image_path) {
                Storage::disk('s3')->delete($item->image_path);
            }

            $path = $imageFile->store(
                'annual-inventory/' . $item->annual_inventory_id,
                's3'
            );

            $item->update(['image_path' => $path]);

            return [
                'success' => true,
                'image_path' => $path,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get items for discrepancy analysis
     */
    public function getDiscrepancyItems(array $filters = [], int $perPage = 50): array
    {
        $query = AnnualInventoryItems::query()
            ->join('annual_inventories', 'annual_inventory_items.annual_inventory_id', '=', 'annual_inventories.id')
            ->select([
                'annual_inventory_items.*',
                'annual_inventories.pid',
                'annual_inventories.location',
                'annual_inventories.pic_name',
            ]);

        // Apply filters to main query
        $this->applyDiscrepancyFilters($query, $filters);

        $query->orderBy('annual_inventories.pid', 'asc')
            ->orderBy('annual_inventory_items.material_number', 'asc');

        $paginated = $query->paginate($perPage);

        // Build statistics query with the same filters applied
        $statsQuery = AnnualInventoryItems::query()
            ->join('annual_inventories', 'annual_inventory_items.annual_inventory_id', '=', 'annual_inventories.id')
            ->select('annual_inventory_items.*');

        // Apply same filters to stats query
        $this->applyDiscrepancyFilters($statsQuery, $filters, true);

        // Get all filtered items for statistics calculation
        $allItems = $statsQuery->get();
        $statistics = $this->calculateDiscrepancyStatistics($allItems);

        return [
            'items' => $paginated->map(function ($item) {
                return [
                    'id' => $item->id,
                    'pid' => $item->pid,
                    'location' => $item->location,
                    'material_number' => $item->material_number,
                    'description' => $item->description,
                    'rack_address' => $item->rack_address,
                    'unit_of_measure' => $item->unit_of_measure,
                    'system_qty' => (float) $item->system_qty,
                    'soh' => $item->soh !== null ? (float) $item->soh : null,
                    'soh_updated_at' => $item->soh_updated_at,
                    'actual_qty' => $item->actual_qty !== null ? (float) $item->actual_qty : null,
                    'price' => (float) $item->price,
                    'price_updated_at' => $item->price_updated_at,
                    'outstanding_gr' => $item->outstanding_gr !== null ? (float) $item->outstanding_gr : null,
                    'outstanding_gi' => $item->outstanding_gi !== null ? (float) $item->outstanding_gi : null,
                    'error_movement' => $item->error_movement !== null ? (float) $item->error_movement : null,
                    'final_discrepancy' => $item->final_discrepancy !== null ? (float) $item->final_discrepancy : null,
                    'final_discrepancy_amount' => $item->final_discrepancy_amount !== null ? (float) $item->final_discrepancy_amount : null,
                    'status' => $item->status,
                    'counted_by' => $item->counted_by,
                    'counted_at' => $item->counted_at,
                    'notes' => $item->notes,
                    'actual_qty_history' => $item->actual_qty_history ?? [],
                ];
            })->all(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
            'statistics' => $statistics,
        ];
    }

    /**
     * Apply discrepancy filters to a query
     */
    private function applyDiscrepancyFilters($query, array $filters, bool $excludeDiscrepancyType = false): void
    {
        // Filter by PID
        if (!empty($filters['pid_id'])) {
            $query->where('annual_inventory_items.annual_inventory_id', $filters['pid_id']);
        }

        // Filter by item status
        if (!empty($filters['status'])) {
            $query->where('annual_inventory_items.status', $filters['status']);
        }

        // Search filter (material number or description)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('annual_inventory_items.material_number', 'LIKE', "%{$search}%")
                    ->orWhere('annual_inventory_items.description', 'LIKE', "%{$search}%")
                    ->orWhere('annual_inventories.pid', 'LIKE', "%{$search}%");
            });
        }

        // Filter by discrepancy type (exclude for stats query to get accurate percentages)
        if (!$excludeDiscrepancyType && !empty($filters['discrepancy_type'])) {
            switch ($filters['discrepancy_type']) {
                case 'surplus':
                    $query->where('annual_inventory_items.final_discrepancy', '>', 0);
                    break;
                case 'shortage':
                    $query->where('annual_inventory_items.final_discrepancy', '<', 0);
                    break;
                case 'match':
                    $query->where('annual_inventory_items.final_discrepancy', '=', 0);
                    break;
            }
        }

        // Filter by location
        if (!empty($filters['location'])) {
            $query->where('annual_inventories.location', $filters['location']);
        }

        // Only show counted items
        if (!empty($filters['counted_only']) && $filters['counted_only'] !== 'false') {
            $query->where('annual_inventory_items.status', '!=', 'PENDING');
        }
    }

    /**
     * Calculate discrepancy statistics with percentages
     * Computes discrepancy on-the-fly from raw fields to match frontend formula
     */
    private function calculateDiscrepancyStatistics($items): array
    {
        $totalItems = count($items);
        $surplusCount = 0;
        $discrepancyCount = 0;
        $matchCount = 0;
        $surplusAmount = 0;
        $discrepancyAmount = 0;
        $totalAmount = 0;

        foreach ($items as $item) {
            $actualQty = (float) ($item->actual_qty ?? 0);
            $soh = (float) ($item->soh ?? 0);
            $outstandingGR = (float) ($item->outstanding_gr ?? 0);
            $outstandingGI = (float) ($item->outstanding_gi ?? 0);
            $errorMovement = (float) ($item->error_movement ?? 0);
            $price = (float) ($item->price ?? 0);

            // Compute discrepancy from raw fields (matches frontend getFinalDiscrepancy)
            $initialGap = $actualQty - $soh;
            $finalDiscrepancy = $initialGap - $outstandingGR + $outstandingGI + $errorMovement;
            $finalDiscrepancyAmount = $finalDiscrepancy * $price;

            $initialAmount = $actualQty * $price;

            // Calculate totals
            if ($finalDiscrepancy > 0) {
                $surplusCount++;
                $surplusAmount += $finalDiscrepancyAmount;
                $totalAmount += $initialAmount;
            } elseif ($finalDiscrepancy < 0) {
                $discrepancyCount++;
                $discrepancyAmount += $finalDiscrepancyAmount;
                $totalAmount += $initialAmount;
            } else {
                $matchCount++;
                $totalAmount += $initialAmount;
            }
        }

        // Calculate percentages
        $surplusCountPercent = $totalItems > 0 ? round(($surplusCount / $totalItems) * 100, 1) : 0;
        $discrepancyCountPercent = $totalItems > 0 ? round(($discrepancyCount / $totalItems) * 100, 1) : 0;
        $matchCountPercent = $totalItems > 0 ? round(($matchCount / $totalItems) * 100, 1) : 0;

        // For amount percentages, calculate based on total absolute amounts
        $totalAbsAmount = $totalAmount > 0 ? abs($totalAmount) : 0;
        $surplusAmountPercent = $totalAbsAmount > 0 ? round((abs($surplusAmount) / $totalAbsAmount) * 100, 1) : 0;
        $discrepancyAmountPercent = $totalAbsAmount > 0 ? round((abs($discrepancyAmount) / $totalAbsAmount) * 100, 1) : 0;

        return [
            'totalItems' => $totalItems,
            'surplusCount' => $surplusCount,
            'discrepancyCount' => $discrepancyCount,
            'matchCount' => $matchCount,
            'surplusAmount' => (float) $surplusAmount,
            'discrepancyAmount' => (float) abs($discrepancyAmount),
            'surplusCountPercent' => $surplusCountPercent,
            'discrepancyCountPercent' => $discrepancyCountPercent,
            'matchCountPercent' => $matchCountPercent,
            'totalAmount' => $totalAbsAmount,
            'surplusAmountPercent' => $surplusAmountPercent,
            'discrepancyAmountPercent' => $discrepancyAmountPercent,
        ];
    }

    /**
     * Bulk update discrepancy fields (SOH, outstanding GR/GI, error movement)
     */
    public function bulkUpdateDiscrepancy(array $items): array
    {
        try {
            DB::beginTransaction();

            $updated = 0;
            $errors = [];

            foreach ($items as $itemData) {
                $itemId = $itemData['id'] ?? null;

                if (!$itemId) {
                    continue;
                }

                $item = AnnualInventoryItems::find($itemId);

                if (!$item) {
                    $errors[] = "Item ID {$itemId}: Not found";
                    continue;
                }

                // Update discrepancy fields including SOH
                $soh = $itemData['soh'] ?? $item->soh ?? 0;
                $outstandingGR = $itemData['outstanding_gr'] ?? $item->outstanding_gr ?? 0;
                $outstandingGI = $itemData['outstanding_gi'] ?? $item->outstanding_gi ?? 0;
                $errorMovement = $itemData['error_movement'] ?? $item->error_movement ?? 0;

                $updateData = [
                    'soh' => (float) $soh,
                    'outstanding_gr' => (float) $outstandingGR,
                    'outstanding_gi' => (float) $outstandingGI,
                    'error_movement' => (float) $errorMovement,
                ];

                // Update SOH timestamp if SOH value changed
                if (isset($itemData['soh']) && (float) $itemData['soh'] !== (float) ($item->soh ?? 0)) {
                    $updateData['soh_updated_at'] = now();
                }

                // Recalculate final discrepancy if actual_qty exists
                if ($item->actual_qty !== null) {
                    $actualQty = (float) $item->actual_qty;

                    // Formula: actual - soh + outstanding_gr + outstanding_gi + error_movement
                    $finalDiscrepancy = $actualQty - (float) $soh + (float) $outstandingGR + (float) $outstandingGI + (float) $errorMovement;
                    $updateData['final_discrepancy'] = $finalDiscrepancy;
                    $updateData['final_discrepancy_amount'] = $finalDiscrepancy * (float) $item->price;
                }

                $item->update($updateData);
                $updated++;
            }

            DB::commit();

            return [
                'success' => true,
                'updated' => $updated,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Bulk update failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get all PIDs for dropdown
     */
    public function getAllPIDsForDropdown(): array
    {
        return AnnualInventory::select('id', 'pid', 'location')
            ->orderBy('pid', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'pid' => $item->pid,
                    'location' => $item->location,
                ];
            })
            ->all();
    }

    /**
     * Update PID details
     */
    public function updatePID(int $id, array $data): array
    {
        try {
            $inventory = AnnualInventory::find($id);

            if (!$inventory) {
                return [
                    'success' => false,
                    'message' => 'PID not found',
                ];
            }

            $inventory->update(array_filter($data, fn($v) => $v !== null));

            return [
                'success' => true,
                'data' => $inventory->fresh(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get PID by PID number with pagination for items
     */
    public function getByPIDWithPagination(string $pid, int $perPage = 50, int $page = 1, string $search = '', string $sortBy = 'material_number', string $sortOrder = 'asc'): ?array
    {
        $inventory = AnnualInventory::where('pid', $pid)->first();

        if (!$inventory) {
            return null;
        }

        $query = AnnualInventoryItems::where('annual_inventory_id', $inventory->id);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('material_number', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%")
                    ->orWhere('rack_address', 'LIKE', "%{$search}%");
            });
        }

        $allowedSortColumns = ['material_number', 'rack_address', 'description', 'status'];
        $sortBy = in_array($sortBy, $allowedSortColumns) ? $sortBy : 'material_number';
        $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'asc';

        $paginated = $query->orderBy($sortBy, $sortOrder)->paginate($perPage, ['*'], 'page', $page);

        // Count statistics (from all items, not just paginated)
        $allItems = AnnualInventoryItems::where('annual_inventory_id', $inventory->id);
        $totalCount = $allItems->count();
        $countedCount = (clone $allItems)->where('status', '!=', 'PENDING')->count();

        return [
            'id' => $inventory->id,
            'pid' => $inventory->pid,
            'date' => $inventory->date?->format('Y-m-d'),
            'status' => $inventory->status,
            'pic_name' => $inventory->pic_name,
            'location' => $inventory->location,
            'items' => $paginated->map(function ($item) {
                return [
                    'id' => $item->id,
                    'material_number' => $item->material_number,
                    'description' => $item->description,
                    'rack_address' => $item->rack_address,
                    'unit_of_measure' => $item->unit_of_measure,
                    'system_qty' => (float) $item->system_qty,
                    'soh' => $item->soh !== null ? (float) $item->soh : null,
                    'actual_qty' => $item->actual_qty !== null ? (float) $item->actual_qty : null,
                    'price' => (float) $item->price,
                    'status' => $item->status,
                    'counted_by' => $item->counted_by,
                    'counted_at' => $item->counted_at,
                    'image_path' => $item->image_path,
                    'image_url' => $item->image_path,
                    'notes' => $item->notes,
                ];
            })->all(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
            'statistics' => [
                'total' => $totalCount,
                'counted' => $countedCount,
                'pending' => $totalCount - $countedCount,
                'progress' => $totalCount > 0 ? round(($countedCount / $totalCount) * 100) : 0,
            ],
        ];
    }

    /**
     * Generate discrepancy Excel template
     */
    public function generateDiscrepancyTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = ['pid', 'Material Number', 'SOH', 'Outstanding GR', 'Outstanding GI', 'Error Movement', 'Price'];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('E5E7EB');
            $col++;
        }

        // Example data
        $examples = [
            ['20252201', 'RM-2024-001', 50, 0, 0, 0],
            ['20252201', 'PK-2024-055', 1200, 0, -100, 0],
            ['20252201', 'EL-2024-889', 45, 5, 0, 0],
        ];

        $row = 2;
        foreach ($examples as $example) {
            $sheet->setCellValue('A' . $row, $example[0]);
            $sheet->setCellValue('B' . $row, $example[1]);
            $sheet->setCellValue('C' . $row, $example[2]);
            $sheet->setCellValue('D' . $row, $example[3]);
            $sheet->setCellValue('E' . $row, $example[4]);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'E') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $filename = 'annual_inventory_discrepancy_template.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import discrepancy data from Excel
     */
    public function importDiscrepancyExcel(string $filePath): array
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            // Skip header row
            array_shift($rows);

            $updated = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                $rowNum = $index + 2; // Account for header and 0-index

                $pidNumber = trim($row[0] ?? '');
                $materialNumber = trim($row[1] ?? '');
                $soh = is_numeric($row[2]) ? (float) $row[2] : null;
                $outstandingGR = is_numeric($row[3]) ? (float) $row[3] : 0;
                $outstandingGI = is_numeric($row[4]) ? (float) $row[4] : 0;
                $errorMovement = is_numeric($row[5]) ? (float) $row[5] : 0;
                $price = is_numeric($row[6]) ? (float) $row[6] : 0;

                if (empty($materialNumber)) {
                    continue;
                }

                // Find item by material number and PID
                $item = AnnualInventoryItems::query()
                    ->whereHas('annualInventory', function ($q) use ($pidNumber) {
                        $q->where('pid', $pidNumber);
                    })
                    ->where('material_number', $materialNumber)
                    ->first();

                if (!$item) {
                    $errors[] = "Row {$rowNum}: Material '{$materialNumber}' not found";
                    continue;
                }

                $updateData = [
                    'outstanding_gr' => $outstandingGR,
                    'outstanding_gi' => $outstandingGI,
                    'error_movement' => $errorMovement,
                    'price' => $price,
                ];

                if ($soh !== null) {
                    $updateData['soh'] = $soh;
                }

                // Recalculate final discrepancy if actual_qty exists
                if ($item->actual_qty !== null) {
                    $actualSoh = $soh ?? $item->soh ?? 0;
                    $finalDiscrepancy = $item->actual_qty - $actualSoh + $outstandingGR + $outstandingGI + $errorMovement;
                    $updateData['final_discrepancy'] = $finalDiscrepancy;
                    $updateData['final_discrepancy_amount'] = $finalDiscrepancy * (float) $item->price;
                }

                $item->update($updateData);
                $updated++;
            }

            DB::commit();

            return [
                'success' => true,
                'updated' => $updated,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Export discrepancy data to Excel with filters
     */
    public function exportDiscrepancyToExcel(array $filters = [])
    {
        $query = AnnualInventoryItems::query()
            ->join('annual_inventories', 'annual_inventory_items.annual_inventory_id', '=', 'annual_inventories.id')
            ->select([
                'annual_inventory_items.*',
                'annual_inventories.pid',
                'annual_inventories.location',
                'annual_inventories.pic_name',
            ]);

        // Apply filters
        $this->applyDiscrepancyFilters($query, $filters);

        $query->orderBy('annual_inventories.pid', 'asc')
            ->orderBy('annual_inventory_items.material_number', 'asc');

        $items = $query->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = [
            'PID',
            'Location',
            'Material Number',
            'Description',
            'Rack',
            'UoM',
            'Price',
            'SOH',
            'Actual Qty',
            'Initial Gap',
            'Outstanding GR (+)',
            'Outstanding GI (-)',
            'Error Movement',
            'Final Discrepancy',
            'Final Amount',
            'Status',
            'Counted By',
            'Counted At',
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('E5E7EB');
            $col++;
        }

        $row = 2;
        foreach ($items as $item) {
            $actualQty = (float) ($item->actual_qty ?? 0);
            $soh = (float) ($item->soh ?? 0);
            $initialGap = $actualQty - $soh;
            $outstandingGR = (float) ($item->outstanding_gr ?? 0);
            $outstandingGI = (float) ($item->outstanding_gi ?? 0);
            $errorMovement = (float) ($item->error_movement ?? 0);
            $finalDiscrepancy = $initialGap + $outstandingGR + $outstandingGI + $errorMovement;
            $price = (float) ($item->price ?? 0);
            $finalAmount = $finalDiscrepancy * $price;

            $sheet->setCellValue('A' . $row, $item->pid);
            $sheet->setCellValue('B' . $row, $item->location);
            $sheet->setCellValue('C' . $row, $item->material_number);
            $sheet->setCellValue('D' . $row, $item->description);
            $sheet->setCellValue('E' . $row, $item->rack_address);
            $sheet->setCellValue('F' . $row, $item->unit_of_measure);
            $sheet->setCellValue('G' . $row, $price);
            $sheet->setCellValue('H' . $row, $soh);
            $sheet->setCellValue('I' . $row, $actualQty);
            $sheet->setCellValue('J' . $row, $initialGap);
            $sheet->setCellValue('K' . $row, $outstandingGR);
            $sheet->setCellValue('L' . $row, $outstandingGI);
            $sheet->setCellValue('M' . $row, $errorMovement);
            $sheet->setCellValue('N' . $row, $finalDiscrepancy);
            $sheet->setCellValue('O' . $row, $finalAmount);
            $sheet->setCellValue('P' . $row, $item->status);
            $sheet->setCellValue('Q' . $row, $item->counted_by);
            $sheet->setCellValue('R' . $row, $item->counted_at);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'R') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Format currency columns
        $lastRow = $row - 1;
        if ($lastRow >= 2) {
            $sheet->getStyle('G2:G' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('O2:O' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $filename = 'annual_inventory_discrepancy_' . date('Y-m-d_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportToExcel(array $filters = [])
    {
        // Build query WITHOUT eager loading items (load per-PID to save memory)
        $query = AnnualInventory::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('pid', 'LIKE', "%{$search}%")
                    ->orWhere('pic_name', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($filters['location'])) {
            $query->where('location', $filters['location']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Optional: allow selecting explicit PIDs
        if (!empty($filters['pids']) && is_array($filters['pids'])) {
            $query->whereIn('pid', $filters['pids']);
        }

        $query->orderBy('pid', 'asc');

        // Check if any data exists
        $count = $query->count();
        if ($count === 0) {
            return null;
        }

        // mode: auto | single | zip
        $mode = $filters['mode'] ?? 'auto';

        // Template - check if exists
        $template = storage_path('app/templates/worksheet_template.xlsx');
        if (!file_exists($template)) {
            throw new \Exception('Export template file not found: worksheet_template.xlsx');
        }
        $format = Format::Xlsx;

        // If single mode or auto with 1 PID
        if ($mode === 'single' || ($mode === 'auto' && $count === 1)) {
            $pid = $this->resolveSinglePidFromQuery($query, $filters);
            if (!$pid) {
                return null;
            }
            // No eager loading needed - downloadSingleWorksheet uses raw queries
            return $this->downloadSingleWorksheet($pid, $template, $format);
        }

        // Otherwise zip (multi or forced zip)
        return $this->downloadZipWorksheetChunked($query, $template, $format);
    }

    /**
     * Resolve single PID from query (memory efficient)
     */
    private function resolveSinglePidFromQuery($query, array $filters)
    {
        if (!empty($filters['pid'])) {
            $found = (clone $query)->where('pid', $filters['pid'])->first();
            if ($found) return $found;
        }
        return $query->first();
    }

    /**
     * Download single worksheet using template with PhpSpreadsheet directly
     * More memory efficient than SheetsService
     */
    private function downloadSingleWorksheet($pid, string $template, Format $format)
    {
        // Try to increase memory limit for this operation
        $originalMemoryLimit = ini_get('memory_limit');
        @ini_set('memory_limit', '1G');

        DB::disableQueryLog();
        \Log::info('Export: Starting template-based export', [
            'pid' => $pid->pid,
            'memory_limit' => ini_get('memory_limit'),
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB'
        ]);

        try {
            // Load template with PhpSpreadsheet directly (not SheetsService)
            $spreadsheet = IOFactory::load($template);
            $sheet = $spreadsheet->getActiveSheet();

            // Fill header data based on template structure
            // D11 [plant], D12 [sloc], D13 [pid], D14 [doc_date]
            $sheet->setCellValue('D11', $pid->plant ?? '2000 - Sunter 2');
            $sheet->setCellValue('D12', $pid->sloc ?? $pid->location ?? '');
            $sheet->setCellValue('D13', $pid->pid);
            $sheet->setCellValue('D14', optional($pid->date)->format('n/j/Y h:i:s A') ?? now()->format('n/j/Y h:i:s A'));

            // Data starts at row 23
            // Columns: B [no], C [material_number], D [description], E [batch], F [rack_address], G [uom], H [checking]
            $dataStartRow = 23;

            // Use raw query with cursor for memory efficiency
            $items = DB::table('annual_inventory_items')
                ->select(['material_number', 'description', 'rack_address', 'unit_of_measure', 'actual_qty'])
                ->where('annual_inventory_id', $pid->id)
                ->cursor();

            $row = $dataStartRow;
            $no = 1;
            foreach ($items as $item) {
                $sheet->setCellValue('B' . $row, $no);
                $sheet->setCellValue('C' . $row, $item->material_number);
                $sheet->setCellValue('D' . $row, $item->description);
                $sheet->setCellValue('E' . $row, ''); // Batch
                $sheet->setCellValue('F' . $row, $item->rack_address);
                $sheet->setCellValue('G' . $row, $item->unit_of_measure);
                $sheet->setCellValue('H' . $row, $item->actual_qty ?? '');
                $row++;
                $no++;
            }

            $safePid = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) $pid->pid);
            $filename = "Worksheet_{$safePid}_" . now()->format('Ymd_His') . ".xlsx";

            $tmpFile = storage_path('app/tmp/' . $filename);
            if (!is_dir(dirname($tmpFile))) {
                mkdir(dirname($tmpFile), 0777, true);
            }

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($tmpFile);

            // Cleanup memory
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $writer, $items);
            gc_collect_cycles();

            // Restore original memory limit
            @ini_set('memory_limit', $originalMemoryLimit);

            \Log::info('Export: Template-based export completed', ['file' => $tmpFile]);

            return response()->download($tmpFile, $filename)->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            \Log::error('Export: Template-based export failed', [
                'error' => $e->getMessage(),
                'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB'
            ]);

            // Cleanup on error
            if (isset($spreadsheet)) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
            }
            gc_collect_cycles();
            @ini_set('memory_limit', $originalMemoryLimit);

            throw $e;
        }
    }

    /**
     * Optimized Zip Download using template with PhpSpreadsheet directly
     */
    private function downloadZipWorksheetChunked($query, string $template, Format $format)
    {
        set_time_limit(0);
        DB::disableQueryLog();

        $zipName = 'annual_inventory_worksheets_' . now()->format('Ymd_His') . '.zip';
        $zipPath = storage_path('app/tmp/' . $zipName);

        if (!is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0777, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Failed to create ZIP file');
        }

        $counter = 0;

        // Process each PID one at a time
        foreach ($query->cursor() as $pid) {
            // Generate Excel from template for this PID
            $binary = $this->generateTemplateExcelBinary($pid, $template);

            $safePid = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) $pid->pid);
            $localName = "Worksheet_{$safePid}.xlsx";

            $zip->addFromString($localName, $binary);

            unset($binary);
            $counter++;

            // Garbage collect every 2 files (more aggressive for template-based)
            if ($counter % 2 === 0) {
                gc_collect_cycles();
            }
        }

        $zip->close();

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }

    /**
     * Generate Excel binary from template for a single PID (memory efficient)
     */
    private function generateTemplateExcelBinary($pid, string $template): string
    {
        // Load fresh copy of template
        $spreadsheet = IOFactory::load($template);
        $sheet = $spreadsheet->getActiveSheet();

        // Fill header data
        $sheet->setCellValue('D11', $pid->plant ?? '2000 - Sunter 2');
        $sheet->setCellValue('D12', $pid->sloc ?? $pid->location ?? '');
        $sheet->setCellValue('D13', $pid->pid);
        $sheet->setCellValue('D14', optional($pid->date)->format('n/j/Y h:i:s A') ?? now()->format('n/j/Y h:i:s A'));

        // Data starts at row 23
        $dataStartRow = 23;

        $items = DB::table('annual_inventory_items')
            ->select(['material_number', 'description', 'rack_address', 'unit_of_measure', 'actual_qty'])
            ->where('annual_inventory_id', $pid->id)
            ->cursor();

        $row = $dataStartRow;
        $no = 1;
        foreach ($items as $item) {
            $sheet->setCellValue('B' . $row, $no);
            $sheet->setCellValue('C' . $row, $item->material_number);
            $sheet->setCellValue('D' . $row, $item->description);
            $sheet->setCellValue('E' . $row, ''); // Batch
            $sheet->setCellValue('F' . $row, $item->rack_address);
            $sheet->setCellValue('G' . $row, $item->unit_of_measure);
            $sheet->setCellValue('H' . $row, $item->actual_qty ?? '');
            $row++;
            $no++;
        }

        // Write to memory stream
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        ob_start();
        $writer->save('php://output');
        $binary = ob_get_clean();

        // Cleanup
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $writer);

        return $binary;
    }

    /**
     * Matches your template markers exactly:
     * D11 [plant], D12 [sloc], D13 [pid], D14 [doc_date], D15 [sheet_name]
     * Row 23: B [items.no], C [items.material_number], D [items.description],
     *         E [items.batch], F [items.rack_address], G [items.uom], H [items.checking]
     */
    private function buildTemplateData($pid): array
    {
        return [
            'plant'      => $pid->plant ?? '2000 - Sunter 2',
            'sloc'       => $pid->sloc ?? ($pid->location ?? ''),
            'pid'        => $pid->pid,
            'doc_date'   => optional($pid->date)->format('n/j/Y h:i:s A') ?? now()->format('n/j/Y h:i:s A'),
            'sheet_name' => $pid->sheet_name ?? 'Worksheet Inv. Workshop',

            'items' => $pid->items->values()->map(function ($item) {
                return [
                    'material_number' => $item->material_number,
                    'description'     => $item->description,
                    'batch'           => '',
                    'rack_address'    => $item->rack_address,
                    'uom'             => $item->unit_of_measure,
                    'checking'        => $item->actual_qty ?? '',
                ];
            })->all(),
        ];
    }
}
