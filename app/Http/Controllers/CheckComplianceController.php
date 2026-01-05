<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckComplianceRequest;
use App\Services\CheckComplianceService;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class CheckComplianceController extends Controller
{
    public function __construct(
        protected CheckComplianceService $checkComplianceService
    ) {
    }

    public function index(CheckComplianceRequest $request)
    {
        $filters = $request->getFilters();
        $pagination = $request->getPaginationParams();

        $cacheKey = sprintf(
            'check_compliance_%s_page_%d_per_%d',
            md5(json_encode($filters)),
            $pagination['page'],
            $pagination['per_page']
        );

        $result = Cache::remember($cacheKey, 300, function () use ($filters, $pagination) {
            return $this->checkComplianceService->getComplianceReport(
                $filters,
                $pagination['per_page'],
                $pagination['page']
            );
        });

        return Inertia::render('WarehouseMonitoring/CheckCompliance', [
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
