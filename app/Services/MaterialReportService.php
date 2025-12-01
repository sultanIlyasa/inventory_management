<?php

namespace App\Services;

use App\Models\Materials;
use Illuminate\Support\Collection;
use App\Models\DailyInput;

use Carbon\Carbon;

class MaterialReportService
{
    /**
     * Get general report with all material statistics
     */
    public function getGeneralReport($filters = [])
    {
        $month = $filters['month'] ?? null;
        $dateFilter = $filters['date'] ?? null;
        $usage = $filters['usage'] ?? null;
        $location = $filters['location'] ?? null;
        $gentani = $filters['gentani'] ?? null;
        $materialsQuery = Materials::query();

        // Apply usage filter
        if ($usage !== null && $usage !== '') {
            if (is_array($usage)) {
                $materialsQuery->whereIn('usage', $usage);
            } elseif (strpos($usage, ',') !== false) {
                $vals = array_map('trim', explode(',', $usage));
                $materialsQuery->whereIn('usage', $vals);
            } else {
                $materialsQuery->where('usage', $usage);
            }
        }

        // Apply Location filter
        if ($location !== null && $location !== '') {
            if (is_array($location)) {
                $materialsQuery->whereIn('location', $location);
            } elseif (strpos($location, ',') !== false) {
                $vals = array_map('trim', explode(',', $location));
                $materialsQuery->whereIn('location', $vals);
            } else {
                $materialsQuery->where('location', $location);
            }
        }
        if ($gentani !== null && $gentani !== '') {
            if (is_array($gentani)) {
                $materialsQuery->whereIn('gentani', $gentani);
            } elseif (strpos($location, ',') !== false) {
                $vals = array_map('trim', explode(',', $gentani));
                $materialsQuery->whereIn('gentani', $vals);
            } else {
                $materialsQuery->where('gentani', $gentani);
            }
        }

        // semua materials yang diperluin
        $materials = $materialsQuery
            ->select(['id', 'material_number', 'description', 'pic_name', 'usage', 'location', 'gentani'])
            ->with(['dailyInputs' => function ($query) use ($month, $dateFilter) {
                $query->select(['id', 'material_id', 'date', 'daily_stock', 'status']);
                if ($month) {
                    $startOfMonth = Carbon::parse($month)->startOfMonth()->toDateString();
                    $endOfMonth = Carbon::parse($month)->endOfMonth()->toDateString();
                    $query->whereBetween('date', [$startOfMonth, $endOfMonth]);
                } elseif ($dateFilter) {
                    $query->where('date', '<=', $dateFilter);
                } else {
                    $query->where('date', '<=', Carbon::now()->toDateString());
                }

                $query->orderBy('date', 'asc');
            }])->get();

        $reports = [];

        // semua materials satuannya material
        foreach ($materials as $material) {
            $dailyInputs = $material->dailyInputs;

            if ($dailyInputs->isEmpty()) {
                $reports[] = $this->getEmptyMaterialReport($material);
                continue;
            }

            $latestInput = $dailyInputs->last();
            $statusDays = $this->calculateStatusDays($dailyInputs);
            $frequencyChanges = $this->calculateFrequencyChanges($dailyInputs);
            $consecutiveDays = $this->calculateConsecutiveDays($dailyInputs, $latestInput);

            $reports[] = [
                'material_number' => $material->material_number,
                'description' => $material->description,
                'pic' => $material->pic_name ?? '-',
                'instock_latest' => $latestInput->daily_stock,
                'current_status' => $latestInput->status,
                'consecutive_days' => $consecutiveDays,
                'status_days' => $statusDays,
                'frequency_changes' => $frequencyChanges,
                'usage' => $material->usage ?? '-',
                'location' => $material->location ?? '-',
                'gentani' => $material->gentani ?? '-',
                'last_updated' => $latestInput->date,
            ];
        }

        return [
            'data' => $reports,
            'statistics' => $this->calculateStatistics($reports),
        ];
    }

    /**
     * Calculate consecutive days for current status (weekdays only)
     */
    public function calculateConsecutiveDays($dailyInputs, $latestInput)
    {
        if (!$latestInput || $dailyInputs->isEmpty()) {
            return 0;
        }

        $currentStatus = $latestInput->status;
        $consecutiveWeekdays = 0;
        $previousDate = null;

        for ($i = $dailyInputs->count() - 1; $i >= 0; $i--) {
            $currentInput = $dailyInputs[$i];

            if ($currentInput->status === $currentStatus) {
                $currentDate = Carbon::parse($currentInput->date);

                if ($currentDate->isWeekend()) {
                    continue;
                }

                if ($previousDate === null) {
                    $consecutiveWeekdays = 1;
                    $previousDate = $currentDate;
                } else {
                    $expectedPreviousDate = $currentDate->copy()->addWeekday();

                    if ($expectedPreviousDate->isSameDay($previousDate)) {
                        $consecutiveWeekdays++;
                        $previousDate = $currentDate;
                    } else {
                        break;
                    }
                }
            } else {
                break;
            }
        }

        return $consecutiveWeekdays;
    }

    /**
     * Calculate days spent in each status
     */
    public function calculateStatusDays($dailyInputs)
    {
        $statusDays = [
            'OK' => 0,
            'CAUTION' => 0,
            'SHORTAGE' => 0,
            'OVERFLOW' => 0,
            'UNCHECKED' => 0,
        ];

        foreach ($dailyInputs as $input) {
            $statusDays[$input->status] = ($statusDays[$input->status] ?? 0) + 1;
        }

        return $statusDays;
    }


    /**
     * Calculate frequency of status changes from OK to problematic states
     */
    public function calculateFrequencyChanges($dailyInputs)
    {
        $changes = [
            'ok_to_caution' => 0,
            'ok_to_shortage' => 0,
            'ok_to_overflow' => 0,
            'caution_to_ok' => 0,
            'shortage_to_ok' => 0,
            'overflow_to_ok' => 0,
            'total_from_ok' => 0,
            'total_to_ok' => 0,
        ];

        for ($i = 1; $i < $dailyInputs->count(); $i++) {
            $previous = $dailyInputs[$i - 1];
            $current = $dailyInputs[$i];

            if ($previous->status !== $current->status) {
                // Count transitions FROM OK
                if ($previous->status === 'OK') {
                    $changes['total_from_ok']++;

                    if ($current->status === 'CAUTION') {
                        $changes['ok_to_caution']++;
                    } elseif ($current->status === 'SHORTAGE') {
                        $changes['ok_to_shortage']++;
                    } elseif ($current->status === 'OVERFLOW') {
                        $changes['ok_to_overflow']++;
                    }
                }

                // Count transitions TO OK (recoveries)
                if ($current->status === 'OK') {
                    $changes['total_to_ok']++;

                    if ($previous->status === 'CAUTION') {
                        $changes['caution_to_ok']++;
                    } elseif ($previous->status === 'SHORTAGE') {
                        $changes['shortage_to_ok']++;
                    } elseif ($previous->status === 'OVERFLOW') {
                        $changes['overflow_to_ok']++;
                    }
                }
            }
        }

        return $changes;
    }

    /**
     * Get Leaderboard by Status
     */

    private function getLeaderboardByStatus(string $status, array $filters = []): Collection
    {
        $generalReport = $this->getGeneralReport($filters);
        $materials = $generalReport['data'];

        return collect($materials)
            ->filter(fn($m) => isset($m['current_status']) && $m['current_status'] === $status)
            ->map(fn($m) => [
                'material_number' => $m['material_number'] ?? null,
                'description' => $m['description'] ?? null,
                'pic' => $m['pic'] ?? '-',
                'current_status' => $m['current_status'] ?? null,
                'current_stock' => $m['instock_latest'] ?? null,
                'days' => $m['consecutive_days'] ?? 0,
                'usage' => $m['usage'] ?? '-',
            ])
            ->sortByDesc('days')
            ->values();
    }


    /**
     * Get caution materials leaderboard
     */
    public function getCautionLeaderboard($filters = []): Collection
    {
        return $this->getLeaderboardByStatus('CAUTION', $filters);
    }

    /**
     * Get shortage materials leaderboard
     */
    public function getShortageLeaderboard($filters = []): Collection
    {
        return $this->getLeaderboardByStatus('SHORTAGE', $filters);
    }

    /**
     * Get all leaderboards at once (for dashboard)
     */
    public function getAllLeaderboards($filters = [])
    {
        return [
            'CAUTION' => $this->getCautionLeaderboard($filters),
            'SHORTAGE' => $this->getShortageLeaderboard($filters),
        ];
    }

    /**
     * Helper: Get empty material report structure
     */
    private function getEmptyMaterialReport($material)
    {
        return [
            'material_number' => $material->material_number,
            'description' => $material->description,
            'pic' => $material->pic_name ?? '-',
            'instock_latest' => 0,
            'current_status' => 'UNCHECKED',
            'status_days' => [
                'OK' => 0,
                'CAUTION' => 0,
                'SHORTAGE' => 0,
                'OVERFLOW' => 0,
                'UNCHECKED' => 0,
            ],
            'consecutive_days' => 0,
            'frequency_changes' => [
                'ok_to_caution' => 0,
                'ok_to_shortage' => 0,
                'ok_to_overflow' => 0,
                'caution_to_ok' => 0,
                'shortage_to_ok' => 0,
                'overflow_to_ok' => 0,
                'total_from_ok' => 0,
                'total_to_ok' => 0,
            ],
            'usage' => $material->usage ?? '-',
            'last_updated' => null,
        ];
    }

    /**
     * Helper: Calculate overall statistics
     */
    private function calculateStatistics($reports)
    {
        $statusCounts = [
            'total' => count($reports),
            'OK' => 0,
            'CAUTION' => 0,
            'SHORTAGE' => 0,
            'OVERFLOW' => 0,
            'UNCHECKED' => 0,
        ];

        foreach ($reports as $report) {
            $status = strtolower($report['current_status']);
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
        }

        return $statusCounts;
    }

    /**
     * Helper: Get last problem date
     */
    private function getLastProblemDate($material)
    {
        // Implementation here
        return null;
    }
    public function getStatusBarChart($filters = [])
    {
        $month = $filters['month'] ?? null;
        $usage = $filters['usage'] ?? null;
        $dateFilter = $filters['date'] ?? Carbon::today()->toDateString();
        $location = $filters['location'] ?? null;
        $gentani = $filters['gentani'] ?? null;

        $materialsQuery = Materials::query();

        if (!empty($usage)) {
            $usages = is_array($usage) ? $usage : array_map('trim', explode(',', $usage));
            $materialsQuery->whereIn('usage', $usages);
        }

        if (!empty($location)) {
            $locations = is_array($location) ? $location : array_map('trim', explode(',', $location));
            $materialsQuery->whereIn('location', $locations);
        }

        if (!empty($gentani)) {
            $gentanis = is_array($gentani) ? $gentani : array_map('trim', explode(',', $gentani));
            $materialsQuery->whereIn('gentani', $gentanis);
        }

        $allMaterials = $materialsQuery->get();

        $dailyInputsQuery = DailyInput::with('material')
            ->when($month, function ($q) use ($month) {
                $start = Carbon::parse($month)->startOfMonth()->toDateString();
                $end = Carbon::parse($month)->endOfMonth()->toDateString();
                $q->whereBetween('date', [$start, $end]);
            })
            ->when(!$month && $dateFilter, function ($q) use ($dateFilter) {
                $q->whereDate('date', '<=', $dateFilter);
            })
            ->whereHas('material', function ($query) use ($usage, $location, $gentani) {
                if (!empty($usage)) {
                    $usages = is_array($usage) ? $usage : array_map('trim', explode(',', $usage));
                    $query->whereIn('usage', $usages);
                }
                if (!empty($location)) {
                    $locations = is_array($location) ? $location : array_map('trim', explode(',', $location));
                    $query->whereIn('location', $locations);
                }
                if (!empty($gentani)) {
                    $gentanis = is_array($gentani) ? $gentani : array_map('trim', explode(',', $gentani));
                    $query->whereIn('gentani', $gentanis);
                }
            })
            ->orderBy('date', 'asc')
            ->get();

        $checked = $dailyInputsQuery
            ->groupBy('material_id')
            ->map(function ($group) {
                $latest = $group->last();
                return [
                    'id' => $latest->material->id,
                    'material_number' => $latest->material->material_number,
                    'usage' => $latest->material->usage,
                    'location' => $latest->material->location,
                    'gentani' => $latest->material->gentani,
                    'daily_stock' => $latest->daily_stock,
                    'status' => $latest->status,
                    'last_updated' => $latest->date,
                ];
            })
            ->values();

        $checkedIds = $checked->pluck('id')->toArray();

        $missing = $allMaterials
            ->whereNotIn('id', $checkedIds)
            ->map(function ($material) {
                return [
                    'id' => $material->id,
                    'material_number' => $material->material_number,
                    'usage' => $material->usage,
                    'location' => $material->location,
                    'gentani' => $material->gentani,
                    'daily_stock' => null,
                    'status' => 'UNCHECKED',
                    'last_updated' => null,
                ];
            })
            ->values();

        $statusCounts = [
            'SHORTAGE' => 0,
            'CAUTION' => 0,
            'OVERFLOW' => 0,
            'UNCHECKED' => 0,
        ];

        $checked->each(function ($item) use (&$statusCounts) {
            $status = strtoupper($item['status'] ?? '');

            if (isset($statusCounts[$status])) {
                $statusCounts[$status]++;
            }
        });

        $statusCounts['UNCHECKED'] += $missing->count();

        return [
            'checked' => $checked,
            'missing' => $missing,
            'statusBarChart' => collect(['SHORTAGE', 'CAUTION', 'OVERFLOW', 'UNCHECKED'])
                ->map(fn($status) => [
                    'status' => $status,
                    'count' => $statusCounts[$status] ?? 0,
                ])
                ->values(),
        ];
    }




    public function getRecoveryDays($filters = [])
    {
        $month = $filters['month'] ?? null;
        $dateFilter = $filters['date'] ?? null;
        $usage = $filters['usage'] ?? null;
        $location = $filters['location'] ?? null;
        $gentani = $filters['gentani'] ?? null;

        $materialsQuery = Materials::query();

        // Apply usage filter
        if ($usage !== null && $usage !== '') {
            if (is_array($usage)) {
                $materialsQuery->whereIn('usage', $usage);
            } else {
                $materialsQuery->where('usage', $usage);
            }
        }

        // Apply location filter
        if ($location !== null && $location !== '') {
            if (is_array($location)) {
                $materialsQuery->whereIn('location', $location);
            } else {
                $materialsQuery->where('location', $location);
            }
        }
        if ($gentani !== null && $gentani !== '') {
            if (is_array($gentani)) {
                $materialsQuery->whereIn('gentani', $gentani);
            } elseif (strpos($gentani, ',') !== false) {
                $vals = array_map('trim', explode(',', $gentani));
                $materialsQuery->whereIn('gentani', $vals);
            } else {
                $materialsQuery->where('gentani', $gentani);
            }
        }

        // Select only needed columns for performance
        $materials = $materialsQuery
            ->select(['id', 'material_number', 'description', 'pic_name', 'usage', 'location', 'gentani'])
            ->with(['dailyInputs' => function ($query) use ($month, $dateFilter) {
                $query->select(['id', 'material_id', 'date', 'daily_stock', 'status']);

                if ($month) {
                    $startOfMonth = Carbon::parse($month)->startOfMonth()->toDateString();
                    $endOfMonth = Carbon::parse($month)->endOfMonth()->toDateString();
                    $query->whereBetween('date', [$startOfMonth, $endOfMonth]);
                } elseif ($dateFilter) {
                    $query->where('date', '<=', $dateFilter);
                } else {
                    $query->where('date', '<=', Carbon::now()->toDateString());
                }

                $query->orderBy('date', 'asc');
            }])
            ->get();

        $recoveredMaterials = collect();

        foreach ($materials as $material) {
            $dailyInputs = $material->dailyInputs;

            if ($dailyInputs->isEmpty()) {
                continue;
            }

            $latestInput = $dailyInputs->last();

            // Only include materials that are currently OK
            if ($latestInput->status !== 'OK') {
                continue;
            }

            // Calculate recovery days
            $recoveryData = $this->calculateRecoveryDays($dailyInputs);

            if ($recoveryData && $recoveryData['recovery_days'] > 0) {
                $recoveredMaterials->push([
                    'material_number' => $material->material_number,
                    'description' => $material->description,
                    'pic' => $material->pic_name ?? '-',
                    'instock' => $latestInput->daily_stock,
                    'current_status' => $latestInput->status,
                    'recovery_days' => $recoveryData['recovery_days'],
                    'last_problem_date' => $recoveryData['last_problem_date'],
                    'recovery_date' => $recoveryData['recovery_date'],
                    'usage' => $material->usage ?? '-',
                    'location' => $material->location ?? '-',
                    'gentani' => $material->gentani ?? '-'
                ]);
            }
        }

        // Sort by recovery days descending
        $recoveredMaterials = $recoveredMaterials->sortByDesc('recovery_days')->values();

        return [
            'data' => $recoveredMaterials,
            'statistics' => [
                'total_recovered' => $recoveredMaterials->count(),
                'average_recovery_days' => round($recoveredMaterials->avg('recovery_days') ?? 0, 1),
                'fastest_recovery' => $recoveredMaterials->min('recovery_days') ?? 0,
                'slowest_recovery' => $recoveredMaterials->max('recovery_days') ?? 0,
            ],
        ];
    }

    /**
     * Calculate recovery days for a material
     * Returns: ['recovery_days' => int, 'last_problem_date' => string, 'recovery_date' => string]
     */
    private function calculateRecoveryDays($dailyInputs)
    {
        if ($dailyInputs->isEmpty()) {
            return null;
        }

        $latestInput = $dailyInputs->last();

        // Must be OK status to have recovered
        if ($latestInput->status !== 'OK') {
            return null;
        }

        $firstOKDate = null;
        $lastProblemDate = null;
        $firstProblemDate = null;
        $inProblemZone = false;

        // Loop backwards from latest
        for ($i = $dailyInputs->count() - 1; $i >= 0; $i--) {
            $currentInput = $dailyInputs[$i];
            $currentDate = Carbon::parse($currentInput->date);

            // Skip weekends (Saturday = 6, Sunday = 0)
            if ($currentDate->isWeekend()) {
                continue;
            }

            // Track OK period
            if ($currentInput->status === 'OK' && !$inProblemZone) {
                $firstOKDate = $currentDate;
                continue;
            }

            // Found problem status
            if ($currentInput->status === 'CAUTION' || $currentInput->status === 'SHORTAGE') {
                if (!$inProblemZone) {
                    $inProblemZone = true;
                    $lastProblemDate = $currentDate;
                }

                // Keep tracking back to find when problem started
                $firstProblemDate = $currentDate;
            }

            // Found the boundary (OK before problem)
            if ($inProblemZone && $currentInput->status === 'OK') {
                break;
            }
        }

        // Calculate weekdays only using Carbon's diffInWeekdays
        if ($firstProblemDate && $firstOKDate) {
            // diffInWeekdays counts weekdays between dates
            // Add 1 to include both start and end dates
            $weekdaysCount = $firstProblemDate->diffInWeekdays($firstOKDate) + 1;

            return [
                'recovery_days' => $weekdaysCount,
                'last_problem_date' => $firstProblemDate->toDateString(),
                'recovery_date' => $firstOKDate->toDateString(),
            ];
        }

        return null;
    }

    /**
     * Get recovery trend by year
     */
    public function getRecoveryTrend($year)
    {
        $trendData = collect();

        // Loop through each month (1-12)
        for ($month = 1; $month <= 12; $month++) {
            $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
            $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();

            // Only process past months
            if ($startOfMonth->isFuture()) {
                continue;
            }

            // Get recovery data for this month
            $monthData = $this->getRecoveryDays([
                'month' => $startOfMonth->format('Y-m')
            ]);

            $recoveredMaterials = collect($monthData['data']);

            $trendData->push([
                'month' => $month,
                'average_recovery_days' => round($recoveredMaterials->avg('recovery_days') ?? 0, 1),
                'total_recovered' => $recoveredMaterials->count(),
            ]);
        }

        return $trendData;
    }

    /**
     * Get frequency data of status changes for materials.
     *
     * Returns a collection of materials with counts of status transitions
     * from OK to other statuses and recoveries to OK, filtered by the given parameters.
     *
     * @param array $filters Optional filters for usage, location, month, or date.
     * @return \Illuminate\Support\Collection
     */
    public function getStatusChangeFrequency($filters = [])
    {
        $generalReport = $this->getGeneralReport($filters);
        $materials = collect($generalReport['data']);

        // Filter out materials with no changes
        $materialsWithChanges = $materials
            ->filter(function ($material) {
                // Safely check if keys exist with default values
                $totalFromOk = $material['frequency_changes']['total_from_ok'] ?? 0;
                $totalToOk = $material['frequency_changes']['total_to_ok'] ?? 0;

                return $totalFromOk > 0 || $totalToOk > 0;
            })
            ->map(function ($material) {
                // Ensure all frequency_changes keys exist with defaults
                $frequencyChanges = array_merge([
                    'ok_to_caution' => 0,
                    'ok_to_shortage' => 0,
                    'ok_to_overflow' => 0,
                    'caution_to_ok' => 0,
                    'shortage_to_ok' => 0,
                    'overflow_to_ok' => 0,
                    'total_from_ok' => 0,
                    'total_to_ok' => 0,
                ], $material['frequency_changes'] ?? []);

                return [
                    'material_number' => $material['material_number'],
                    'description' => $material['description'],
                    'pic' => $material['pic'],
                    'usage' => $material['usage'],
                    'current_status' => $material['current_status'],
                    'frequency_changes' => $frequencyChanges,
                    'gentani' => $material['gentani']
                ];
            })
            ->sortByDesc('frequency_changes.total_from_ok')
            ->values();

        return $materialsWithChanges;
    }

    /**
     * Get Overdue status
     *
     */
    public function getOverdueStatus($filters = [])
    {
        $month = $filters['month'] ?? null;
        $dateFilter = $filters['date'] ?? null;
        $usage = $filters['usage'] ?? null;
        $location = $filters['location'] ?? null;
        $gentani = $filters['gentani'] ?? null;

        $materialsQuery = Materials::query();

        // Apply usage filter
        if ($usage !== null && $usage !== '') {
            if (is_array($usage)) {
                $materialsQuery->whereIn('usage', $usage);
            } else {
                $materialsQuery->where('usage', $usage);
            }
        }

        // Apply location filter
        if ($location !== null && $location !== '') {
            if (is_array($location)) {
                $materialsQuery->whereIn('location', $location);
            } else {
                $materialsQuery->where('location', $location);
            }
        }
        if ($gentani !== null && $gentani !== '') {
            if (is_array($gentani)) {
                $materialsQuery->whereIn('gentani', $gentani);
            } elseif (strpos($gentani, ',') !== false) {
                $vals = array_map('trim', explode(',', $gentani));
                $materialsQuery->whereIn('gentani', $vals);
            } else {
                $materialsQuery->where('gentani', $gentani);
            }
        }

        $materials = $materialsQuery
            ->select(['id', 'material_number', 'description', 'pic_name', 'usage', 'location', 'gentani'])
            ->with(['dailyInputs' => function ($query) use ($month, $dateFilter) {
                $query->select(['id', 'material_id', 'date', 'daily_stock', 'status']);

                if ($month) {
                    $startOfMonth = Carbon::parse($month)->startOfMonth()->toDateString();
                    $endOfMonth = Carbon::parse($month)->endOfMonth()->toDateString();
                    $query->whereBetween('date', [$startOfMonth, $endOfMonth]);
                } elseif ($dateFilter) {
                    $query->where('date', '<=', $dateFilter);
                } else {
                    $query->where('date', '<=', Carbon::now()->toDateString());
                }

                $query->orderBy('date', 'asc');
            }])
            ->get();

        $report = collect($materials)->map(function ($material) {
            $dailyInputs = $material->dailyInputs;

            if ($dailyInputs->isEmpty()) {
                return [
                    'material_number' => $material->material_number,
                    'description' => $material->description,
                    'pic' => $material->pic_name,
                    'instock' => null,
                    'current_status' => 'N/A',
                    'days' => 0,
                    'location' => $material->location,
                    'usage' => $material->usage,
                    'gentani' => $material->gentani
                ];
            }

            $latestInput = $dailyInputs->last();
            $consecutiveDays = $this->calculateConsecutiveDays($dailyInputs, $latestInput);

            return [
                'material_number' => $material->material_number,
                'description' => $material->description,
                'pic' => $material->pic_name,
                'instock' => $latestInput->daily_stock,
                'current_status' => $latestInput->status,
                'days' => $consecutiveDays,
                'location' => $material->location,
                'usage' => $material->usage,
                'gentani' => $material->gentani

            ];
        });

        $filteredReport = $report->filter(fn($item) => $item['current_status'] !== 'OK')->values();

        return [
            'data' => $filteredReport,
        ];
    }
}
