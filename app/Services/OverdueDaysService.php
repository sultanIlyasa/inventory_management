<?php

namespace App\Services;

use App\Models\DailyInput;
use App\Models\Materials;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class OverdueDaysService
{
    public function getOverdueReports(array $filters, int $perPage, int $page): array
    {
        // Build base query WITHOUT sorting
        $baseQuery = $this->buildBaseQuery($filters);
        $total = (clone $baseQuery)->count();

        // Apply sorting only for the main data query
        $sortField = $filters['sortField'] ?? 'status';
        $sortDirection = strtolower($filters['sortDirection'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $orderedQuery = $this->applySort(clone $baseQuery, $sortField, $sortDirection);
        $paginator = $orderedQuery->cursorPaginate($perPage);

        $data = collect($paginator->items())
            ->values()
            ->map(function ($item, $index) {
                return [
                    'key' => 'report-' . $item->material_number,
                    'number' => $index + 1,
                    'material_number' => $item->material_number,
                    'description' => $item->description,
                    'pic_name' => $item->pic_name ?? '-',
                    'instock' => $item->instock,
                    'status' => $item->status,
                    'days' => (int) $item->consecutive_days,
                    'location' => $item->location ?? '-',
                    'usage' => $item->usage ?? '-',
                    'gentani' => $item->gentani ?? '-',
                ];
            });

        // Use UNORDERED base query for stats and filters
        $statistics = $this->getStatusStatistics($baseQuery);
        $filterOptions = $this->getFilterOptions($baseQuery);

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

    private function buildBaseQuery(array $filters): Builder
    {
        $latestInputs = $this->latestStatusSubquery($filters);

        $query = Materials::query()
            ->applyFilters($filters)
            ->joinSub($latestInputs, 'latest_inputs', function ($join) {
                $join->on('latest_inputs.material_id', '=', 'materials.id');
            })
            ->whereIn('latest_inputs.status', ['SHORTAGE', 'CAUTION', 'OVERFLOW'])
            ->select([
                'materials.id',
                'materials.material_number',
                'materials.description',
                'materials.pic_name',
                'materials.usage',
                'materials.location',
                'materials.gentani',
                'latest_inputs.daily_stock as instock',
                'latest_inputs.status',
                DB::raw('(' . $this->consecutiveDaysSql() . ') as consecutive_days'),
                DB::raw('(' . $this->statusPrioritySql() . ') as status_priority'),
            ])
            ->when(!empty($filters['status']), function ($query) use ($filters) {
                $query->where('latest_inputs.status', $filters['status']);
            })
            ->when(!empty($filters['pic']), function ($query) use ($filters) {
                $query->where('materials.pic_name', $filters['pic']);
            })
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = '%' . $filters['search'] . '%';
                $query->where(function ($inner) use ($search) {
                    $inner->where('materials.material_number', 'like', $search)
                        ->orWhere('materials.description', 'like', $search)
                        ->orWhere('materials.pic_name', 'like', $search)
                        ->orWhere('latest_inputs.status', 'like', $search);
                });
            });

        return $query->toBase();
    }

    private function latestStatusSubquery(array $filters): Builder
    {
        $dateFilter = $filters['date'] ?? null;

        return DailyInput::query()
            ->select([
                'material_id',
                'daily_stock',
                'status',
                'date',
                'id',
            ])
            ->when($dateFilter, fn($query) => $query->where('date', '<=', $dateFilter))
            ->whereIn('id', function ($query) use ($dateFilter) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('daily_inputs as di2')
                    ->when($dateFilter, fn($sub) => $sub->where('di2.date', '<=', $dateFilter))
                    ->whereColumn('di2.material_id', 'daily_inputs.material_id');
            })
            ->toBase();
    }

    private function applySort(Builder $query, string $field, string $direction): Builder
    {
        if ($field === 'days') {
            return $query
                ->orderBy('consecutive_days', $direction)
                ->orderBy('status_priority', 'asc');
        }

        return $query
            ->orderBy('status_priority', $direction)
            ->orderBy('consecutive_days', 'desc');
    }

    private function getStatusStatistics(Builder $baseQuery): array
    {
        // Clone and select only status column
        $stats = (clone $baseQuery)
            ->select('latest_inputs.status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('latest_inputs.status')
            ->pluck('total', 'status');

        return [
            'SHORTAGE' => (int) ($stats['SHORTAGE'] ?? 0),
            'CAUTION' => (int) ($stats['CAUTION'] ?? 0),
            'OVERFLOW' => (int) ($stats['OVERFLOW'] ?? 0),
        ];
    }

    private function getFilterOptions(Builder $baseQuery): array
    {
        // Get distinct values without the complex columns
        $simpleQuery = Materials::query()
            ->applyFilters(request()->only(['usage', 'location', 'gentani']))
            ->joinSub($this->latestStatusSubquery([]), 'latest_inputs', function ($join) {
                $join->on('latest_inputs.material_id', '=', 'materials.id');
            })
            ->whereIn('latest_inputs.status', ['SHORTAGE', 'CAUTION', 'OVERFLOW'])
            ->toBase();

        return [
            'pics' => (clone $simpleQuery)
                ->distinct()
                ->orderBy('materials.pic_name')
                ->pluck('materials.pic_name')
                ->filter()
                ->values()
                ->all(),
            'locations' => (clone $simpleQuery)
                ->distinct()
                ->orderBy('materials.location')
                ->pluck('materials.location')
                ->filter()
                ->values()
                ->all(),
            'usages' => (clone $simpleQuery)
                ->distinct()
                ->orderBy('materials.usage')
                ->pluck('materials.usage')
                ->filter()
                ->values()
                ->all(),
            'gentani' => (clone $simpleQuery)
                ->distinct()
                ->orderBy('materials.gentani')
                ->pluck('materials.gentani')
                ->filter()
                ->values()
                ->all(),
        ];
    }

    private function consecutiveDaysSql(): string
    {
        return "
            SELECT COUNT(*)
            FROM daily_inputs di
            WHERE di.material_id = materials.id
              AND di.status = latest_inputs.status
              AND di.date <= latest_inputs.date
              AND DAYOFWEEK(di.date) NOT IN (1, 7)
              AND NOT EXISTS (
                  SELECT 1
                  FROM daily_inputs di2
                  WHERE di2.material_id = di.material_id
                    AND di2.date > di.date
                    AND di2.date <= latest_inputs.date
                    AND di2.status != latest_inputs.status
              )
        ";
    }

    private function statusPrioritySql(): string
    {
        return "
            CASE latest_inputs.status
                WHEN 'SHORTAGE' THEN 1
                WHEN 'CAUTION' THEN 2
                WHEN 'OVERFLOW' THEN 3
                WHEN 'OK' THEN 4
                ELSE 5
            END
        ";
    }
}
