<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DailyInput;
use App\Models\Materials;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function getCautionLeaderboard(Request $request)
    {
        // 1. Get current time, put inside $today variable
        $today = Carbon::now()->toDayDateTimeString();
        //2. Fetch all the reports and put it on the $generalReport variable
        $generalReport = $this->getGeneralReport($request)->getData(true);
        // 3. Checks whether $generalReport is an array,If $generalReport['data'] exists and is not null, use it — otherwise, use an empty array.
        $materials = is_array($generalReport) ? ($generalReport['data'] ?? []) : [];
        // 4. Wraps the materials in collection, use filter to filterout currect status as caution and map to a new collection and then sort descending
        $leaderboard = collect($materials)
            ->filter(fn($m) => isset($m['current_status']) && $m['current_status'] === 'CAUTION')
            ->map(fn($m) => [
                'material_number' => $m['material_number'] ?? null,
                'description' => $m['description'] ?? null,
                'pic' => $m['pic'] ?? '-',
                'current_status' => $m['current_status'] ?? null,
                'current_stock' => $m['instock_latest'] ?? ($m['instock'] ?? null),
                'days' => $m['consecutive_days'] ?? 0,
            ])
            ->sortByDesc('days')
            // Returns a clean, zero-based array suitable for JSON responses or rendering.
            ->values();


        // Gets the per_page query parameter from the request (for example: /api/leaderboard?per_page=20).
        $perPage = (int) $request->get('per_page', 10);
        // Gets the current page query parameter (for example: /api/leaderboard?page=3).
        $page = (int) $request->get('page', 1);
        $total = $leaderboard->count();
        // It returns only the slice of items that belong to the given page.
        $paged = $leaderboard->forPage($page, $perPage)->values();

        $stats = [
            'total_caution' => $total,
            'average_days' => round($leaderboard->avg('days') ?? 0, 1),
            'max_days' => $leaderboard->max('days') ?? 0,
            'min_days' => $leaderboard->min('days') ?? 0,
            'today' => $today

        ];

        $data = [
            'statistics' => $stats,
            'leaderboard' => $paged,
            'pagination' => [
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage),
                'per_page' => $perPage,
                'total' => $total,
            ]
        ];

        if ($request->header('X-Inertia')) {
            return Inertia::render('CautionOverdueLeaderboard', $data);
        }

        return response()->json([
            'success' => true,
            ...$data
        ]);
    }


    // GET /api/reports/shortage-leaderboard?top=N&date=YYYY-MM-DD (optional date filter)
    public function getShortageLeaderboard(Request $request)
    {
        $materials = $this->getGeneralReport($request)->getData(true);
        $today = Carbon::now()->toDayDateTimeString();
        $generalReport = $this->getGeneralReport($request)->getData(true);
        $materials = is_array($generalReport) ? ($generalReport['data'] ?? []) : [];
        $leaderboard = collect($materials)
            ->filter(fn($m) => isset($m['current_status']) && $m['current_status'] === 'SHORTAGE')
            ->map(fn($m) => [
                'material_number' => $m['material_number'] ?? null,
                'description' => $m['description'] ?? null,
                'pic' => $m['pic'] ?? '-',
                'current_status' => $m['current_status'] ?? null,
                'current_stock' => $m['instock_latest'] ?? ($m['instock'] ?? null),
                'days' => $m['consecutive_days'] ?? 0,
            ])
            ->sortByDesc('days')
            ->values();


        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        $total = $leaderboard->count();
        $paged = $leaderboard->forPage($page, $perPage)->values();

        $stats = [
            'total_shortage' => $leaderboard->count(),
            'average_days' => round($leaderboard->avg('days') ?? 0, 1),
            'max_days' => $leaderboard->max('days') ?? 0,
            'min_days' => $leaderboard->min('days') ?? 0,
            'today' => $today

        ];

        return response()->json([
            'success' => true,
            'statistics' => $stats,
            'data' => $paged,
            'current_page' => (int) $page,
            'last_page' => ceil($total / $perPage),
            'per_page' => (int) $perPage,
            'total' => $total,

        ]);
    }


    public function getGeneralReport(Request $request)
    {
        $month = $request->input('month');
        $dateFilter = $request->input('date');
        $usage = $request->input('usage');

        // Start with Materials query
        $materialsQuery = Materials::query();

        // Apply usage filter on materials table
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

        // Load materials with filtered daily inputs
        $materials = $materialsQuery->with(['dailyInputs' => function ($query) use ($month, $dateFilter) {
            // Date filtering logic
            if ($month) {
                // If month is provided, filter by entire month
                $startOfMonth = Carbon::parse($month)->startOfMonth()->toDateString();
                $endOfMonth = Carbon::parse($month)->endOfMonth()->toDateString();
                $query->whereBetween('date', [$startOfMonth, $endOfMonth]);
            } elseif ($dateFilter) {
                // If specific date is provided, filter up to that date
                $query->where('date', '<=', $dateFilter);
            } else {
                // Default: up to today
                $query->where('date', '<=', Carbon::now()->toDateString());
            }

            $query->orderBy('date', 'asc');
        }])->get();

        $reports = [];

        foreach ($materials as $material) {
            $dailyInputs = $material->dailyInputs;

            if ($dailyInputs->isEmpty()) {
                $reports[] = [
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

        return response()->json([
            'success' => true,
            'month' => $month ?: 'All time',
            'date' => $dateFilter,
            'usage' => $usage ?: 'All',
            'total_materials' => count($reports),
            'data' => $reports
        ]);
    }

    private function calculateStatusDays($dailyInputs)
    {
        $days = [
            'OK' => 0,
            'CAUTION' => 0,
            'SHORTAGE' => 0,
            'OVERFLOW' => 0,
            'UNCHECKED' => 0
        ];

        if ($dailyInputs->isEmpty()) {
            return $days;
        }

        $previousDate = null;
        $previousStatus = null;

        foreach ($dailyInputs as $input) {
            $currentDate = Carbon::parse($input->date);

            if ($previousDate && $previousStatus) {
                // Calculate the number of days between previous and current
                $daysDiff = $previousDate->diffInDays($currentDate);

                // If there's a gap, add those days to the previous status
                if ($daysDiff > 1) {
                    $days[$previousStatus] += ($daysDiff - 1);
                }
            }

            // Always count the current day
            $days[$input->status] += 1;

            $previousDate = $currentDate;
            $previousStatus = $input->status;
        }

        return $days;
    }

    private function getLongestPeriod($dailyInputs, $targetStatus)
    {
        $maxStreak = 0;
        $currentStreak = 0;
        $previousDate = null;

        foreach ($dailyInputs as $input) {
            $currentDate = Carbon::parse($input->date);

            if ($input->status === $targetStatus) {
                if ($previousDate) {
                    // Check if dates are consecutive
                    $daysDiff = $previousDate->diffInDays($currentDate);
                    if ($daysDiff === 1) {
                        // Consecutive day with same status
                        $currentStreak++;
                    } else {
                        // Gap in dates, restart streak
                        $currentStreak = 1;
                    }
                } else {
                    // First occurrence
                    $currentStreak = 1;
                }

                $maxStreak = max($maxStreak, $currentStreak);
                $previousDate = $currentDate;
            } else {
                // Different status, reset streak
                $currentStreak = 0;
                $previousDate = null;
            }
        }

        return $maxStreak;
    }

    // GET /api/reports/general?date=YYYY-MM-DD (optional date filter)

    // public function currentStatusReport(Request $request)
    // {
    //     $dateFilter = $request->input('date', Carbon::now()->toDateString());
    //     // Load all daily inputs up to today, ordered by date
    //     $materials = Materials::with(['dailyInputs' => function ($query) use ($dateFilter) {
    //         $query->where('date', '<=', $dateFilter)
    //             ->orderBy('date', 'asc');
    //     }])->get();


    //     $report = $materials->map(function ($material) {
    //         $dailyInputs = $material->dailyInputs;

    //         if ($dailyInputs->isEmpty()) {
    //             return [
    //                 'material_number' => $material->material_number,
    //                 'description' => $material->description,
    //                 'pic' => $material->pic_name,
    //                 'instock' => null,
    //                 'current_status' => 'N/A',
    //                 'days' => 0,
    //             ];
    //         }

    //         $latestInput = $dailyInputs->last();
    //         $consecutiveDays = $this->calculateConsecutiveDays($dailyInputs, $latestInput);

    //         return [
    //             'material_number' => $material->material_number,
    //             'description' => $material->description,
    //             'pic' => $material->pic_name,
    //             'instock' => $latestInput->daily_stock,
    //             'current_status' => $latestInput->status,
    //             'days' => $consecutiveDays,
    //         ];
    //     });

    //     return response()->json($report);
    // }

    public function getOverdueStatus(Request $request)
    {
        $month = $request->input('month');
        $dateFilter = $request->input('date', Carbon::now()->toDateString());
        $usage = $request->input('usage');
        $materials = Materials::with(['dailyInputs' => function ($query) use ($month, $dateFilter, $usage) {
            // Apply usage filter if provided
            if ($usage !== null && $usage !== '') {
                if (is_array($usage)) {
                    $query->whereIn('usage', $usage);
                } elseif (strpos($usage, ',') !== false) {
                    $vals = array_map('trim', explode(',', $usage));
                    $query->whereIn('usage', $vals);
                } else {
                    $query->where('usage', $usage);
                }
            }

            // Date filtering logic
            if ($month) {
                // If month is provided, filter by entire month
                $startOfMonth = Carbon::parse($month)->startOfMonth()->toDateString();
                $endOfMonth = Carbon::parse($month)->endOfMonth()->toDateString();
                $query->whereBetween('date', [$startOfMonth, $endOfMonth]);
            } elseif ($dateFilter) {
                // If specific date is provided, filter up to that date
                $query->where('date', '<=', $dateFilter);
            } else {
                // Default: up to today
                $query->where('date', '<=', Carbon::now()->toDateString());
            }

            $query->orderBy('date', 'asc');
        }])->get();

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
            ];
        });

        $filteredReport = $report->filter(fn($item) => $item['current_status'] !== 'OK')->values();

        return response()->json($filteredReport);
    }

    /**
     * Calculate consecutive days with same status
     */
    private function calculateConsecutiveDays($dailyInputs, $latestInput)
    {
        if (!$latestInput || $dailyInputs->isEmpty()) {
            return 0;
        }

        $currentStatus = $latestInput->status;
        $consecutiveWeekdays = 0;
        $previousDate = null;

        // Loop backwards from latest to oldest
        for ($i = $dailyInputs->count() - 1; $i >= 0; $i--) {
            $currentInput = $dailyInputs[$i];

            if ($currentInput->status === $currentStatus) {
                $currentDate = Carbon::parse($currentInput->date);

                // Skip weekends (Saturday = 6, Sunday = 0)
                if ($currentDate->isWeekend()) {
                    continue;
                }

                if ($previousDate === null) {
                    // First weekday found
                    $consecutiveWeekdays = 1;
                    $previousDate = $currentDate;
                } else {
                    // Check if dates are consecutive weekdays
                    $expectedPreviousDate = $currentDate->copy()->addWeekday();

                    // If the next expected weekday matches our previous date, it's consecutive
                    if ($expectedPreviousDate->isSameDay($previousDate)) {
                        $consecutiveWeekdays++;
                        $previousDate = $currentDate;
                    } else {
                        // Gap found - stop counting
                        break;
                    }
                }
            } else {
                // Status changed - stop counting
                break;
            }
        }

        return $consecutiveWeekdays;
    }

    // Recovery Days Component
    public function getRecoveryDays(Request $request)
    {
        $month = $request->input('month'); // e.g. '2025-10'
        $dateFilter = $request->input('date', Carbon::now()->toDateString());
        $usage = $request->input('usage');

        $materialsQuery = Materials::query();
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

        $materials = $materialsQuery->with(['dailyInputs' => function ($query) use ($month, $dateFilter) {

            if ($month) {
                // Get all inputs within the month
                $startOfMonth = Carbon::parse($month)->startOfMonth()->toDateString();
                $endOfMonth = Carbon::parse($month)->endOfMonth()->toDateString();
                $query->whereBetween('date', [$startOfMonth, $endOfMonth])
                    ->orderBy('date', 'asc');
            } elseif ($dateFilter) {
                $query->where('date', '<=', $dateFilter);
            } else {
                // Default: up to given date
                $query->where('date', '<=', $dateFilter)->orderBy('date', 'asc');
            }
        }])->get();

        $report = $materials->map(function ($material) {
            $dailyInputs = $material->dailyInputs;

            if ($dailyInputs->isEmpty()) {
                return null; // Skip materials with no data
            }

            $latestInput = $dailyInputs->last();
            $recoveryDays = $this->calculateRecoveryDays($dailyInputs, $latestInput);

            if ($latestInput->status !== 'OK' || $recoveryDays === null || $recoveryDays === 0) {
                return null;
            }

            return [
                'material_number' => $material->material_number,
                'description' => $material->description,
                'pic' => $material->pic_name,
                'instock' => $latestInput->daily_stock,
                'current_status' => $latestInput->status,
                'recovery_days' => $recoveryDays,
                'last_problem_date' => $this->getLastProblemDate($dailyInputs),
                'recovery_date' => $latestInput->date,
            ];
        })->filter()->values(); // Remove nulls and re-index

        // Calculate monthly or general statistics
        $stats = [
            'total_recovered' => $report->count(),
            'average_recovery_days' => $report->avg('recovery_days')
                ? round($report->avg('recovery_days'), 1)
                : 0,
            'fastest_recovery' => $report->min('recovery_days') ?? 0,
            'slowest_recovery' => $report->max('recovery_days') ?? 0,
        ];

        return response()->json([
            'success' => true,
            'statistics' => $stats,
            'month' => $month ?: 'All time',
            'usage' => $usage ?: 'All',
            'data' => $report
        ]);
    }

    // Helper to get last problem date
    private function getLastProblemDate($dailyInputs)
    {
        for ($i = $dailyInputs->count() - 1; $i >= 0; $i--) {
            $input = $dailyInputs[$i];
            if ($input->status === 'CAUTION' || $input->status === 'SHORTAGE') {
                return $input->date;
            }
        }
        return null;
    }

    private function calculateRecoveryDays($dailyInputs, $latestInput)
    {
        if (!$latestInput || $latestInput->status !== 'OK') {
            return null;
        }

        $firstOKDate = null;
        $lastProblemDate = null;
        $firstProblemDate = null;
        $inProblemZone = false;

        // Loop backwards
        for ($i = $dailyInputs->count() - 1; $i >= 0; $i--) {
            $currentInput = $dailyInputs[$i];
            $currentDate = Carbon::parse($currentInput->date);

            // Track OK period
            if ($currentInput->status === 'OK' && !$inProblemZone) {
                $firstOKDate = $currentDate;
                continue;
            }

            // Found problem
            if (($currentInput->status === 'CAUTION' || $currentInput->status === 'SHORTAGE')) {
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

        // Return total calendar days from problem start to first OK day
        if ($firstProblemDate && $firstOKDate) {
            return $firstProblemDate->diffInDays($firstOKDate) + 1;
        }

        return 0;
    }

    public function getRecoveryTrend(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);

        // We'll store average recovery days per month
        $trendData = collect();

        // Loop through each month (1–12)
        for ($month = 1; $month <= 12; $month++) {
            $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
            $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();

            // Load materials with their daily inputs for this month
            $materials = Materials::with(['dailyInputs' => function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                    ->orderBy('date', 'asc');
            }])->get();

            $monthlyReports = $materials->map(function ($material) {
                $dailyInputs = $material->dailyInputs;

                if ($dailyInputs->isEmpty()) return null;

                $latestInput = $dailyInputs->last();
                $recoveryDays = $this->calculateRecoveryDays($dailyInputs, $latestInput);

                // Only include materials that have recovered
                if ($latestInput->status !== 'OK' || !$recoveryDays || $recoveryDays <= 0) {
                    return null;
                }

                return [
                    'material_number' => $material->material_number,
                    'recovery_days' => $recoveryDays,
                ];
            })->filter()->values();

            // Aggregate stats
            $averageRecovery = $monthlyReports->avg('recovery_days') ?? 0;
            $totalRecovered = $monthlyReports->count();

            $trendData->push([
                'month' => $month,
                'average_recovery_days' => round($averageRecovery, 1),
                'total_recovered' => $totalRecovered,
            ]);
        }

        return response()->json([
            'success' => true,
            'year' => (int) $year,
            'data' => $trendData,
        ]);
    }

    /**
     * Calculate frequency of status changes from OK to problematic states
     */
    private function calculateFrequencyChanges($dailyInputs)
    {
        $changes = [
            'ok_to_caution' => 0,
            'ok_to_shortage' => 0,
            'ok_to_overflow' => 0,
            'caution_to_ok' => 0,
            'shortage_to_ok' => 0,
            'total_from_ok' => 0,
            'total_to_ok' => 0,
        ];

        $previousStatus = null;

        foreach ($dailyInputs as $input) {
            $currentStatus = $input->status;

            if ($previousStatus !== null && $previousStatus !== $currentStatus) {
                // Count transitions FROM OK
                if ($previousStatus === 'OK') {
                    if ($currentStatus === 'CAUTION') {
                        $changes['ok_to_caution']++;
                        $changes['total_from_ok']++;
                    } elseif ($currentStatus === 'SHORTAGE') {
                        $changes['ok_to_shortage']++;
                        $changes['total_from_ok']++;
                    } elseif ($currentStatus === 'OVERFLOW') {
                        $changes['ok_to_overflow']++;
                        $changes['total_from_ok']++;
                    }
                }

                // Count transitions TO OK (recoveries)
                if ($currentStatus === 'OK') {
                    if ($previousStatus === 'CAUTION') {
                        $changes['caution_to_ok']++;
                        $changes['total_to_ok']++;
                    } elseif ($previousStatus === 'SHORTAGE') {
                        $changes['shortage_to_ok']++;
                        $changes['total_to_ok']++;
                    }
                }
            }

            $previousStatus = $currentStatus;
        }

        return $changes;
    }
}
