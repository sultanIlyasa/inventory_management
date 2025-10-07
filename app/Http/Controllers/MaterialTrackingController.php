<?php

// app/Http/Controllers/MaterialTrackingController.php
namespace App\Http\Controllers;

use App\Models\MaterialTracking;
use App\Models\Material;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MaterialTrackingController extends Controller
{
    // GET /api/tracking/active
    public function active()
    {
        $active = MaterialTracking::whereNull('ended_at')
            ->with('material')
            ->get()
            ->map(function ($tracking) {
                $tracking->duration_minutes = Carbon::parse($tracking->started_at)->diffInMinutes(Carbon::now());
                return $tracking;
            });

        return response()->json([
            'success' => true,
            'message' => 'Active shortage/critical materials',
            'data' => $active
        ]);
    }

    // GET /api/tracking/history?material_id=1
    public function history(Request $request)
    {
        $materialId = $request->query('material_id');

        $query = MaterialTracking::with('material')->orderBy('started_at', 'desc');
        if ($materialId) {
            $query->where('material_id', $materialId);
        }

        return response()->json([
            'success' => true,
            'message' => 'Material tracking history',
            'data' => $query->get()
        ]);
    }

    // POST /api/tracking/start
    public function start(Request $request)
    {
        $validated = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'status' => 'required|in:shortage,critical'
        ]);

        // End previous active if exists
        MaterialTracking::where('material_id', $validated['material_id'])
            ->whereNull('ended_at')
            ->update(['ended_at' => Carbon::now()]);

        $tracking = MaterialTracking::create([
            'material_id' => $validated['material_id'],
            'status' => $validated['status'],
            'started_at' => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tracking started',
            'data' => $tracking
        ]);
    }

    // POST /api/tracking/end
    public function end(Request $request)
    {
        $validated = $request->validate([
            'material_id' => 'required|exists:materials,id',
        ]);

        $tracking = MaterialTracking::where('material_id', $validated['material_id'])
            ->whereNull('ended_at')
            ->first();

        if ($tracking) {
            $tracking->update(['ended_at' => Carbon::now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tracking ended',
            'data' => $tracking
        ]);
    }
}
