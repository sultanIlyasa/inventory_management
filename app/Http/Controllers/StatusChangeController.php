<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MaterialReportService;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class StatusChangeController extends Controller
{
    protected $reportService;

    public function __construct(MaterialReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Main status change page
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

        // Create cache key
        $cacheKey = 'status_change_' . md5(json_encode($filters));

        // Cache for 5 minutes
        $statusChangeData = Cache::remember($cacheKey, 300, function () use ($filters) {
            return $this->reportService->getStatusChangeFrequency($filters);
        });

        return Inertia::render('WarehouseMonitoring/StatusChange', [
            'statusChangeData' => $this->formatStatusChangeData($statusChangeData, $request),
            'statistics' => $this->calculateStatistics($statusChangeData),
            'filters' => $filters,
        ]);
    }

    /**
     * API: Get status change data (for AJAX)
     */
    public function statusChangeApi(Request $request)
    {
        $filters = $this->getFilters($request);
        $cacheKey = 'status_change_' . md5(json_encode($filters));

        $statusChangeData = Cache::remember($cacheKey, 300, function () use ($filters) {
            return $this->reportService->getGeneralReport($filters);
        });

        $formattedData = $this->formatStatusChangeData($statusChangeData, $request);

        return response()->json([
            'success' => true,
            'statusChangeData' => $formattedData['data'],
            'statistics' => $this->calculateStatistics($statusChangeData),
            'pagination' => $formattedData['pagination']
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
     * Helper: Format and paginate data
     */
    private function formatStatusChangeData($data, Request $request)
    {
        $collection = collect($data);

        $perPage = (int) $request->get('per_page', 10);
        $page = (int) $request->get('page', 1);
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
     * Helper: Calculate statistics
     */
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
