<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Services\LeaderboardService;
use App\Services\MaterialReportService;
use App\Services\RecoveryDaysService;
use App\Services\StatusChangeService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
            'barChart' => $this->reportService->getStatusBarChart($filters),
            'fastestToCritical' => $this->getFastestToCriticalData($filters),
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

    private function getFastestToCriticalData(array $filters): array
    {
        return $this->leaderboardService->getFastestToCritical($filters, 5);
    }

    /**
     * API endpoint - inventory overview with real data
     */
    public function inventoryOverviewApi(Request $request)
    {
        $request->validate([
            'location' => 'nullable|in:SUNTER_1,SUNTER_2',
            'usage'    => 'nullable|in:DAILY,WEEKLY,MONTHLY',
            'month'    => 'nullable|date_format:Y-m',
            'gentani'  => 'nullable|in:GENTAN-I,NON_GENTAN-I',
            'page'     => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $filters = $this->getFilters($request);
        $page    = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 10);
        $offset  = ($page - 1) * $perPage;

        $whereConditions = [];
        $bindings        = [];

        if (!empty($filters['usage']))    { $whereConditions[] = "materials.usage = :usage";       $bindings['usage']    = $filters['usage']; }
        if (!empty($filters['location'])) { $whereConditions[] = "materials.location = :location"; $bindings['location'] = $filters['location']; }
        if (!empty($filters['gentani']))  { $whereConditions[] = "materials.gentani = :gentani";   $bindings['gentani']  = $filters['gentani']; }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $countSql = "SELECT COUNT(DISTINCT materials.id) as total FROM materials {$whereClause}";
        $total    = DB::selectOne($countSql, $bindings)->total ?? 0;

        $dataSql = "
            SELECT materials.id, materials.material_number, materials.description, materials.usage,
                   latest_di.status AS raw_status, latest_di.daily_stock AS soh,
                   pm.streak_days, pm.coverage_shifts
            FROM materials
            INNER JOIN (
                SELECT di.material_id, di.status, di.daily_stock
                FROM daily_inputs di
                INNER JOIN (
                    SELECT material_id, MAX(date) AS max_date FROM daily_inputs GROUP BY material_id
                ) latest ON di.material_id = latest.material_id AND di.date = latest.max_date
            ) AS latest_di ON materials.id = latest_di.material_id
            LEFT JOIN problematic_materials pm ON pm.material_number = materials.material_number
            {$whereClause}
            ORDER BY materials.material_number ASC
            LIMIT :limit OFFSET :offset
        ";

        $dataBindings = array_merge($bindings, ['limit' => $perPage, 'offset' => $offset]);
        $rows = DB::select($dataSql, $dataBindings);

        $data = array_map(function ($row) {
            $rawStatus = $row->raw_status ?? 'OK';
            if ($rawStatus === 'SHORTAGE') {
                $status = 'Shortage';
                $overdueDays  = $row->streak_days ?? null;
                $recoveryDays = $row->coverage_shifts !== null ? (int) ceil($row->coverage_shifts / 3) : null;
            } elseif ($rawStatus === 'CAUTION') {
                $status = 'Caution';
                $overdueDays  = $row->streak_days ?? null;
                $recoveryDays = $row->coverage_shifts !== null ? (int) ceil($row->coverage_shifts / 3) : null;
            } else {
                $status = 'OK';
                $overdueDays  = null;
                $recoveryDays = null;
            }

            return [
                'id'           => $row->id,
                'material'     => $row->description,
                'sku'          => $row->material_number,
                'category'     => $row->usage,
                'status'       => $status,
                'overdueDays'  => $overdueDays,
                'recoveryDays' => $recoveryDays,
                'soh'          => (int) $row->soh,
            ];
        }, $rows);

        $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;

        return response()->json([
            'success' => true,
            'data'    => $data,
            'pagination' => [
                'current_page' => $page,
                'last_page'    => $lastPage,
                'per_page'     => $perPage,
                'total'        => $total,
                'from'         => $total > 0 ? $offset + 1 : null,
                'to'           => $total > 0 ? min($offset + $perPage, $total) : null,
            ],
        ]);
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
