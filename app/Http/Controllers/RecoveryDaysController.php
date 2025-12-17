<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecoveryDaysRequest;
use App\Services\RecoveryDaysService;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class RecoveryDaysController extends Controller
{
    public function __construct(
        protected RecoveryDaysService $recoveryDaysService
    ) {
    }

    public function index(RecoveryDaysRequest $request)
    {
        $filters = $request->getFilters();
        $pagination = $request->getPaginationParams();

        $cacheKey = sprintf(
            'recovery_days_%s_page_%d_per_%d',
            md5(json_encode($filters)),
            $pagination['page'],
            $pagination['per_page']
        );

        $recoveryData = Cache::remember($cacheKey, 300, function () use ($filters, $pagination) {
            return $this->recoveryDaysService->getRecoveryReport(
                $filters,
                $pagination['per_page'],
                $pagination['page']
            );
        });

        $year = $request->getTrendYear();
        $trendCacheKey = 'recovery_trend_' . $year;

        $trendData = Cache::remember($trendCacheKey, 300, function () use ($year) {
            return $this->recoveryDaysService->getRecoveryTrend($year);
        });

        return Inertia::render('WarehouseMonitoring/RecoveryDays', [
            'recoveryData' => [
                'data' => $recoveryData['data'],
                'pagination' => $recoveryData['pagination'],
            ],
            'statistics' => $recoveryData['statistics'],
            'trendData' => $trendData,
            'filters' => $this->withDefaultFilters($filters),
        ]);
    }

    public function recoveryApi(RecoveryDaysRequest $request)
    {
        $filters = $request->getFilters();
        $pagination = $request->getPaginationParams();

        $cacheKey = sprintf(
            'recovery_days_%s_page_%d_per_%d',
            md5(json_encode($filters)),
            $pagination['page'],
            $pagination['per_page']
        );

        $recoveryData = Cache::remember($cacheKey, 300, function () use ($filters, $pagination) {
            return $this->recoveryDaysService->getRecoveryReport(
                $filters,
                $pagination['per_page'],
                $pagination['page']
            );
        });

        $year = $request->getTrendYear();
        $trendCacheKey = 'recovery_trend_' . $year;
        $trendData = Cache::remember($trendCacheKey, 300, function () use ($year) {
            return $this->recoveryDaysService->getRecoveryTrend($year);
        });

        return response()->json([
            'success' => true,
            'recoveryData' => $recoveryData['data'],
            'statistics' => $recoveryData['statistics'],
            'trendData' => $trendData,
            'pagination' => $recoveryData['pagination'],
        ]);
    }

    public function trendApi(RecoveryDaysRequest $request)
    {
        $year = $request->getTrendYear();
        $cacheKey = 'recovery_trend_' . $year;

        $trendData = Cache::remember($cacheKey, 300, function () use ($year) {
            return $this->recoveryDaysService->getRecoveryTrend($year);
        });

        return response()->json([
            'success' => true,
            'data' => $trendData,
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
