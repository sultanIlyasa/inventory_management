<?php

namespace App\Http\Controllers;

use App\Services\MaterialReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class OverdueDaysController extends Controller
{
    protected $reportService;

    public function __construct(MaterialReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Display the overdue days dashboard using Inertia.
     */
    public function index(Request $request)
    {
        $request->validate([
            'location' => 'nullable|in:SUNTER_1,SUNTER_2',
            'usage' => 'nullable|in:DAILY,WEEKLY,MONTHLY',
            'month' => 'nullable|date_format:Y-m',
            'gentani' => 'nullable|in:GENTAN-I,NON_GENTAN-I',
            'status' => 'nullable|in:SHORTAGE,CAUTION,OVERFLOW,OK,UNCHECKED,N/A',
            'sortField' => 'nullable|in:status,days',
            'sortDirection' => 'nullable|in:asc,desc',
        ]);

        $filters = $this->getFilters($request);

        $cacheKey = 'overdue_days_' . md5(json_encode($filters));

        $overdueData = Cache::remember($cacheKey, 300, function () use ($filters) {
            return $this->reportService->getOverdueStatus($filters);
        });

        $formattedData = $this->formatOverdueData($overdueData['data'], $filters, $request);

        return Inertia::render('WarehouseMonitoring/OverdueDays', [
            'overdueReports' => [
                'data' => $formattedData['data'],
                'pagination' => $formattedData['pagination'],
            ],
            'statistics' => $this->getStatusStatistics($formattedData['all_data']),
            'filterOptions' => $this->getFilterOptions($formattedData['all_data']),
            'filters' => $filters,
        ]);
    }

    private function getFilters(Request $request): array
    {
        return [
            'date' => $request->date,
            'month' => $request->month,
            'usage' => $request->usage,
            'location' => $request->location,
            'gentani' => $request->gentani,
            'search' => $request->search,
            'pic' => $request->pic,
            'status' => $request->status,
            'sortField' => $request->get('sortField', 'status'),
            'sortDirection' => $request->get('sortDirection', 'desc'),
        ];
    }

    private function formatOverdueData($data, array $filters, Request $request): array
    {
        $collection = collect($data);

        $filtered = $collection->filter(function ($item) use ($filters) {
            $matches = true;

            if (!empty($filters['pic'])) {
                $matches = $matches && strcasecmp($item['pic'], $filters['pic']) === 0;
            }

            if (!empty($filters['location'])) {
                $matches = $matches && strcasecmp($item['location'], $filters['location']) === 0;
            }

            if (!empty($filters['usage'])) {
                $matches = $matches && strcasecmp($item['usage'], $filters['usage']) === 0;
            }

            if (!empty($filters['gentani'])) {
                $matches = $matches && strcasecmp($item['gentani'], $filters['gentani']) === 0;
            }

            if (!empty($filters['status'])) {
                $matches = $matches && strcasecmp($item['current_status'], $filters['status']) === 0;
            }

            if (!empty($filters['search'])) {
                $query = strtolower($filters['search']);
                $matches = $matches && (
                    str_contains(strtolower((string) $item['material_number']), $query) ||
                    str_contains(strtolower((string) $item['description']), $query) ||
                    str_contains(strtolower((string) $item['pic']), $query) ||
                    str_contains(strtolower((string) $item['current_status']), $query)
                );
            }

            return $matches;
        })->values();

        $statusPriority = [
            'SHORTAGE' => 1,
            'CAUTION' => 2,
            'OVERFLOW' => 3,
            'OK' => 4,
            'UNCHECKED' => 5,
            'N/A' => 6,
        ];

        $sortField = $filters['sortField'] ?? 'status';
        $sortDirection = $filters['sortDirection'] ?? 'desc';

        $sorted = $filtered->sort(function ($a, $b) use ($sortField, $sortDirection, $statusPriority) {
            $compareValue = 0;

            if ($sortField === 'days') {
                $compareValue = ($b['days'] ?? 0) <=> ($a['days'] ?? 0);
                if ($compareValue === 0) {
                    $compareValue = ($statusPriority[$a['current_status']] ?? 999) <=> ($statusPriority[$b['current_status']] ?? 999);
                }
            } else {
                $compareValue = ($statusPriority[$a['current_status']] ?? 999) <=> ($statusPriority[$b['current_status']] ?? 999);
                if ($compareValue === 0) {
                    $compareValue = ($b['days'] ?? 0) <=> ($a['days'] ?? 0);
                }
            }

            return $sortDirection === 'asc' ? -$compareValue : $compareValue;
        })->values();

        $perPage = (int) $request->get('per_page', 15);
        $page = (int) $request->get('page', 1);
        $total = $sorted->count();
        $lastPage = (int) ceil($total / $perPage);

        if ($page > $lastPage && $lastPage > 0) {
            $page = $lastPage;
        }

        $paged = $sorted->forPage($page, $perPage)->values();

        $formatted = $paged->map(function ($item, $index) use ($page, $perPage) {
            return [
                'key' => 'report-' . (($page - 1) * $perPage + $index),
                'number' => ($page - 1) * $perPage + $index + 1,
                'material_number' => $item['material_number'],
                'description' => $item['description'],
                'pic_name' => $item['pic'],
                'instock' => $item['instock'],
                'status' => $item['current_status'],
                'days' => $item['days'],
                'location' => $item['location'],
                'usage' => $item['usage'],
                'gentani' => $item['gentani'],
            ];
        });

        return [
            'data' => $formatted,
            'pagination' => [
                'current_page' => $page,
                'last_page' => max(1, $lastPage),
                'per_page' => $perPage,
                'total' => $total,
            ],
            'all_data' => $sorted,
        ];
    }

    private function getStatusStatistics($data): array
    {
        $collection = collect($data);

        return [
            'SHORTAGE' => $collection->where('current_status', 'SHORTAGE')->count(),
            'CAUTION' => $collection->where('current_status', 'CAUTION')->count(),
            'OVERFLOW' => $collection->where('current_status', 'OVERFLOW')->count(),
            'OK' => $collection->where('current_status', 'OK')->count(),
            'UNCHECKED' => $collection->where('current_status', 'UNCHECKED')->count(),
        ];
    }

    private function getFilterOptions($data): array
    {
        $collection = collect($data);

        return [
            'pics' => $collection->pluck('pic')->filter()->unique()->sort()->values(),
            'locations' => $collection->pluck('location')->filter()->unique()->sort()->values(),
            'usages' => $collection->pluck('usage')->filter()->unique()->sort()->values(),
            'gentani' => $collection->pluck('gentani')->filter()->unique()->sort()->values(),
        ];
    }
}
