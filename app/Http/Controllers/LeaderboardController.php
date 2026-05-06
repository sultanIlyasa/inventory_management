<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaderboardRequest;
use App\Services\LeaderboardService;
use Inertia\Inertia;

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
        $cautionData = $this->getLeaderboardData('CAUTION', $filters, $perPage, $page);
        $shortageData = $this->getLeaderboardData('SHORTAGE', $filters, $perPage, $page);

        return Inertia::render('WarehouseMonitoring/Leaderboard', [
            'cautionData' => $cautionData,
            'shortageData' => $shortageData,
            'activeTab' => $activeTab,
            'filters' => $filters,
        ]);
    }

    /**
     * API endpoint for AJAX calls
     */
    public function cautionApi(LeaderboardRequest $request)
    {
        $filters = $request->getFilters();
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 10);

        $data = $this->getLeaderboardData('CAUTION', $filters, $perPage, $page);

        return response()->json([
            'success' => true,
            'data' => $data,
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

        $data = $this->getLeaderboardData('SHORTAGE', $filters, $perPage, $page);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    private function getLeaderboardData(string $type, array $filters, int $perPage, int $page): array
    {
        if ($type === 'CAUTION') {
            return $this->leaderboardService->getCautionLeaderboard($filters, $perPage, $page);
        }

        return $this->leaderboardService->getShortageLeaderboard($filters, $perPage, $page);
    }
}
