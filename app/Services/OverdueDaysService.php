<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * SIMPLE Overdue Days Service
 * Tracks materials in problem status (SHORTAGE, CAUTION, OVERFLOW, UNCHECKED)
 * and counts consecutive weekdays they've been in that status
 */
class OverdueDaysService
{
    /**
     * Get overdue reports with pagination
     */
    public function getOverdueReports(array $filters, int $perPage, int $page): array
    {
        // Step 1: Build WHERE conditions
        $whereConditions = $this->buildWhereConditions($filters);
        $bindings = $this->buildBindings($filters);

        // Step 2: Get total count
        $total = $this->getTotalCount($whereConditions, $bindings, $filters);

        // Step 3: Get paginated data
        $data = $this->getPaginatedData($whereConditions, $bindings, $filters, $perPage, $page);

        // Step 4: Get statistics
        $statistics = $this->getStatistics($whereConditions, $bindings, $filters);

        // Step 5: Get filter options
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
     * Build WHERE conditions array
     */
    private function buildWhereConditions(array $filters): array
    {
        $conditions = [];

        // Filter by status (SHORTAGE, CAUTION, OVERFLOW, or UNCHECKED)
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'UNCHECKED') {
                $conditions[] = "latest_status.status IS NULL";
            } else {
                $conditions[] = "latest_status.status = :status";
            }
        } else {
            // Show all problem statuses by default
            $conditions[] = "(latest_status.status IN ('SHORTAGE', 'CAUTION', 'OVERFLOW') OR latest_status.status IS NULL)";
        }

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
                OR `latest_status`.`status` LIKE :search
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

        if (!empty($filters['status']) && $filters['status'] !== 'UNCHECKED') {
            $bindings['status'] = $filters['status'];
        }

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

        if (!empty($filters['date'])) {
            $bindings['date'] = $filters['date'];
        }

        return $bindings;
    }

    /**
     * Get total count for pagination
     */
    private function getTotalCount(array $whereConditions, array $bindings, array $filters): int
    {
        $whereClause = implode(' AND ', $whereConditions);
        $dateCondition = !empty($filters['date']) ? "AND di.date <= :date" : "";

        $sql = "
            SELECT COUNT(DISTINCT materials.id) as total
            FROM materials
            LEFT JOIN (
                SELECT
                    di.material_id,
                    di.status,
                    di.daily_stock,
                    di.date
                FROM daily_inputs di
                WHERE di.id IN (
                    SELECT MAX(id)
                    FROM daily_inputs
                    WHERE 1=1 {$dateCondition}
                    GROUP BY material_id
                )
            ) as latest_status ON materials.id = latest_status.material_id
            WHERE {$whereClause}
        ";

        $result = DB::selectOne($sql, $bindings);
        return $result->total ?? 0;
    }

    /**
     * Get paginated data with consecutive days calculation
     */
    private function getPaginatedData(array $whereConditions, array $bindings, array $filters, int $perPage, int $page): array
    {
        $whereClause = implode(' AND ', $whereConditions);
        $dateCondition = !empty($filters['date']) ? "AND di.date <= :date" : "";
        $offset = ($page - 1) * $perPage;

        // Determine sort order
        $sortField = $filters['sortField'] ?? 'status';
        $sortDirection = strtoupper($filters['sortDirection'] ?? 'DESC');

        $orderBy = match($sortField) {
            'days' => "consecutive_days {$sortDirection}, status_priority ASC",
            'last_updated' => "last_updated_date {$sortDirection}, status_priority ASC",
            default => "status_priority {$sortDirection}, consecutive_days DESC"
        };

        $sql = "
            SELECT
                materials.id,
                materials.material_number,
                materials.description,
                materials.pic_name,
                materials.usage,
                materials.location,
                materials.gentani,
                COALESCE(latest_status.daily_stock, 0) as instock,
                COALESCE(latest_status.status, 'UNCHECKED') as status,
                latest_status.date as last_updated_date,
                COALESCE(consecutive_days.days, 0) as consecutive_days,
                CASE COALESCE(latest_status.status, 'UNCHECKED')
                    WHEN 'SHORTAGE' THEN 1
                    WHEN 'CAUTION' THEN 2
                    WHEN 'OVERFLOW' THEN 3
                    WHEN 'UNCHECKED' THEN 4
                    ELSE 5
                END as status_priority
            FROM materials
            LEFT JOIN (
                SELECT
                    di.material_id,
                    di.status,
                    di.daily_stock,
                    di.date
                FROM daily_inputs di
                WHERE di.id IN (
                    SELECT MAX(id)
                    FROM daily_inputs
                    WHERE 1=1 {$dateCondition}
                    GROUP BY material_id
                )
            ) as latest_status ON materials.id = latest_status.material_id
            LEFT JOIN (
                SELECT
                    di.material_id,
                    CASE
                        WHEN di.date IS NULL THEN 0
                        ELSE (
                            DATEDIFF(CURDATE(), di.date)
                            - (WEEK(CURDATE()) - WEEK(di.date)) * 2
                            - CASE WHEN DAYOFWEEK(di.date) = 1 THEN 1 ELSE 0 END
                            - CASE WHEN DAYOFWEEK(CURDATE()) = 7 THEN 1 ELSE 0 END
                        )
                    END as days
                FROM daily_inputs di
                WHERE di.id IN (
                    SELECT MAX(id)
                    FROM daily_inputs
                    WHERE 1=1 {$dateCondition}
                    GROUP BY material_id
                )
            ) as consecutive_days ON materials.id = consecutive_days.material_id
            WHERE {$whereClause}
            ORDER BY {$orderBy}
            LIMIT :limit OFFSET :offset
        ";

        $dataBindings = array_merge($bindings, [
            'limit' => $perPage,
            'offset' => $offset
        ]);

        $results = DB::select($sql, $dataBindings);

        // Format data
        return collect($results)->map(function ($item, $index) use ($page, $perPage) {
            return [
                'key' => 'report-' . $item->material_number,
                'number' => ($page - 1) * $perPage + $index + 1,
                'material_number' => $item->material_number,
                'description' => $item->description,
                'pic_name' => $item->pic_name ?? '-',
                'instock' => (int) $item->instock,
                'status' => $item->status,
                'days' => (int) $item->consecutive_days,
                'last_updated' => $item->last_updated_date ?? null,
                'location' => $item->location ?? '-',
                'usage' => $item->usage ?? '-',
                'gentani' => $item->gentani ?? '-',
            ];
        })->values()->all();
    }

    /**
     * Get statistics by status
     */
    private function getStatistics(array $whereConditions, array $bindings, array $filters): array
    {
        $whereClause = implode(' AND ', $whereConditions);
        $dateCondition = !empty($filters['date']) ? "AND di.date <= :date" : "";

        $sql = "
            SELECT
                COALESCE(latest_status.status, 'UNCHECKED') as status,
                COUNT(DISTINCT materials.id) as total
            FROM materials
            LEFT JOIN (
                SELECT
                    di.material_id,
                    di.status
                FROM daily_inputs di
                WHERE di.id IN (
                    SELECT MAX(id)
                    FROM daily_inputs
                    WHERE 1=1 {$dateCondition}
                    GROUP BY material_id
                )
            ) as latest_status ON materials.id = latest_status.material_id
            WHERE {$whereClause}
            GROUP BY COALESCE(latest_status.status, 'UNCHECKED')
        ";

        $results = DB::select($sql, $bindings);
        $stats = collect($results)->pluck('total', 'status')->all();

        return [
            'SHORTAGE' => (int) ($stats['SHORTAGE'] ?? 0),
            'CAUTION' => (int) ($stats['CAUTION'] ?? 0),
            'OVERFLOW' => (int) ($stats['OVERFLOW'] ?? 0),
            'UNCHECKED' => (int) ($stats['UNCHECKED'] ?? 0),
        ];
    }

    /**
     * Get filter options (PICs, locations, usages, gentani)
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
