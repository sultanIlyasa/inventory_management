<?php

namespace App\Http\Controllers;

use App\Exports\StatusChangeLogExport;
use App\Http\Requests\StatusChangeRequest;
use App\Services\StatusChangeService;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

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

    public function exportLog(StatusChangeRequest $request)
    {
        $filters = $request->getFilters();
        $fromStatus = $request->query('from_status');
        $toStatus = $request->query('to_status');
        $fileName = 'status_change_log_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new StatusChangeLogExport($filters, $fromStatus, $toStatus), $fileName);
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
