<?php

namespace App\Services;

use App\Models\Materials;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;


class StatusChangeService
{
    public function getStatusChangeReport(array $filters, int $perPage, int $page): array
    {
        $baseQuery = $this->buildBaseQuery($filters);
        $total = (clone $baseQuery)->count();

        $paginator = (clone $baseQuery)
            ->orderByDesc('changes.total_from_ok')
            ->cursorPaginate($perPage);

        $data = collect($paginator->items())->map(function ($item) {
            return [
                'material_number' => $item->material_number,
                'description' => $item->description,
                'pic' => $item->pic_name ?? '-',
                'usage' => $item->usage ?? '-',
                'location' => $item->location ?? '-',
                'gentani' => $item->gentani ?? '-',
                'current_status' => $item->current_status ?? '-',
                'frequency_changes' => [
                    'ok_to_caution' => (int) $item->ok_to_caution,
                    'ok_to_shortage' => (int) $item->ok_to_shortage,
                    'ok_to_overflow' => (int) $item->ok_to_overflow,
                    'caution_to_ok' => (int) $item->caution_to_ok,
                    'shortage_to_ok' => (int) $item->shortage_to_ok,
                    'total_from_ok' => (int) $item->total_from_ok,
                    'total_to_ok' => (int) $item->total_to_ok,
                ],
            ];
        })->values();

        $statsRow = Cache::remember(
            'status_change_stats:' . md5(json_encode($filters)),
            now()->addMinutes(10),
            function () use ($baseQuery) {
                return DB::query()
                    ->fromSub($baseQuery, 'stats')
                    ->selectRaw('
                SUM(stats.ok_to_caution) as total_to_caution,
                SUM(stats.ok_to_shortage) as total_to_shortage,
                SUM(stats.ok_to_overflow) as total_to_overflow,
                SUM(stats.total_to_ok) as total_recovered,
                SUM(stats.total_from_ok) as total_changes
            ')
                    ->first();
            }
        );


        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'last_page' => max(1, (int) ceil($total / $perPage)),
                'per_page' => $perPage,
                'total' => $total,
            ],
            'statistics' => [
                'total_materials' => $total,
                'total_to_caution' => (int) ($statsRow->total_to_caution ?? 0),
                'total_to_shortage' => (int) ($statsRow->total_to_shortage ?? 0),
                'total_to_overflow' => (int) ($statsRow->total_to_overflow ?? 0),
                'total_recovered' => (int) ($statsRow->total_recovered ?? 0),
                'total_changes' => (int) ($statsRow->total_changes ?? 0),
            ],
        ];
    }

    private function buildBaseQuery(array $filters): Builder
    {
        $transitionsSub = $this->statusTransitionsSubquery($filters);
        $latestStatusSub = $this->latestStatusSubquery($filters);

        $query = Materials::query()
            ->applyFilters($filters)
            ->joinSub($transitionsSub, 'changes', function ($join) {
                $join->on('changes.material_id', '=', 'materials.id');
            })
            ->joinSub($latestStatusSub, 'latest', function ($join) {
                $join->on('latest.material_id', '=', 'materials.id');
            })

            ->select([
                'materials.material_number',
                'materials.description',
                'materials.pic_name',
                'materials.usage',
                'materials.location',
                'materials.gentani',
                'latest.current_status',
                'changes.ok_to_caution',
                'changes.ok_to_shortage',
                'changes.ok_to_overflow',
                'changes.caution_to_ok',
                'changes.shortage_to_ok',
                'changes.total_from_ok',
                'changes.total_to_ok',
            ])
            ->where(function ($query) {
                $query->where('changes.total_from_ok', '>', 0)
                    ->orWhere('changes.total_to_ok', '>', 0);
            });

        return $query->toBase();
    }


    private function statusTransitionsSubquery(array $filters): Builder
    {
        $historySub = $this->statusHistorySubquery($filters);

        return DB::query()
            ->fromSub($historySub, 'history')
            ->select([
                'history.material_id',
                DB::raw("SUM(CASE WHEN history.prev_status = 'OK' AND history.status = 'CAUTION' THEN 1 ELSE 0 END) as ok_to_caution"),
                DB::raw("SUM(CASE WHEN history.prev_status = 'OK' AND history.status = 'SHORTAGE' THEN 1 ELSE 0 END) as ok_to_shortage"),
                DB::raw("SUM(CASE WHEN history.prev_status = 'OK' AND history.status = 'OVERFLOW' THEN 1 ELSE 0 END) as ok_to_overflow"),
                DB::raw("SUM(CASE WHEN history.prev_status = 'CAUTION' AND history.status = 'OK' THEN 1 ELSE 0 END) as caution_to_ok"),
                DB::raw("SUM(CASE WHEN history.prev_status = 'SHORTAGE' AND history.status = 'OK' THEN 1 ELSE 0 END) as shortage_to_ok"),
                DB::raw("SUM(CASE WHEN history.prev_status = 'OK' AND history.status != 'OK' THEN 1 ELSE 0 END) as total_from_ok"),
                DB::raw("SUM(CASE WHEN history.status = 'OK' AND history.prev_status IS NOT NULL AND history.prev_status != 'OK' THEN 1 ELSE 0 END) as total_to_ok"),
            ])
            ->groupBy('history.material_id');
    }

    private function statusHistorySubquery(array $filters): Builder
    {
        $query = DB::table('daily_inputs')
            ->select([
                'daily_inputs.material_id',
                'daily_inputs.status',
                DB::raw("LAG(daily_inputs.status) OVER (PARTITION BY daily_inputs.material_id ORDER BY daily_inputs.date, daily_inputs.id) as prev_status"),
            ])
            ->join('materials', 'materials.id', '=', 'daily_inputs.material_id')
            ->when(!empty($filters['month']), function ($query) use ($filters) {
                $start = Carbon::parse($filters['month'])->startOfMonth()->toDateString();
                $end = Carbon::parse($filters['month'])->endOfMonth()->toDateString();
                $query->whereBetween('daily_inputs.date', [$start, $end]);
            })
            ->when(!empty($filters['date']) && empty($filters['month']), function ($query) use ($filters) {
                $query->where('daily_inputs.date', '<=', $filters['date']);
            });

        $this->applyMaterialFilters($query, $filters);

        return $query;
    }

    private function applyMaterialFilters($query, array $filters): void
    {
        if (!empty($filters['usage'])) {
            $usages = is_array($filters['usage'])
                ? $filters['usage']
                : array_map('trim', explode(',', $filters['usage']));
            $query->whereIn('materials.usage', $usages);
        }

        if (!empty($filters['location'])) {
            $locations = is_array($filters['location'])
                ? $filters['location']
                : array_map('trim', explode(',', $filters['location']));
            $query->whereIn('materials.location', $locations);
        }

        if (!empty($filters['gentani'])) {
            $gentani = is_array($filters['gentani'])
                ? $filters['gentani']
                : array_map('trim', explode(',', $filters['gentani']));
            $query->whereIn('materials.gentani', $gentani);
        }
        if (!empty($filters['pic_name'])) {
            $gentani = is_array($filters['pic_name'])
                ? $filters['pic_name']
                : array_map('trim', explode(',', $filters['pic_name']));
            $query->whereIn('materials.pic_name', $gentani);
        }
    }

    private function latestStatusSubquery(array $filters)
    {
        return DB::table('daily_inputs')
            ->select([
                'daily_inputs.material_id',
                'daily_inputs.status as current_status',
            ])
            ->whereIn('daily_inputs.id', function ($query) use ($filters) {
                $query->select(DB::raw('MAX(di2.id)'))
                    ->from('daily_inputs as di2')
                    ->whereColumn('di2.material_id', 'daily_inputs.material_id');

                if (!empty($filters['date'])) {
                    $query->where('di2.date', '<=', $filters['date']);
                }
            });
    }
}
