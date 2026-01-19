<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DailyInput;
use App\Models\Materials;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DailyInputExport;
use PDO;
use PhpParser\Node\Stmt\TryCatch;

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

        $dailyInputsQuery = DailyInput::with(['material', 'material.discrepancyMaterial'])
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

        $missing = Materials::with('discrepancyMaterial')
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

    public function export(Request $request)
    {
        $date = $request->query('date', Carbon::today()->toDateString());
        $usage = $request->query('usage');
        $location = $request->query('location');

        $fileName = 'Inventory-' .
            now()
            ->locale('id')
            ->translatedFormat('d-F-Y_H.i') .
            '_WIB.xlsx';
        return Excel::download(new DailyInputExport($date, $usage, $location), $fileName);
    }

    // sync daily-input status with updated min-max stock
    public function syncDailyInputStatus()
    {
        try {
            DB::beginTransaction();

            // 1. Get all DailyInputs with their parent Material
            // Using chunk() is safer for memory if you have thousands of records
            $updatedCount = 0;

            DailyInput::with('material')->chunk(100, function ($inputs) use (&$updatedCount) {
                foreach ($inputs as $input) {
                    // If material is deleted or missing, skip
                    if (!$input->material) continue;

                    $stock = $input->daily_stock;
                    $min = $input->material->stock_minimum;
                    $max = $input->material->stock_maximum;

                    // 2. Re-evaluate logic based on CURRENT material thresholds
                    $newStatus = 'OK';
                    if ($stock == 0) {
                        $newStatus = 'SHORTAGE';
                    } elseif ($stock < $min) {
                        $newStatus = 'CAUTION';
                    } elseif ($stock > $max) {
                        $newStatus = 'OVERFLOW';
                    }

                    // 3. Update only if status has changed
                    if ($input->status !== $newStatus) {
                        $input->status = $newStatus;
                        $input->save();
                        $updatedCount++;
                    }
                }
            });

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Daily input statuses synchronized successfully',
                'data' => [
                    'updated_records' => $updatedCount,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
