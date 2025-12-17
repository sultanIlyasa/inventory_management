<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Services\LeaderboardService;
use App\Http\Requests\LeaderboardRequest;
use Illuminate\Support\Facades\Cache;

/**
 * Skinny Controller for Inertia.js
 * Single Responsibility: Handle HTTP requests and return Inertia responses
 */
class LeaderboardController extends Controller
{
    protected LeaderboardService $leaderboardService;

    public function __construct(LeaderboardService $leaderboardService)
    {
        $this->leaderboardService = $leaderboardService;
    }

    /**
     * Main Inertia page - handles both initial load and pagination
     */
    public function index(LeaderboardRequest $request)
    {
        $filters = $request->getFilters();
        $activeTab = $request->input('tab', 'CAUTION');
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 10);

        // Get both leaderboards with pagination
        $cautionData = $this->getCachedLeaderboard('CAUTION', $filters, $perPage, $page);
        $shortageData = $this->getCachedLeaderboard('SHORTAGE', $filters, $perPage, $page);

        return Inertia::render('WarehouseMonitoring/Leaderboard', [
            'cautionData' => $cautionData,
            'shortageData' => $shortageData,
            'activeTab' => $activeTab,
            'filters' => $filters,
        ]);
    }

    /**
     * API endpoint for AJAX calls (if you still need it)
     * Returns JSON instead of Inertia response
     */
    public function cautionApi(LeaderboardRequest $request)
    {
        $filters = $request->getFilters();
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 10);

        $data = $this->getCachedLeaderboard('CAUTION', $filters, $perPage, $page);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * API endpoint for SHORTAGE
     */
    public function shortageApi(LeaderboardRequest $request)
    {
        $filters = $request->getFilters();
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 10);

        $data = $this->getCachedLeaderboard('SHORTAGE', $filters, $perPage, $page);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get cached leaderboard data
     */
    private function getCachedLeaderboard(string $type, array $filters, int $perPage, int $page): array
    {
        // Include page in cache key!
        $cacheKey = sprintf(
            'leaderboard_%s_%s_page_%d_per_%d',
            $type,
            md5(json_encode($filters)),
            $page,  // ← IMPORTANT: Page must be in cache key
            $perPage
        );

        // Cache for 5 minutes
        return Cache::remember($cacheKey, 300, function () use ($type, $filters, $perPage, $page) {
            if ($type === 'CAUTION') {
                return $this->leaderboardService->getCautionLeaderboard($filters, $perPage, $page);
            } else {
                return $this->leaderboardService->getShortageLeaderboard($filters, $perPage, $page);
            }
        });
    }
}
