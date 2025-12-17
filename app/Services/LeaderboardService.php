<?php

namespace App\Services;

use App\Models\Materials;
use Illuminate\Support\Facades\DB;

/**
 * SUPER SIMPLE Leaderboard Service
 * Easy to understand, no complex type hints or builders
 */
class LeaderboardService
{
    /**
     * Get CAUTION leaderboard
     */
    public function getCautionLeaderboard(array $filters = [], int $perPage = 10, int $page = 1): array
    {
        return $this->getLeaderboard('CAUTION', $filters, $perPage, $page);
    }

    /**
     * Get SHORTAGE leaderboard
     */
    public function getShortageLeaderboard(array $filters = [], int $perPage = 10, int $page = 1): array
    {
        return $this->getLeaderboard('SHORTAGE', $filters, $perPage, $page);
    }

    /**
     * Get both leaderboards
     */
    public function getAllLeaderboards(array $filters = [], int $perPage = 10): array
    {
        return [
            'caution' => $this->getCautionLeaderboard($filters, $perPage, 1),
            'shortage' => $this->getShortageLeaderboard($filters, $perPage, 1),
        ];
    }

    /**
     * Main method: Get leaderboard data
     * Uses simple SQL queries - easy to understand and debug
     */
    private function getLeaderboard(string $status, array $filters, int $perPage, int $page): array
    {
        // Step 1: Build WHERE conditions
        $whereConditions = ["daily_inputs.status = :status"];
        $bindings = ['status' => $status];

        if (!empty($filters['usage'])) {
            $whereConditions[] = "materials.usage = :usage";
            $bindings['usage'] = $filters['usage'];
        }

        if (!empty($filters['location'])) {
            $whereConditions[] = "materials.location = :location";
            $bindings['location'] = $filters['location'];
        }

        if (!empty($filters['gentani'])) {
            $whereConditions[] = "materials.gentani = :gentani";
            $bindings['gentani'] = $filters['gentani'];
        }
        if (!empty($filters['pic_name'])) {
            $whereConditions[] = "materials.pic_name = :pic_name";
            $bindings['pic_name'] = $filters['pic_name'];
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Step 2: Get total count (for pagination)
        $countSql = "
            SELECT COUNT(DISTINCT materials.id) as total
            FROM materials
            INNER JOIN daily_inputs ON materials.id = daily_inputs.material_id
            WHERE {$whereClause}
        ";

        $totalResult = DB::selectOne($countSql, $bindings);
        $total = $totalResult->total ?? 0;

        // Step 3: Get paginated data
        $offset = ($page - 1) * $perPage;
        $dataSql = "
            SELECT
                materials.id,
                materials.material_number,
                materials.description,
                materials.pic_name,
                materials.usage,
                materials.location,
                materials.gentani,
                COUNT(DISTINCT daily_inputs.date) as days,
                MAX(daily_inputs.daily_stock) as current_stock
            FROM materials
            INNER JOIN daily_inputs ON materials.id = daily_inputs.material_id
            WHERE {$whereClause}
            GROUP BY
                materials.id,
                materials.material_number,
                materials.description,
                materials.pic_name,
                materials.usage,
                materials.location,
                materials.gentani
            ORDER BY days DESC
            LIMIT :limit OFFSET :offset
        ";

        $dataBindings = array_merge($bindings, [
            'limit' => $perPage,
            'offset' => $offset
        ]);

        $data = DB::select($dataSql, $dataBindings);

        // Step 4: Calculate statistics (only if we have data)
        $statistics = $this->calculateStatistics($status, $filters, $total);

        // Step 5: Return formatted response
        return [
            'data' => $data,
            'statistics' => $statistics,
            'pagination' => [
                'current_page' => $page,
                'last_page' => $total > 0 ? (int) ceil($total / $perPage) : 1,
                'per_page' => $perPage,
                'total' => $total,
                'from' => $total > 0 ? $offset + 1 : null,
                'to' => $total > 0 ? min($offset + $perPage, $total) : null,
            ]
        ];
    }

    /**
     * Calculate statistics for the leaderboard
     * Simple query - gets AVG, MAX, MIN of days
     */
    private function calculateStatistics(string $status, array $filters, int $total): array
    {
        // If no records, return zeros
        if ($total === 0) {
            return [
                'type' => $status,
                'total' => 0,
                'average_days' => 0,
                'max_days' => 0,
                'min_days' => 0,
            ];
        }

        // Build WHERE conditions (same as main query)
        $whereConditions = ["daily_inputs.status = :status"];
        $bindings = ['status' => $status];

        if (!empty($filters['usage'])) {
            $whereConditions[] = "materials.usage = :usage";
            $bindings['usage'] = $filters['usage'];
        }

        if (!empty($filters['location'])) {
            $whereConditions[] = "materials.location = :location";
            $bindings['location'] = $filters['location'];
        }

        if (!empty($filters['gentani'])) {
            $whereConditions[] = "materials.gentani = :gentani";
            $bindings['gentani'] = $filters['gentani'];
        }
        if (!empty($filters['pic_name'])) {
            $whereConditions[] = "materials.pic_name = :pic_name";
            $bindings['pic_name'] = $filters['pic_name'];
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Query to get statistics
        $statsSql = "
            SELECT
                AVG(days_per_material.days) as avg_days,
                MAX(days_per_material.days) as max_days,
                MIN(days_per_material.days) as min_days
            FROM (
                SELECT
                    materials.id,
                    COUNT(DISTINCT daily_inputs.date) as days
                FROM materials
                INNER JOIN daily_inputs ON materials.id = daily_inputs.material_id
                WHERE {$whereClause}
                GROUP BY materials.id
            ) as days_per_material
        ";

        $stats = DB::selectOne($statsSql, $bindings);

        return [
            'type' => $status,
            'total' => $total,
            'average_days' => round($stats->avg_days ?? 0, 1),
            'max_days' => $stats->max_days ?? 0,
            'min_days' => $stats->min_days ?? 0,
        ];
    }

    /**
     * Get top N materials (for widgets/cards)
     */
    public function getTopMaterials(string $status, array $filters = [], int $limit = 5): array
    {
        $whereConditions = ["daily_inputs.status = :status"];
        $bindings = ['status' => $status];

        if (!empty($filters['usage'])) {
            $whereConditions[] = "materials.usage = :usage";
            $bindings['usage'] = $filters['usage'];
        }

        if (!empty($filters['location'])) {
            $whereConditions[] = "materials.location = :location";
            $bindings['location'] = $filters['location'];
        }

        if (!empty($filters['gentani'])) {
            $whereConditions[] = "materials.gentani = :gentani";
            $bindings['gentani'] = $filters['gentani'];
        }
        if (!empty($filters['pic_name'])) {
            $whereConditions[] = "materials.pic_name = :pic_name";
            $bindings['pic_name'] = $filters['pic_name'];
        }

        $whereClause = implode(' AND ', $whereConditions);

        $sql = "
            SELECT
                materials.id,
                materials.material_number,
                materials.description,
                materials.pic_name ,
                materials.usage,
                materials.location,
                materials.gentani,
                COUNT(DISTINCT daily_inputs.date) as days,
                MAX(daily_inputs.daily_stock) as current_stock
            FROM materials
            INNER JOIN daily_inputs ON materials.id = daily_inputs.material_id
            WHERE {$whereClause}
            GROUP BY
                materials.id,
                materials.material_number,
                materials.description,
                materials.pic_name,
                materials.usage,
                materials.location,
                materials.gentani
            ORDER BY days DESC
            LIMIT :limit
        ";

        $bindings['limit'] = $limit;

        return DB::select($sql, $bindings);
    }

    /**
     * Get summary counts
     */
    public function getLeaderboardSummary(array $filters = []): array
    {
        $whereConditions = [];
        $bindings = [];

        if (!empty($filters['usage'])) {
            $whereConditions[] = "materials.usage = :usage";
            $bindings['usage'] = $filters['usage'];
        }

        if (!empty($filters['location'])) {
            $whereConditions[] = "materials.location = :location";
            $bindings['location'] = $filters['location'];
        }

        if (!empty($filters['gentani'])) {
            $whereConditions[] = "materials.gentani = :gentani";
            $bindings['gentani'] = $filters['gentani'];
        }
         if (!empty($filters['pic_name'])) {
            $whereConditions[] = "materials.pic_name = :pic_name";
            $bindings['pic_name'] = $filters['pic_name'];
        }

        $whereClause = !empty($whereConditions) ? 'AND ' . implode(' AND ', $whereConditions) : '';

        $sql = "
            SELECT
                SUM(CASE WHEN daily_inputs.status = 'CAUTION' THEN 1 ELSE 0 END) as caution_count,
                SUM(CASE WHEN daily_inputs.status = 'SHORTAGE' THEN 1 ELSE 0 END) as shortage_count
            FROM (
                SELECT DISTINCT materials.id, daily_inputs.status
                FROM materials
                INNER JOIN daily_inputs ON materials.id = daily_inputs.material_id
                WHERE 1=1 {$whereClause}
            ) as unique_materials
        ";

        $result = DB::selectOne($sql, $bindings);

        return [
            'caution_count' => $result->caution_count ?? 0,
            'shortage_count' => $result->shortage_count ?? 0,
        ];
    }
}
