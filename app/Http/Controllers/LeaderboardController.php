<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\MaterialReportService;

class LeaderboardController extends Controller
{
    protected $reportService;

    public function __construct(MaterialReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    //Get all leaderboard
    public function index(Request $request)
    {
        $request->validate([
            'location' => 'nullable|in:SUNTER_1,SUNTER_2',
            'usage' => 'nullable|in:DAILY,WEEKLY,MONTHLY',
            'month' => 'nullable|date_format:Y-m'
        ]);
        $filters = $this->getFilters($request);
        $allLeaderboards = $this->reportService->getAllLeaderboards($filters);

        return Inertia::render('WarehouseMonitoring/Leaderboard', [
            'cautionData' => $this->paginateAndFormat($allLeaderboards['CAUTION'], $request, 'CAUTION'),
            'shortageData' => $this->paginateAndFormat($allLeaderboards['SHORTAGE'], $request, 'SHORTAGE'),
            'activeTab' => $request->get('tab', 'CAUTION'),
            'filters' => $filters,
        ]);
    }

    /**
     * Dedicated caution leaderboard page
     */
    public function caution(Request $request)
    {
        $filters = $this->getFilters($request);
        $leaderboard = $this->reportService->getCautionLeaderboard($filters);
        $data = $this->paginateAndFormat($leaderboard, $request, 'CAUTION');

        return Inertia::render('Leaderboard/Caution', [
            'initialLeaderboard' => $data['leaderboard'],
            'initialStatistics' => $data['statistics'],
            'initialPagination' => $data['pagination'],
            'filters' => $filters,
        ]);
    }

    /**
     * Dedicated shortage leaderboard page
     */
    public function shortage(Request $request)
    {
        $filters = $this->getFilters($request);
        $leaderboard = $this->reportService->getShortageLeaderboard($filters);
        $data = $this->paginateAndFormat($leaderboard, $request, 'SHORTAGE');

        return Inertia::render('Leaderboard/Shortage', [
            'initialLeaderboard' => $data['leaderboard'],
            'initialStatistics' => $data['statistics'],
            'initialPagination' => $data['pagination'],
            'filters' => $filters,
        ]);
    }

    /**
     * API: Caution leaderboard
     */
    public function cautionApi(Request $request)
    {
        $filters = $this->getFilters($request);
        $leaderboard = $this->reportService->getCautionLeaderboard($filters);
        $data = $this->paginateAndFormat($leaderboard, $request, 'CAUTION');

        return response()->json(['success' => true, ...$data]);
    }

    /**
     * API: Shortage leaderboard
     */
    public function shortageApi(Request $request)
    {
        $filters = $this->getFilters($request);
        $leaderboard = $this->reportService->getShortageLeaderboard($filters);
        $data = $this->paginateAndFormat($leaderboard, $request, 'SHORTAGE');

        return response()->json(['success' => true, ...$data]);
    }


    /**
     * Helper: Get filters from request
     */
    private function getFilters(Request $request): array
    {
        return [
            'date' => $request->date,
            'month' => $request->month,
            'usage' => $request->usage,
            'location' => $request->location,
        ];
    }

    private function paginateAndFormat($leaderboard, Request $request, string $type)
    {
        $perPage = (int) $request->get('per_page', 10);
        $page = (int) $request->get('page', 1);
        $total = $leaderboard->count();
        $paged = $leaderboard->forPage($page, $perPage)->values();

        return [
            'statistics' => [
                'total' => $total,
                'average_days' => round($leaderboard->avg('days') ?? 0, 1),
                'max_days' => $leaderboard->max('days') ?? 0,
                'min_days' => $leaderboard->min('days') ?? 0,
                'type' => $type,
            ],
            'leaderboard' => $paged,
            'pagination' => [
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage),
                'per_page' => $perPage,
                'total' => $total,
            ]
        ];
    }
}
