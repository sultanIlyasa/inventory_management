<?php

namespace App\Http\Controllers;

use App\Http\Requests\OverdueDaysRequest;
use App\Services\OverdueDaysService;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class OverdueDaysController extends Controller
{
    public function __construct(
        protected OverdueDaysService $overdueDaysService
    ) {
    }

    public function index(OverdueDaysRequest $request)
    {
        $filters = $request->getFilters();
        $pagination = $request->getPaginationParams();

        $cacheKey = sprintf(
            'overdue_days_%s_page_%d_per_%d',
            md5(json_encode($filters)),
            $pagination['page'],
            $pagination['per_page']
        );

        $result = Cache::remember($cacheKey, 300, function () use ($filters, $pagination) {
            return $this->overdueDaysService->getOverdueReports(
                $filters,
                $pagination['per_page'],
                $pagination['page']
            );
        });

        return Inertia::render('WarehouseMonitoring/OverdueDays', [
            'overdueReports' => [
                'data' => $result['data'],
                'pagination' => $result['pagination'],
            ],
            'statistics' => $result['statistics'],
            'filterOptions' => $result['filter_options'],
            'filters' => $this->withDefaultFilters($filters),
        ]);
    }

    private function withDefaultFilters(array $filters): array
    {
        $defaults = [
            'date' => '',
            'month' => '',
            'usage' => '',
            'location' => '',
            'gentani' => '',
            'search' => '',
            'pic' => '',
            'status' => '',
            'sortField' => 'status',
            'sortDirection' => 'desc',
        ];

        return array_merge($defaults, $filters);
    }
}
