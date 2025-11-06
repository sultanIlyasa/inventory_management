<?php

namespace App\Services;

use App\Models\Materials;
use Illuminate\Support\Collection;
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
                $materialsQuery->whereIn('location', $location);
            } else {
                $materialsQuery->where('location', $location);
            }
        }

        $materials = $materialsQuery->with(['dailyInputs' => function ($query) use ($month, $dateFilter) {
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
     * Calculate frequency of status changes from OK
     */
    public function calculateFrequencyChanges($dailyInputs)
    {
        $changes = [
            'ok_to_caution' => 0,
            'ok_to_shortage' => 0,
            'ok_to_overflow' => 0,
            'total_from_ok' => 0,
        ];

        for ($i = 1; $i < $dailyInputs->count(); $i++) {
            $previous = $dailyInputs[$i - 1];
            $current = $dailyInputs[$i];

            if ($previous->status === 'OK' && $current->status !== 'OK') {
                $changes['total_from_ok']++;

                if ($current->status === 'CAUTION') {
                    $changes['ok_to_caution']++;
                } elseif ($current->status === 'SHORTAGE') {
                    $changes['ok_to_shortage']++;
                } elseif ($current->status === 'OVERFLOW') {
                    $changes['ok_to_overflow']++;
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
     * Get recovery days report
     */
    public function getRecoveryDays($filters = [])
    {
        $generalReport = $this->getGeneralReport($filters);
        $materials = collect($generalReport['data']);

        $recoveredMaterials = $materials
            ->filter(function ($material) {
                return $material['current_status'] === 'OK'
                    && $material['consecutive_days'] > 0;
            })
            ->map(function ($material) use ($filters) {
                return [
                    'material_number' => $material['material_number'],
                    'description' => $material['description'],
                    'pic' => $material['pic'],
                    'instock' => $material['instock_latest'],
                    'current_status' => $material['current_status'],
                    'recovery_days' => $material['consecutive_days'],
                    'last_problem_date' => $this->getLastProblemDate($material),
                    'recovery_date' => $material['last_updated'],
                ];
            })
            ->values();

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
                'total_from_ok' => 0,
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
}
