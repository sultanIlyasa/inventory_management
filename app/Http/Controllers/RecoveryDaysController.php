<?php

namespace App\Http\Controllers;

use App\Services\MaterialReportService;
use Illuminate\Http\Request;


class RecoveryDaysController extends Controller
{
    protected $reportService;
    public function __construct(MaterialReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index(Request $request)
    {
        $request->validate([
            'location' => 'nullable|in:SUNTER_1,SUNTER_2',
            'usage' => 'nullable|in:DAILY,WEEKLY,MONTHLY',
            'month' => 'nullable|date_format:Y-m'
        ]);
        $filters = [
            'location' => $request->get('location'),
            'usage' => $request->get('usage'),
            'month' => $request->get('month'),
        ];
        return inertia('WarehouseMonitoring/RecoveryDays', [
            'filters' => $filters,
        ]);
        // Validate and process request if needed
        return response()->json([
            'message' => 'Recovery Days API endpoint'
        ]);
    }
}
