<?php

namespace App\Http\Controllers;

use App\Services\MaterialReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class RecoveryDaysController extends Controller
{
    protected $reportService;

    public function __construct(MaterialReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Main recovery days page
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

        // Create cache key based on filters
        $cacheKey = 'recovery_days_' . md5(json_encode($filters));

        // Cache for 5 minutes
        $recoveryData = Cache::remember($cacheKey, 300, function () use ($filters) {
            return $this->reportService->getRecoveryDays($filters);
        });

        // Get year for trend data
        $year = $filters['month'] ? date('Y', strtotime($filters['month'])) : date('Y');
        $trendCacheKey = 'recovery_trend_' . $year;

        $trendData = Cache::remember($trendCacheKey, 300, function () use ($year) {
            return $this->reportService->getRecoveryTrend($year);
        });

        return Inertia::render('WarehouseMonitoring/RecoveryDays', [
            'recoveryData' => $this->formatRecoveryData($recoveryData['data'], $request),
            'statistics' => $recoveryData['statistics'],
            'trendData' => $trendData,
            'filters' => $filters,
        ]);
    }

    /**
     * API: Get recovery days data (for AJAX requests)
     */
    public function recoveryApi(Request $request)
    {
        $filters = $this->getFilters($request);
        $cacheKey = 'recovery_days_' . md5(json_encode($filters));

        $recoveryData = Cache::remember($cacheKey, 300, function () use ($filters) {
            return $this->reportService->getRecoveryDays($filters);
        });
        $year = $filters['month'] ? date('Y', strtotime($filters['month'])) : date('Y');
        $trendCacheKey = 'recovery_trend_' . $year;
        $trendData = Cache::remember($trendCacheKey, 300, function () use ($year) {
            return $this->reportService->getRecoveryTrend($year);
        });

        $formattedData = $this->formatRecoveryData($recoveryData['data'], $request);

        return response()->json([
            'success' => true,
            'recoveryData' => $formattedData['data'],
            'statistics' => $recoveryData['statistics'],
            'trendData' => $trendData,
            'pagination' => $formattedData['pagination']
        ]);
    }

    /**
     * API: Get recovery trend data
     */
    public function trendApi(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $cacheKey = 'recovery_trend_' . $year;

        $trendData = Cache::remember($cacheKey, 300, function () use ($year) {
            return $this->reportService->getRecoveryTrend($year);
        });

        return response()->json([
            'success' => true,
            'data' => $trendData
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
            'gentani' => $request->gentani
        ];
    }

    /**
     * Helper: Format and paginate recovery data
     */
    private function formatRecoveryData($data, Request $request)
    {
        $collection = collect($data);

        $perPage = (int) $request->get('per_page', 10);
        $page = (int) $request->get('page', 1);
        $total = $collection->count();

        // Ensure page is valid
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
}
