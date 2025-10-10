<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DailyInput;
use App\Models\Materials;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    // GET /api/reports/daily?date=YYYY-MM-DD
    public function daily(Request $request)
    {
        $date = $request->query('date', Carbon::today()->toDateString());

        $totalMaterials = Materials::count();
        $checked = DailyInput::whereDate('date', $date)->count();
        $unchecked = $totalMaterials - $checked;

        $statusDist = DailyInput::whereDate('date', $date)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return response()->json([
            'success' => true,
            'summary' => [
                'total_materials' => $totalMaterials,
                'checked' => $checked,
                'unchecked' => $unchecked,
                'status_distribution' => $statusDist
            ]
        ]);
    }

    // GET /api/reports/monthly?month=YYYY-MM
    public function monthly(Request $request)
    {
        $month = $request->query('month', Carbon::now()->format('Y-m'));

        $inputs = DailyInput::with('material')
            ->whereYear('date', substr($month, 0, 4))
            ->whereMonth('date', substr($month, 5, 2))
            ->get()
            ->groupBy(function ($item) {
                return $item->date->format('Y-m-d');
            });

        $report = [];
        foreach ($inputs as $date => $dailyInputs) {
            $totalMaterials = Materials::count();
            $checked = $dailyInputs->count();
            $unchecked = $totalMaterials - $checked;

            $statusDist = $dailyInputs->groupBy('status')->map->count();

            $report[] = [
                'date' => $date,
                'total_materials' => $totalMaterials,
                'checked' => $checked,
                'unchecked' => $unchecked,
                'status_distribution' => $statusDist
            ];
        }

        return response()->json([
            'success' => true,
            'report' => $report
        ]);
    }

    // GET /api/reports/material/yearly?year=YYYY
    public function yearly(Request $request)
    {
        $year = $request->query('year', Carbon::now()->format('Y'));

        $inputs = DailyInput::with('material')
            ->whereYear('date', $year)
            ->get()
            ->groupBy('material_id');

        $report = [];
        foreach ($inputs as $materialId => $materialInputs) {
            $material = $materialInputs->first()->material;
            $statusDist = $materialInputs->groupBy('status')->map->count();

            $report[] = [
                'material_id' => $materialId,
                'material_number' => $material->material_number,
                'description' => $material->description,
                'unit_of_measure' => $material->unit_of_measure,
                'status_distribution' => $statusDist
            ];
        }

        return response()->json([
            'success' => true,
            'report' => $report
        ]);
    }

    // GET /api/reports/general?date=YYYY-MM-DD (optional date filter)
    public function getGeneralReport(Request $request)
    {
        $dateFilter = $request->input('date'); // optional filter
        $materials = Materials::with(['dailyInputs' => function ($query) use ($dateFilter) {
            if ($dateFilter) {
                $query->whereDate('date', $dateFilter);
            }
            $query->orderBy('date', 'asc');
        }])->get();

        $reports = [];

        foreach ($materials as $material) {
            $dailyInputs = $material->dailyInputs;
            $statusDays = $this->calculateStatusDays($dailyInputs);

            $reports[] = [
                'material_number' => $material->material_number,
                'description' => $material->description,
                'pic' => $material->pic_name ?? '-',
                'instock_latest' => $dailyInputs->last()->daily_stock ?? 0,
                'current_status' => $dailyInputs->last()->status ?? 'UNCHECKED',
                'status_days' => $statusDays,
                'longest_critical' => $this->getLongestPeriod($dailyInputs, 'CRITICAL'),
                'longest_shortage' => $this->getLongestPeriod($dailyInputs, 'SHORTAGE'),
                'last_updated' => $dailyInputs->last()->date ?? null,
            ];
        }

        return response()->json([
            'data' => $reports
        ]);
    }

    private function calculateStatusDays($dailyInputs)
    {
        $days = [
            'OK' => 0,
            'CRITICAL' => 0,
            'SHORTAGE' => 0,
            'OVERFLOW' => 0,
        ];

        if ($dailyInputs->isEmpty()) return $days;

        $previousStatus = null;
        $currentStreak = 0;

        foreach ($dailyInputs as $index => $input) {
            if ($input->status === $previousStatus) {
                $currentStreak++;
            } else {
                if ($previousStatus) {
                    $days[$previousStatus] += $currentStreak;
                }
                $currentStreak = 1;
            }

            $previousStatus = $input->status;
        }

        // Add the last streak
        $days[$previousStatus] += $currentStreak;

        return $days;
    }

    private function getLongestPeriod($dailyInputs, $targetStatus)
    {
        $maxStreak = 0;
        $currentStreak = 0;

        foreach ($dailyInputs as $input) {
            if ($input->status === $targetStatus) {
                $currentStreak++;
                $maxStreak = max($maxStreak, $currentStreak);
            } else {
                $currentStreak = 0;
            }
        }

        return $maxStreak;
    }
    public function currentStatusReport()
    {
        $today = Carbon::now()->toDateString();
        // Load all daily inputs up to today, ordered by date
        $materials = Materials::with(['dailyInputs' => function ($query) use ($today) {
            $query->where('date', '<=', $today)
                ->orderBy('date', 'asc');
        }])->get();

        $report = $materials->map(function ($material) {
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

        return response()->json($report);
    }

    /**
     * Calculate consecutive days with same status
     */
    private function calculateConsecutiveDays($dailyInputs, $latestInput)
    {
        if (!$latestInput) {
            return 0;
        }

        $currentStatus = $latestInput->status;
        $firstMatchDate = null;
        $lastMatchDate = null;

        // Loop backwards from latest to oldest
        for ($i = $dailyInputs->count() - 1; $i >= 0; $i--) {
            $currentInput = $dailyInputs[$i];

            if ($currentInput->status === $currentStatus) {
                $currentDate = Carbon::parse($currentInput->date);

                if ($lastMatchDate === null) {
                    $lastMatchDate = $currentDate;
                }

                $firstMatchDate = $currentDate;
            } else {
                break;
            }
        }

        if ($firstMatchDate && $lastMatchDate) {
            return $firstMatchDate->diffInDays($lastMatchDate) + 1;
        }

        return 0;
    }
}
