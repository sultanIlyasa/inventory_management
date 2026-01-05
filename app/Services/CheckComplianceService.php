<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Check Compliance Service
 * Shows materials that need to be checked today based on their usage frequency:
 * - DAILY: Should be checked every day
 * - WEEKLY: Should be checked at least once per week
 * - MONTHLY: Should be checked at least once per month
 */
class CheckComplianceService
{
    /**
     * Get materials that need checking today
     */
    public function getComplianceReport(array $filters, int $perPage, int $page): array
    {
        $whereConditions = $this->buildWhereConditions($filters);
        $bindings = $this->buildBindings($filters);

        // Get total count
        $total = $this->getTotalCount($whereConditions, $bindings);

        // Get paginated data
        $data = $this->getPaginatedData($whereConditions, $bindings, $perPage, $page);

        // Get statistics
        $statistics = $this->getStatistics($whereConditions, $bindings);

        // Get filter options
        $filterOptions = $this->getFilterOptions($filters);

        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'last_page' => max(1, (int) ceil($total / $perPage)),
                'per_page' => $perPage,
                'total' => $total,
            ],
            'statistics' => $statistics,
            'filter_options' => $filterOptions,
        ];
    }

    /**
     * Build WHERE conditions
     */
    private function buildWhereConditions(array $filters): array
    {
        $conditions = [];

        // Filter by usage
        if (!empty($filters['usage'])) {
            $conditions[] = "`materials`.`usage` = :usage";
        }

        // Filter by location
        if (!empty($filters['location'])) {
            $conditions[] = "`materials`.`location` = :location";
        }

        // Filter by gentani
        if (!empty($filters['gentani'])) {
            $conditions[] = "`materials`.`gentani` = :gentani";
        }

        // Filter by PIC
        if (!empty($filters['pic'])) {
            $conditions[] = "`materials`.`pic_name` = :pic";
        }

        // Search filter
        if (!empty($filters['search'])) {
            $conditions[] = "(
                `materials`.`material_number` LIKE :search
                OR `materials`.`description` LIKE :search
                OR `materials`.`pic_name` LIKE :search
            )";
        }

        return $conditions;
    }

    /**
     * Build parameter bindings
     */
    private function buildBindings(array $filters): array
    {
        $bindings = [];

        if (!empty($filters['usage'])) {
            $bindings['usage'] = $filters['usage'];
        }

        if (!empty($filters['location'])) {
            $bindings['location'] = $filters['location'];
        }

        if (!empty($filters['gentani'])) {
            $bindings['gentani'] = $filters['gentani'];
        }

        if (!empty($filters['pic'])) {
            $bindings['pic'] = $filters['pic'];
        }

        if (!empty($filters['search'])) {
            $bindings['search'] = '%' . $filters['search'] . '%';
        }

        return $bindings;
    }

    /**
     * Get total count
     */
    private function getTotalCount(array $whereConditions, array $bindings): int
    {
        $whereClause = !empty($whereConditions) ? 'AND ' . implode(' AND ', $whereConditions) : '';

        $sql = "
            SELECT COUNT(*) as total
            FROM (
                SELECT DISTINCT `materials`.`id`
                FROM `materials`
                LEFT JOIN `daily_inputs` ON `daily_inputs`.`material_id` = `materials`.`id`
                    AND `daily_inputs`.`date` = CURDATE()
                LEFT JOIN (
                    SELECT `material_id`, MAX(`date`) as last_check_date
                    FROM `daily_inputs`
                    GROUP BY `material_id`
                ) as last_check ON `materials`.`id` = last_check.`material_id`
                WHERE (
                    (`materials`.`usage` = 'DAILY' AND `daily_inputs`.`id` IS NULL)
                    OR (`materials`.`usage` = 'WEEKLY' AND (last_check.last_check_date IS NULL OR last_check.last_check_date < DATE_SUB(CURDATE(), INTERVAL 7 DAY)))
                    OR (`materials`.`usage` = 'MONTHLY' AND (last_check.last_check_date IS NULL OR last_check.last_check_date < DATE_SUB(CURDATE(), INTERVAL 30 DAY)))
                )
                {$whereClause}
            ) as subquery
        ";

        $result = DB::selectOne($sql, $bindings);
        return $result->total ?? 0;
    }

    /**
     * Get paginated data
     */
    private function getPaginatedData(array $whereConditions, array $bindings, int $perPage, int $page): array
    {
        $whereClause = !empty($whereConditions) ? 'AND ' . implode(' AND ', $whereConditions) : '';
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT
                `materials`.`id`,
                `materials`.`material_number`,
                `materials`.`description`,
                `materials`.`pic_name`,
                `materials`.`usage`,
                `materials`.`location`,
                `materials`.`gentani`,
                `materials`.`stock_minimum`,
                `materials`.`stock_maximum`,
                last_check.last_check_date,
                CASE
                    WHEN last_check.last_check_date IS NULL THEN 'Never Checked'
                    WHEN `materials`.`usage` = 'DAILY' THEN 'Due Today'
                    WHEN `materials`.`usage` = 'WEEKLY' THEN CONCAT('Last: ', DATE_FORMAT(last_check.last_check_date, '%d/%m/%Y'))
                    WHEN `materials`.`usage` = 'MONTHLY' THEN CONCAT('Last: ', DATE_FORMAT(last_check.last_check_date, '%d/%m/%Y'))
                END as check_status,
                CASE
                    WHEN last_check.last_check_date IS NULL THEN 999
                    ELSE DATEDIFF(CURDATE(), last_check.last_check_date)
                END as days_since_check
            FROM `materials`
            LEFT JOIN `daily_inputs` ON `daily_inputs`.`material_id` = `materials`.`id`
                AND `daily_inputs`.`date` = CURDATE()
            LEFT JOIN (
                SELECT `material_id`, MAX(`date`) as last_check_date
                FROM `daily_inputs`
                GROUP BY `material_id`
            ) as last_check ON `materials`.`id` = last_check.`material_id`
            WHERE (
                (`materials`.`usage` = 'DAILY' AND `daily_inputs`.`id` IS NULL)
                OR (`materials`.`usage` = 'WEEKLY' AND (last_check.last_check_date IS NULL OR last_check.last_check_date < DATE_SUB(CURDATE(), INTERVAL 7 DAY)))
                OR (`materials`.`usage` = 'MONTHLY' AND (last_check.last_check_date IS NULL OR last_check.last_check_date < DATE_SUB(CURDATE(), INTERVAL 30 DAY)))
            )
            {$whereClause}
            ORDER BY days_since_check DESC, `materials`.`usage` ASC
            LIMIT :limit OFFSET :offset
        ";

        $dataBindings = array_merge($bindings, [
            'limit' => $perPage,
            'offset' => $offset
        ]);

        $results = DB::select($sql, $dataBindings);

        return collect($results)->map(function ($item, $index) use ($page, $perPage) {
            return [
                'key' => 'compliance-' . $item->material_number,
                'number' => ($page - 1) * $perPage + $index + 1,
                'material_number' => $item->material_number,
                'description' => $item->description,
                'pic_name' => $item->pic_name ?? '-',
                'usage' => $item->usage ?? '-',
                'location' => $item->location ?? '-',
                'gentani' => $item->gentani ?? '-',
                'stock_min' => (int) $item->stock_minimum,
                'stock_max' => (int) $item->stock_maximum,
                'last_check_date' => $item->last_check_date,
                'check_status' => $item->check_status,
                'days_since_check' => (int) $item->days_since_check,
            ];
        })->values()->all();
    }

    /**
     * Get statistics by usage type
     */
    private function getStatistics(array $whereConditions, array $bindings): array
    {
        $whereClause = !empty($whereConditions) ? 'AND ' . implode(' AND ', $whereConditions) : '';

        $sql = "
            SELECT
                `materials`.`usage`,
                COUNT(DISTINCT `materials`.`id`) as total
            FROM `materials`
            LEFT JOIN `daily_inputs` ON `daily_inputs`.`material_id` = `materials`.`id`
                AND `daily_inputs`.`date` = CURDATE()
            LEFT JOIN (
                SELECT `material_id`, MAX(`date`) as last_check_date
                FROM `daily_inputs`
                GROUP BY `material_id`
            ) as last_check ON `materials`.`id` = last_check.`material_id`
            WHERE (
                (`materials`.`usage` = 'DAILY' AND `daily_inputs`.`id` IS NULL)
                OR (`materials`.`usage` = 'WEEKLY' AND (last_check.last_check_date IS NULL OR last_check.last_check_date < DATE_SUB(CURDATE(), INTERVAL 7 DAY)))
                OR (`materials`.`usage` = 'MONTHLY' AND (last_check.last_check_date IS NULL OR last_check.last_check_date < DATE_SUB(CURDATE(), INTERVAL 30 DAY)))
            )
            {$whereClause}
            GROUP BY `materials`.`usage`
        ";

        $results = DB::select($sql, $bindings);
        $stats = collect($results)->pluck('total', 'usage')->all();

        return [
            'DAILY' => (int) ($stats['DAILY'] ?? 0),
            'WEEKLY' => (int) ($stats['WEEKLY'] ?? 0),
            'MONTHLY' => (int) ($stats['MONTHLY'] ?? 0),
            'TOTAL' => array_sum($stats),
        ];
    }

    /**
     * Get filter options
     */
    private function getFilterOptions(array $filters): array
    {
        $whereConditions = [];
        $bindings = [];

        if (!empty($filters['usage'])) {
            $whereConditions[] = "`usage` = :usage";
            $bindings['usage'] = $filters['usage'];
        }

        if (!empty($filters['location'])) {
            $whereConditions[] = "`location` = :location";
            $bindings['location'] = $filters['location'];
        }

        if (!empty($filters['gentani'])) {
            $whereConditions[] = "`gentani` = :gentani";
            $bindings['gentani'] = $filters['gentani'];
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        return [
            'pics' => DB::select("
                SELECT DISTINCT `pic_name`
                FROM `materials`
                {$whereClause}
                ORDER BY `pic_name`
            ", $bindings),
            'locations' => DB::select("
                SELECT DISTINCT `location`
                FROM `materials`
                {$whereClause}
                ORDER BY `location`
            ", $bindings),
            'usages' => DB::select("
                SELECT DISTINCT `usage`
                FROM `materials`
                {$whereClause}
                ORDER BY `usage`
            ", $bindings),
            'gentani' => DB::select("
                SELECT DISTINCT `gentani`
                FROM `materials`
                {$whereClause}
                ORDER BY `gentani`
            ", $bindings),
        ];
    }
}
