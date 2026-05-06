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
        $whereConditions = ["latest.status = :status"];
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
        $currentStatusCte = $this->currentStatusCte();

        // Step 2: Get total count (for pagination)
        $countSql = "
            {$currentStatusCte}
            SELECT COUNT(DISTINCT materials.id) as total
            FROM materials
            INNER JOIN latest ON materials.id = latest.material_id
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
                COALESCE(streaks.days, 0) as days,
                latest.daily_stock as current_stock
            FROM materials
            INNER JOIN latest ON materials.id = latest.material_id
            LEFT JOIN streaks ON materials.id = streaks.material_id
            WHERE {$whereClause}
            ORDER BY days DESC
            LIMIT :limit OFFSET :offset
        ";
        $dataSql = $currentStatusCte . $dataSql;

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
        $whereConditions = ["latest.status = :status"];
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
        $currentStatusCte = $this->currentStatusCte();

        // Query to get statistics
        $statsSql = "
            {$currentStatusCte}
            SELECT
                AVG(COALESCE(streaks.days, 0)) as avg_days,
                MAX(COALESCE(streaks.days, 0)) as max_days,
                MIN(COALESCE(streaks.days, 0)) as min_days
            FROM materials
            INNER JOIN latest ON materials.id = latest.material_id
            LEFT JOIN streaks ON materials.id = streaks.material_id
            WHERE {$whereClause}
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
        $whereConditions = ["latest.status = :status"];
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
        $currentStatusCte = $this->currentStatusCte();

        $sql = "
            {$currentStatusCte}
            SELECT
                materials.id,
                materials.material_number,
                materials.description,
                materials.pic_name ,
                materials.usage,
                materials.location,
                materials.gentani,
                COALESCE(streaks.days, 0) as days,
                latest.daily_stock as current_stock
            FROM materials
            INNER JOIN latest ON materials.id = latest.material_id
            LEFT JOIN streaks ON materials.id = streaks.material_id
            WHERE {$whereClause}
            ORDER BY days DESC
            LIMIT :limit
        ";

        $bindings['limit'] = $limit;

        return DB::select($sql, $bindings);
    }

    /**
     * Get top N SHORTAGE materials sorted ASC by days (fewest days = fastest to critical)
     */
    public function getFastestToCritical(array $filters = [], int $limit = 5): array
    {
        $whereConditions = ["latest.status = :status"];
        $bindings = ['status' => 'SHORTAGE'];

        if (!empty($filters['usage']))    { $whereConditions[] = "materials.usage = :usage";       $bindings['usage']    = $filters['usage']; }
        if (!empty($filters['location'])) { $whereConditions[] = "materials.location = :location"; $bindings['location'] = $filters['location']; }
        if (!empty($filters['gentani']))  { $whereConditions[] = "materials.gentani = :gentani";   $bindings['gentani']  = $filters['gentani']; }
        if (!empty($filters['pic_name'])) { $whereConditions[] = "materials.pic_name = :pic_name"; $bindings['pic_name'] = $filters['pic_name']; }

        $whereClause = implode(' AND ', $whereConditions);
        $currentStatusCte = $this->currentStatusCte();

        // Fetch enough candidates to find $limit distinct days values
        $fetchLimit = $limit * 20;
        $sql = "
            {$currentStatusCte}
            SELECT materials.id, materials.material_number, materials.description,
                   materials.pic_name, materials.usage, materials.location, materials.gentani,
                   COALESCE(streaks.days, 0) as days,
                   latest.daily_stock as current_stock
            FROM materials
            INNER JOIN latest ON materials.id = latest.material_id
            LEFT JOIN streaks ON materials.id = streaks.material_id
            WHERE {$whereClause}
            ORDER BY days ASC
            LIMIT :limit
        ";
        $bindings['limit'] = $fetchLimit;
        $rows = DB::select($sql, $bindings);

        // Keep only the first material per unique days value to show runway variety
        $seen = [];
        $result = [];
        foreach ($rows as $row) {
            if (!isset($seen[$row->days])) {
                $seen[$row->days] = true;
                $result[] = $row;
                if (count($result) >= $limit) break;
            }
        }
        return $result;
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
        $currentStatusCte = $this->currentStatusCte();

        $sql = "
            {$currentStatusCte}
            SELECT
                SUM(CASE WHEN latest.status = 'CAUTION' THEN 1 ELSE 0 END) as caution_count,
                SUM(CASE WHEN latest.status = 'SHORTAGE' THEN 1 ELSE 0 END) as shortage_count
            FROM materials
            INNER JOIN latest ON materials.id = latest.material_id
            WHERE 1=1 {$whereClause}
        ";

        $result = DB::selectOne($sql, $bindings);

        return [
            'caution_count' => $result->caution_count ?? 0,
            'shortage_count' => $result->shortage_count ?? 0,
        ];
    }

    private function currentStatusCte(): string
    {
        return "
            WITH ranked_by_day AS (
                SELECT
                    daily_inputs.id,
                    daily_inputs.material_id,
                    daily_inputs.date,
                    daily_inputs.daily_stock,
                    daily_inputs.status,
                    ROW_NUMBER() OVER (
                        PARTITION BY daily_inputs.material_id, daily_inputs.date
                        ORDER BY daily_inputs.id DESC
                    ) as day_rank
                FROM daily_inputs
            ),
            deduped_inputs AS (
                SELECT id, material_id, date, daily_stock, status
                FROM ranked_by_day
                WHERE day_rank = 1
            ),
            latest_ranked AS (
                SELECT
                    deduped_inputs.*,
                    ROW_NUMBER() OVER (
                        PARTITION BY deduped_inputs.material_id
                        ORDER BY deduped_inputs.date DESC, deduped_inputs.id DESC
                    ) as latest_rank
                FROM deduped_inputs
            ),
            latest AS (
                SELECT *
                FROM latest_ranked
                WHERE latest_rank = 1
            ),
            streak_scan AS (
                SELECT
                    deduped_inputs.material_id,
                    deduped_inputs.status,
                    latest.status as latest_status,
                    SUM(CASE WHEN deduped_inputs.status <> latest.status THEN 1 ELSE 0 END) OVER (
                        PARTITION BY deduped_inputs.material_id
                        ORDER BY deduped_inputs.date DESC, deduped_inputs.id DESC
                        ROWS BETWEEN UNBOUNDED PRECEDING AND 1 PRECEDING
                    ) as previous_status_breaks
                FROM deduped_inputs
                INNER JOIN latest ON latest.material_id = deduped_inputs.material_id
            ),
            streaks AS (
                SELECT
                    material_id,
                    COUNT(*) as days
                FROM streak_scan
                WHERE status = latest_status
                    AND COALESCE(previous_status_breaks, 0) = 0
                GROUP BY material_id
            )
        ";
    }
}
