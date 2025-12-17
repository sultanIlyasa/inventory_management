<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DailyInput;
use App\Models\Materials;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DailyInputController extends Controller
{
    // GET /api/daily-input?date=YYYY-MM-DD
    public function index(Request $request)
    {
        $date = $request->query('date', Carbon::today()->toDateString());

        $inputs = DailyInput::with('material')
            ->whereDate('date', $date)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $inputs
        ]);
    }

    // POST /api/daily-input
    public function store(Request $request)
    {
        $validated = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'date' => 'required|date',
            'daily_stock' => 'required|integer|min:0'
        ]);

        $material = Materials::findOrFail($validated['material_id']);

        $dailyStock = max(0, $validated['daily_stock']); // prevent negative numbers

        if ($dailyStock === 0) {
            $status = 'SHORTAGE';
        } elseif ($dailyStock < $material->stock_minimum) {
            $status = 'CAUTION';
        } elseif ($dailyStock > $material->stock_maximum) {
            $status = 'OVERFLOW';
        } else {
            $status = 'OK';
        }
        if ($validated['date'] == Carbon::today()->toDateString()) {
            // Check if an entry already exists for today
            $existing = DailyInput::where('material_id', $validated['material_id'])
                ->whereDate('date', $validated['date'])
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Daily input for this material already recorded today.'
                ], 409);
            }
        }

        $dailyInput = DailyInput::create([
            ...$validated,
            'status' => $status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Daily input recorded successfully',
            'data' => $dailyInput
        ], 201);
    }

    // GET /api/daily-input/missing?date=YYYY-MM-DD
    public function missing(Request $request)
    {
        $date = $request->query('date', Carbon::today()->toDateString());
        $checked = DailyInput::whereDate('date', $date)->pluck('material_id')->toArray();
        $missing = Materials::whereNotIn('id', $checked)->get();

        return response()->json([
            'success' => true,
            'message' => "Materials pending check for $date",
            'data' => $missing
        ]);
    }


    // GET /api/daily-input/status?date=YYYY-MM-DD
    public function dailyStatus(Request $request)
    {
        $date = $request->query('date', Carbon::today()->toDateString());
        $usage = $request->query('usage');
        $usage = $usage ? strtoupper($usage) : null;
        $location = $request->query('location');

        $carbonDate = Carbon::parse($date)->locale('id');

        if ($usage === 'WEEKLY') {
            $start = $carbonDate->copy()->startOfWeek();
            $end   = $carbonDate->copy()->endOfWeek();
        } elseif ($usage === 'MONTHLY') {
            $start = $carbonDate->copy()->startOfMonth();
            $end   = $carbonDate->copy()->endOfMonth();
        } else {
            $start = $carbonDate->copy()->startOfDay();
            $end   = $carbonDate->copy()->endOfDay();
        }

        $dailyInputsQuery = DailyInput::with('material')
            ->whereBetween('date', [$start, $end])
            ->when($usage, function ($query) use ($usage) {
                $query->whereHas(
                    'material',
                    fn($q) =>
                    $q->where('usage', $usage)
                );
            })
            ->when(!empty($location), function ($query) use ($location) {
                $locations = is_array($location)
                    ? $location
                    : array_map('trim', explode(',', $location));
                $query->whereHas(
                    'material',
                    fn($q) =>
                    $q->whereIn('location', $locations)
                );
            });

        $checked = $dailyInputsQuery->get();
        $checkedIds = $checked->pluck('material_id')->toArray();

        $missing = Materials::query()
            ->when($usage, fn($q) => $q->where('usage', $usage))
            ->when(!empty($location), function ($query) use ($location) {
                $locations = is_array($location)
                    ? $location
                    : array_map('trim', explode(',', $location));
                $query->whereIn('location', $locations);
            })
            ->whereNotIn('id', $checkedIds)
            ->get();

        return response()->json([
            'success' => true,
            'usage_mode' => $usage ?? 'ALL',
            'date_range' => [
                'start' => $start->toDateString(),
                'end'   => $end->toDateString(),
            ],
            'checked' => $checked,
            'missing' => $missing,
        ]);
    }


    //delete /api/daily-input/{id}
    public function destroy($id)
    {
        $dailyInput = DailyInput::findOrFail($id);
        $dailyInput->delete();
        return response()->json([
            'success' => true,
            'message' => 'Daily input deleted successfully'
        ]);
    }

    public function weeklyStatus(Request $request)
    {
        $week = $request->query('date', Carbon::now()->toDateString());
        $endOfWeek = Carbon::parse($week)->endOfWeek()->toDateString();
        $startOfWeek = Carbon::parse($week)->startOfWeek()->toDateString();
        $location = $request->query('location');

        // Build query with optional filters
        $dailyInputsQuery = DailyInput::with('material')
            ->whereBetween('date', [$startOfWeek, $endOfWeek])
            ->whereHas('material', function ($query) {
                $query->where('usage', 'WEEKLY');
            });
        // Filter by usage (supports single, comma-separated, or array)
        if (!empty($usage)) {
            $usages = is_array($usage) ? $usage : array_map('trim', explode(',', $usage));
            $dailyInputsQuery->whereHas('material', function ($query) use ($usages) {
                $query->whereIn('usage', $usages);
            });
        }

        // Filter by location (supports single, comma-separated, or array)
        if (!empty($location)) {
            $locations = is_array($location) ? $location : array_map('trim', explode(',', $location));
            $dailyInputsQuery->whereHas('material', function ($query) use ($locations) {
                $query->whereIn('location', $locations);
            });
        }

        // Get checked materials after applying filters
        $checked = $dailyInputsQuery->get();

        // Extract IDs of checked materials
        $checkedIds = $checked->pluck('material_id')->toArray();

        // Get missing materials (apply same filters)
        $missing = Materials::query()
            ->where('usage', 'WEEKLY')
            ->when(!empty($location), function ($query) use ($location) {
                $locations = is_array($location)
                    ? $location
                    : array_map('trim', explode(',', $location));
                $query->whereIn('location', $locations);
            })
            ->whereNotIn('id', $checkedIds)
            ->get();

        return response()->json([
            'success' => true,
            'start_week' => $startOfWeek,
            'end_week' => $endOfWeek,
            'filters' => [
                'location' => $location,
            ],
            'checked' => $checked,
            'missing' => $missing,
        ]);
    }
}
