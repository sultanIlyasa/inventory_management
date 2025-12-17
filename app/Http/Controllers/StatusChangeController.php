<?php

namespace App\Http\Controllers;

use App\Http\Requests\StatusChangeRequest;
use App\Services\StatusChangeService;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class StatusChangeController extends Controller
{
    public function __construct(
        protected StatusChangeService $statusChangeService
    ) {
    }

    public function index(StatusChangeRequest $request)
    {
        $filters = $request->getFilters();
        $pagination = $request->getPaginationParams();

        $cacheKey = sprintf(
            'status_change_%s_page_%d_per_%d',
            md5(json_encode($filters)),
            $pagination['page'],
            $pagination['per_page']
        );

        $statusChangeData = Cache::remember($cacheKey, 300, function () use ($filters, $pagination) {
            return $this->statusChangeService->getStatusChangeReport(
                $filters,
                $pagination['per_page'],
                $pagination['page']
            );
        });

        return Inertia::render('WarehouseMonitoring/StatusChange', [
            'statusChangeData' => [
                'data' => $statusChangeData['data'],
                'pagination' => $statusChangeData['pagination'],
            ],
            'statistics' => $statusChangeData['statistics'],
            'filters' => $this->withDefaultFilters($filters),
        ]);
    }

    public function statusChangeApi(StatusChangeRequest $request)
    {
        $filters = $request->getFilters();
        $pagination = $request->getPaginationParams();

        $cacheKey = sprintf(
            'status_change_%s_page_%d_per_%d',
            md5(json_encode($filters)),
            $pagination['page'],
            $pagination['per_page']
        );

        $statusChangeData = Cache::remember($cacheKey, 300, function () use ($filters, $pagination) {
            return $this->statusChangeService->getStatusChangeReport(
                $filters,
                $pagination['per_page'],
                $pagination['page']
            );
        });

        return response()->json([
            'success' => true,
            'statusChangeData' => $statusChangeData['data'],
            'statistics' => $statusChangeData['statistics'],
            'pagination' => $statusChangeData['pagination'],
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
        ];

        return array_merge($defaults, $filters);
    }
}
