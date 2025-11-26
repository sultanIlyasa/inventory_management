<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\MaterialReportService;
use Illuminate\Support\Facades\Cache;

class LeaderboardController extends Controller
{
    protected $reportService;

    public function __construct(MaterialReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Get all leaderboard - with caching
     */
    public function index(Request $request)
    {
        $request->validate([
            'location' => 'nullable|in:SUNTER_1,SUNTER_2',
            'usage' => 'nullable|in:DAILY,WEEKLY,MONTHLY',
            'month' => 'nullable|date_format:Y-m',
            'gentani' => 'nullable|in:GENTAN-I,NON_GENTAN-I'
        ]);

        $filters = $this->getFilters($request);
        $activeTab = $request->get('tab', 'CAUTION');

        // Create cache key based on filters
        $cacheKey = 'leaderboard_' . md5(json_encode($filters));

        // Cache for 5 minutes
        $leaderboards = Cache::remember($cacheKey, 300, function () use ($filters) {
            return $this->reportService->getAllLeaderboards($filters);
        });

        return Inertia::render('WarehouseMonitoring/Leaderboard', [
            'cautionData' => $this->paginateAndFormat($leaderboards['CAUTION'], $request, 'CAUTION'),
            'shortageData' => $this->paginateAndFormat($leaderboards['SHORTAGE'], $request, 'SHORTAGE'),
            'activeTab' => $activeTab,
            'filters' => $filters,
        ]);
    }

    /**
     * API: Caution leaderboard - for AJAX requests
     */
    public function cautionApi(Request $request)
    {
        $filters = $this->getFilters($request);
        $cacheKey = 'caution_' . md5(json_encode($filters));

        $leaderboard = Cache::remember($cacheKey, 300, function () use ($filters) {
            return $this->reportService->getCautionLeaderboard($filters);
        });

        $data = $this->paginateAndFormat($leaderboard, $request, 'CAUTION');

        return response()->json(['success' => true, ...$data]);
    }

    /**
     * API: Shortage leaderboard - for AJAX requests
     */
    public function shortageApi(Request $request)
    {
        $filters = $this->getFilters($request);
        $cacheKey = 'shortage_' . md5(json_encode($filters));

        $leaderboard = Cache::remember($cacheKey, 300, function () use ($filters) {
            return $this->reportService->getShortageLeaderboard($filters);
        });

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
            'gentani' => $request->gentani
        ];
    }

    /**
     * Helper: Paginate and format data
     */
    private function paginateAndFormat($leaderboard, Request $request, string $type)
    {
        $perPage = (int) $request->get('per_page', 10);
        $page = (int) $request->get('page', 1);
        $total = $leaderboard->count();

        // Ensure page is valid
        $lastPage = (int) ceil($total / $perPage);
        if ($page > $lastPage && $lastPage > 0) {
            $page = $lastPage;
        }

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
                'last_page' => max(1, $lastPage),
                'per_page' => $perPage,
                'total' => $total,
            ]
        ];
    }
}
