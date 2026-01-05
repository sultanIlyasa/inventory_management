<?php

namespace App\Services;

use App\Models\DailyInput;
use App\Models\Materials;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecoveryDaysService
{
    public function getRecoveryReport(array $filters, int $perPage, int $page): array
    {
        $baseQuery = $this->buildBaseQuery($filters);

        // Wrap in subquery to handle pagination correctly with groupBy
        $subquery = DB::query()->fromSub($baseQuery, 'recovery_base')
            ->orderByDesc('recovery_days')
            ->orderBy('material_number');

        $total = (clone $subquery)->count();

        // Apply pagination with offset and limit
        $offset = ($page - 1) * $perPage;
        $items = (clone $subquery)
            ->offset($offset)
            ->limit($perPage)
            ->get();

        $data = collect($items)->map(function ($item) {
            return [
                'material_number' => $item->material_number,
                'description' => $item->description,
                'pic' => $item->pic_name ?? '-',
                'instock' => $item->instock ?? 0,
                'current_status' => 'OK',
                'recovery_days' => (int) $item->recovery_days,
                'last_problem_date' => $item->last_problem_date,
                'recovery_date' => $item->recovery_date,
                'usage' => $item->usage ?? '-',
                'location' => $item->location ?? '-',
                'gentani' => $item->gentani ?? '-',
            ];
        })->values();


        $statsQuery = DB::query()->fromSub(clone $baseQuery, 'recovery_stats');

        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'last_page' => max(1, (int) ceil($total / $perPage)),
                'per_page' => $perPage,
                'total' => $total,
            ],
            'statistics' => [
                'total_recovered' => (clone $statsQuery)->count(),
                'average_recovery_days' => round((float) (clone $statsQuery)->avg('recovery_days'), 1),
                'fastest_recovery' => (int) (clone $statsQuery)->min('recovery_days'),
                'slowest_recovery' => (int) (clone $statsQuery)->max('recovery_days'),
            ],
        ];
    }

    public function getRecoveryTrend(int $year): Collection
    {
        $subquery = $this->buildBaseQuery(['year' => $year]);

        return DB::query()
            ->fromSub($subquery, 'recovery')
            ->select([
                DB::raw('MONTH(recovery.recovery_date) as month'),
                DB::raw('AVG(recovery.recovery_days) as average_recovery_days'),
                DB::raw('COUNT(*) as total_recovered')
            ])
            ->groupBy(DB::raw('MONTH(recovery.recovery_date)'))
            ->orderBy('month')
            ->get();
    }


    private function buildBaseQuery(array $filters): Builder
    {
        $latestOk = DB::table('daily_inputs')
            ->select([
                'material_id',
                'daily_stock',
                DB::raw('MAX(date) as recovery_date'),
            ])
            ->where('status', 'OK')
            ->groupBy('material_id', 'daily_stock');

        $query = Materials::query()
            ->applyFilters($filters)
            ->joinSub($latestOk, 'ok', function ($join) {
                $join->on('materials.id', '=', 'ok.material_id');
            })
            ->leftJoin('daily_inputs as problem', function ($join) {
                $join->on('problem.material_id', '=', 'materials.id')
                    ->whereIn('problem.status', ['CAUTION', 'SHORTAGE'])
                    ->whereColumn('problem.date', '<=', 'ok.recovery_date');
            })
            ->select([
                'materials.material_number',
                'materials.description',
                'materials.pic_name',
                'materials.usage',
                'materials.location',
                'materials.gentani',
                DB::raw('ok.recovery_date'),
                DB::raw('ok.daily_stock as instock'),
                DB::raw('MAX(problem.date) as last_problem_date'),
                DB::raw('DATEDIFF(ok.recovery_date, MAX(problem.date)) + 1 as recovery_days'),
            ])
            ->groupBy(
                'materials.id',
                'materials.material_number',
                'materials.description',
                'materials.pic_name',
                'materials.usage',
                'materials.location',
                'materials.gentani',
                'ok.recovery_date',
                'ok.daily_stock'
            )
            ->havingNotNull('last_problem_date')
            ->having('recovery_days', '>', 0);

        if (!empty($filters['month'])) {
            $start = Carbon::parse($filters['month'])->startOfMonth()->toDateString();
            $end = Carbon::parse($filters['month'])->endOfMonth()->toDateString();
            $query->whereBetween('ok.recovery_date', [$start, $end]);
        }

        return $query;
    }
}
