<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaderChecklistRequest;
use App\Services\LeaderChecklistService;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class LeaderChecklistController extends Controller
{
    public function __construct(
        protected LeaderChecklistService $leaderChecklistService
    ) {
    }

    public function index(LeaderChecklistRequest $request)
    {
        $filters = $request->getFilters();
        $pagination = $request->getPaginationParams();

        $cacheKey = sprintf(
            'leader_checklist_%s_page_%d_per_%d',
            md5(json_encode($filters)),
            $pagination['page'],
            $pagination['per_page']
        );

        $result = Cache::remember($cacheKey, 300, function () use ($filters, $pagination) {
            return $this->leaderChecklistService->getComplianceReport(
                $filters,
                $pagination['per_page'],
                $pagination['page']
            );
        });

        return Inertia::render('WarehouseMonitoring/LeaderChecklist', [
            'complianceReports' => [
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
            'usage' => '',
            'location' => '',
            'gentani' => '',
            'search' => '',
            'pic' => '',
        ];

        return array_merge($defaults, $filters);
    }
}
