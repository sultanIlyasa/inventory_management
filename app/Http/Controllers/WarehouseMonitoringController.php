<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Services\MaterialReportService;
use Illuminate\Support\Facades\Cache;

class WarehouseMonitoringController extends Controller
{
    protected $reportService;

    public function __construct(MaterialReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Main warehouse monitoring page - fetches all data at once
     */
    public function index(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
            'location' => 'nullable|in:SUNTER_1,SUNTER_2',
            'usage' => 'nullable|in:DAILY,WEEKLY,MONTHLY',
            'month' => 'nullable|date_format:Y-m',
            'gentani' => 'nullable|in:GENTAN-I,NON_GENTAN-I'
        ]);

        $filters = $this->getFilters($request);
        $cacheKey = 'warehouse_monitoring_' . md5(json_encode($filters));

        // Cache all dashboard data for 5 minutes
        $dashboardData = Cache::remember($cacheKey, 300, function () use ($filters, $request) {
            return $this->fetchAllDashboardData($filters, $request);
        });

        return Inertia::render('WarehouseMonitoring/index', [
            'cautionData' => $dashboardData['caution'],
            'shortageData' => $dashboardData['shortage'],
            'recoveryData' => $dashboardData['recovery'],
            'statusChangeData' => $dashboardData['statusChange'],
            'barChartData' => $dashboardData['barChart'],
            'filters' => $filters,
        ]);
    }

    /**
     * API endpoint - fetch all dashboard data at once
     */
    public function dashboardApi(Request $request)
    {
        $request->validate([
            'location' => 'nullable|in:SUNTER_1,SUNTER_2',
            'usage' => 'nullable|in:DAILY,WEEKLY,MONTHLY',
            'month' => 'nullable|date_format:Y-m',
            'gentani' => 'nullable|in:GENTAN-I,NON_GENTAN-I'
        ]);

        $filters = $this->getFilters($request);
        $cacheKey = 'warehouse_monitoring_api_' . md5(json_encode($filters));

        $dashboardData = Cache::remember($cacheKey, 300, function () use ($filters, $request) {
            return $this->fetchAllDashboardData($filters, $request);
        });

        return response()->json([
            'success' => true,
            'data' => $dashboardData,
            'filters' => $filters,
            'timestamp' => now()->toIso8601String()
        ]);
    }

    /**
     * Fetch all dashboard data - Caution, Shortage, and Recovery
     */
    private function fetchAllDashboardData($filters, $request)
    {
        $perPage = (int) $request->get('per_page', 5);

        return [
            'caution' => $this->getCautionData($filters, $perPage),
            'shortage' => $this->getShortageData($filters, $perPage),
            'recovery' => $this->getRecoveryData($filters, $perPage),
            'statusChange' => $this->getStatusChangeData($filters, $perPage),
            'barChart' => $this->reportService->getStatusBarChart($filters)
        ];
    }

    private function getStatusChangeData($filters, $perPage = 5)
    {
        $statusChangeData = $this->reportService->getStatusChangeFrequency($filters);

        $paginated = $this->paginateData($statusChangeData, 1, $perPage);
        return [
            'data' => $paginated['data'],
            'statistics' => $this->calculateStatistics($statusChangeData),
            'pagination' => $paginated['pagination']
        ];
    }

    /**
     * Get Caution Leaderboard data
     */
    private function getCautionData($filters, $perPage = 5)
    {
        $leaderboard = $this->reportService->getCautionLeaderboard($filters);

        $paginated = $this->paginateData($leaderboard, 1, $perPage);

        return [
            'statistics' => [
                'total' => $leaderboard->count(),
                'average_days' => round($leaderboard->avg('days') ?? 0, 1),
                'max_days' => $leaderboard->max('days') ?? 0,
                'min_days' => $leaderboard->min('days') ?? 0,
                'type' => 'CAUTION',
            ],
            'leaderboard' => $paginated['data'],
            'pagination' => $paginated['pagination']
        ];
    }

    /**
     * Get Shortage Leaderboard data
     */
    private function getShortageData($filters, $perPage = 5)
    {
        $leaderboard = $this->reportService->getShortageLeaderboard($filters);

        $paginated = $this->paginateData($leaderboard, 1, $perPage);

        return [
            'statistics' => [
                'total' => $leaderboard->count(),
                'average_days' => round($leaderboard->avg('days') ?? 0, 1),
                'max_days' => $leaderboard->max('days') ?? 0,
                'min_days' => $leaderboard->min('days') ?? 0,
                'type' => 'SHORTAGE',
            ],
            'leaderboard' => $paginated['data'],
            'pagination' => $paginated['pagination']
        ];
    }

    /**
     * Get Recovery Days data
     */
    private function getRecoveryData($filters, $perPage = 5)
    {
        $recoveryResult = $this->reportService->getRecoveryDays($filters);
        $recoveryData = collect($recoveryResult['data']);

        $year = $filters['month'] ? date('Y', strtotime($filters['month'])) : date('Y');
        $trendCacheKey = 'recovery_trend_' . $year;

        $trendData = Cache::remember($trendCacheKey, 300, function () use ($year) {
            return $this->reportService->getRecoveryTrend($year);
        });

        $paginated = $this->paginateData($recoveryData, 1, $perPage);

        return [
            'statistics' => [
                'total_recovered' => $recoveryResult['statistics']['total_recovered'],
                'average_recovery_days' => $recoveryResult['statistics']['average_recovery_days'],
                'fastest_recovery' => $recoveryResult['statistics']['fastest_recovery'],
                'slowest_recovery' => $recoveryResult['statistics']['slowest_recovery'],
            ],
            'data' => $paginated['data'],
            'pagination' => $paginated['pagination'],
            'trendData' => $trendData
        ];
    }

    /**
     * Helper: Paginate data
     */
    private function paginateData($collection, $page = 1, $perPage = 5)
    {
        $total = $collection->count();
        $lastPage = (int) ceil($total / $perPage);

        if ($page > $lastPage && $lastPage > 0) {
            $page = $lastPage;
        }

        $paged = $collection->forPage($page, $perPage)->values();

        return [
            'data' => $paged,
            'pagination' => [
                'current_page' => $page,
                'last_page' => max(1, $lastPage),
                'per_page' => $perPage,
                'total' => $total,
            ]
        ];
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

    private function calculateStatistics($data)
    {
        $collection = collect($data);

        return [
            'total_materials' => $collection->count(),
            'total_to_caution' => $collection->sum('frequency_changes.ok_to_caution'),
            'total_to_shortage' => $collection->sum('frequency_changes.ok_to_shortage'),
            'total_to_overflow' => $collection->sum('frequency_changes.ok_to_overflow'),
            'total_recovered' => $collection->sum('frequency_changes.total_to_ok'),
            'total_changes' => $collection->sum('frequency_changes.total_from_ok'),
        ];
    }
}
