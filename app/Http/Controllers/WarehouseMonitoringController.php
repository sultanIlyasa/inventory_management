<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Services\LeaderboardService;
use App\Services\MaterialReportService;
use App\Services\RecoveryDaysService;
use App\Services\StatusChangeService;
use Illuminate\Support\Facades\Cache;

class WarehouseMonitoringController extends Controller
{
    public function __construct(
        protected MaterialReportService $reportService,
        protected LeaderboardService $leaderboardService,
        protected RecoveryDaysService $recoveryDaysService,
        protected StatusChangeService $statusChangeService
    ) {
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
        $statusChangeData = $this->statusChangeService->getStatusChangeReport($filters, $perPage, 1);

        return [
            'data' => $statusChangeData['data'],
            'statistics' => $statusChangeData['statistics'],
            'pagination' => $statusChangeData['pagination'],
        ];
    }

    private function getCautionData($filters, $perPage = 5)
    {
        $leaderboard = $this->leaderboardService->getCautionLeaderboard($filters, $perPage, 1);

        return [
            'statistics' => $leaderboard['statistics'],
            'leaderboard' => $leaderboard['data'],
            'pagination' => $leaderboard['pagination'],
        ];
    }

    private function getShortageData($filters, $perPage = 5)
    {
        $leaderboard = $this->leaderboardService->getShortageLeaderboard($filters, $perPage, 1);

        return [
            'statistics' => $leaderboard['statistics'],
            'leaderboard' => $leaderboard['data'],
            'pagination' => $leaderboard['pagination'],
        ];
    }

    private function getRecoveryData($filters, $perPage = 5)
    {
        $recoveryResult = $this->recoveryDaysService->getRecoveryReport($filters, $perPage, 1);

        $year = !empty($filters['month']) ? date('Y', strtotime($filters['month'])) : date('Y');
        $trendCacheKey = 'recovery_trend_' . $year;

        $trendData = Cache::remember($trendCacheKey, 300, function () use ($year) {
            return $this->recoveryDaysService->getRecoveryTrend($year);
        });

        return [
            'statistics' => $recoveryResult['statistics'],
            'data' => $recoveryResult['data'],
            'pagination' => $recoveryResult['pagination'],
            'trendData' => $trendData,
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
            'gentani' => $request->gentani,
        ];
    }
}
