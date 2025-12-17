<?php

namespace App\Services;

use App\Models\Materials;
use App\Models\DailyInput;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Service responsible for material reporting business logic
 * Single Responsibility: Generate material reports and analytics
 *
 * This service uses Fat Models - all DB queries are in Models
 */
class MaterialReportService
{
    /**
     * Get general report with pagination
     * Single responsibility: Coordinate data retrieval and formatting
     */
    public function getGeneralReport(array $filters = [], int $perPage = 50, int $page = 1): array
    {
        $reports = [];

        // Use chunking to handle large datasets efficiently
        Materials::applyFilters($filters)
            ->select(['id', 'material_number', 'description', 'pic_name', 'usage', 'location', 'gentani'])
            ->chunk(100, function ($materials) use (&$reports, $filters) {
                foreach ($materials as $material) {
                    $reports[] = $this->buildMaterialReport($material, $filters);
                }
            });

        // Paginate in memory (or use a better approach with query builder pagination)
        $total = count($reports);
        $offset = ($page - 1) * $perPage;
        $paginatedReports = array_slice($reports, $offset, $perPage);

        return [
            'data' => $paginatedReports,
            'statistics' => $this->calculateStatistics($reports),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ]
        ];
    }

    /**
     * Build report for a single material
     * Single responsibility: Format material data for report
     */
    private function buildMaterialReport(Materials $material, array $filters): array
    {
        $month = $filters['month'] ?? null;
        $dateFilter = $filters['date'] ?? null;

        // Get latest input using model method (Fat Model principle)
        $latestInput = DailyInput::getLatestForMaterial($material->id, $dateFilter);

        if (!$latestInput) {
            return $this->getEmptyMaterialReport($material);
        }

        // Get all metrics using model methods (DB logic in models)
        $statusDays = DailyInput::getStatusDaysForMaterial($material->id, $month, $dateFilter);
        $frequencyChanges = DailyInput::getStatusTransitionsForMaterial($material->id, $month, $dateFilter);
        $consecutiveDays = DailyInput::getConsecutiveDaysForMaterial(
            $material->id,
            $latestInput->status,
            $latestInput->date->toDateString()
        );

        return [
            'material_number' => $material->material_number,
            'description' => $material->description,
            'pic' => $material->pic_name ?? '-',
            'instock_latest' => $latestInput->daily_stock,
            'current_status' => $latestInput->status,
            'consecutive_days' => $consecutiveDays,
            'status_days' => $statusDays,
            'frequency_changes' => $frequencyChanges,
            'usage' => $material->usage ?? '-',
            'location' => $material->location ?? '-',
            'gentani' => $material->gentani ?? '-',
            'last_updated' => $latestInput->date->toDateString(),
        ];
    }

    /**
     * Get status bar chart data
     * Single responsibility: Format status distribution for charts
     */
    public function getStatusBarChart(array $filters = []): array
    {
        // Use model method for status distribution (Fat Model)
        $statusCounts = Materials::getStatusDistribution($filters);

        return [
            'statusBarChart' => collect(['SHORTAGE', 'CAUTION', 'OK', 'OVERFLOW', 'UNCHECKED'])
                ->map(fn($status) => [
                    'status' => $status,
                    'count' => $statusCounts[$status] ?? 0,
                ])
                ->values()
                ->toArray(),
            'summary' => $statusCounts,
        ];
    }

    // Recovery days & status change methods moved to dedicated services (RecoveryDaysService/StatusChangeService).

    // Overdue status logic now handled inside OverdueDaysService.

    /**
     * Helper: Get empty material report structure
     */
    private function getEmptyMaterialReport(Materials $material): array
    {
        return [
            'material_number' => $material->material_number,
            'description' => $material->description,
            'pic' => $material->pic_name ?? '-',
            'instock_latest' => 0,
            'current_status' => 'UNCHECKED',
            'consecutive_days' => 0,
            'status_days' => [
                'OK' => 0,
                'CAUTION' => 0,
                'SHORTAGE' => 0,
                'OVERFLOW' => 0,
                'UNCHECKED' => 0,
            ],
            'frequency_changes' => [
                'ok_to_caution' => 0,
                'ok_to_shortage' => 0,
                'ok_to_overflow' => 0,
                'caution_to_ok' => 0,
                'shortage_to_ok' => 0,
                'overflow_to_ok' => 0,
                'total_from_ok' => 0,
                'total_to_ok' => 0,
            ],
            'usage' => $material->usage ?? '-',
            'location' => $material->location ?? '-',
            'gentani' => $material->gentani ?? '-',
            'last_updated' => null,
        ];
    }

    /**
     * Helper: Calculate overall statistics
     */
    private function calculateStatistics(array $reports): array
    {
        $statusCounts = [
            'total' => count($reports),
            'OK' => 0,
            'CAUTION' => 0,
            'SHORTAGE' => 0,
            'OVERFLOW' => 0,
            'UNCHECKED' => 0,
        ];

        foreach ($reports as $report) {
            $status = $report['current_status'];
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
        }

        return $statusCounts;
    }
}
