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



}
