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
        $total = (clone $baseQuery)->count();

        $paginator = (clone $baseQuery)
            ->orderByDesc('recovery_days')
            ->orderBy('materials.material_number')
            ->cursorPaginate($perPage);

        $data = collect($paginator->items())->map(function ($item) {
            return [
                'material_number' => $item->material_number,
                'description' => $item->description,
                'pic' => $item->pic_name ?? '-',
                'instock' => $item->instock,
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
        $trendData = collect();

        for ($month = 1; $month <= 12; $month++) {
            $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();

            if ($startOfMonth->isFuture()) {
                break;
            }

            $monthKey = $startOfMonth->format('Y-m');
            $monthlyQuery = $this->buildBaseQuery(['month' => $monthKey]);
            $statsBase = DB::query()->fromSub($monthlyQuery, 'monthly_recovery');
            $totalRecovered = (clone $statsBase)->count();
            $averageDays = $totalRecovered > 0
                ? (clone $statsBase)->avg('monthly_recovery.recovery_days')
                : 0;

            $trendData->push([
                'month' => $month,
                'average_recovery_days' => round((float) $averageDays, 1),
                'total_recovered' => $totalRecovered,
            ]);
        }

        return $trendData;
    }

    private function buildBaseQuery(array $filters): Builder
    {
        $latestOk = DB::table('daily_inputs')
            ->select([
                'material_id',
                DB::raw('MAX(date) as recovery_date'),
            ])
            ->where('status', 'OK')
            ->groupBy('material_id');

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
                'ok.recovery_date'
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
