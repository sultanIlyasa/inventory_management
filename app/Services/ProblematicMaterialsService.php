<?php

namespace App\Services;

use App\Models\ProblematicMaterials;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProblematicMaterialsService
{
    private const SYNC_CACHE_KEY = 'problematic_materials_synced';
    private const SYNC_TTL       = 300; // 5 minutes

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Sync the table (if stale) then return a paginated result from the model.
     */
    public function getProblematicMaterials(int $page = 1, int $perPage = 5, array $filters = []): LengthAwarePaginator
    {
        $this->sync();

        $paginator = ProblematicMaterials::query()
            ->when($filters['status'] ?? null, fn($q, $status) => $q->where('status', $status))
            ->when($filters['usage'] ?? null, fn($q, $usage) => $q->where('usage', $usage))
            ->when($filters['location'] ?? null, fn($q, $location) => $q->where('location', $location))
            ->when($filters['gentani'] ?? null, fn($q, $gentani) => $q->where('gentani', $gentani))
            ->when($filters['month'] ?? null, function ($q, $month) {
                $start = Carbon::parse($month)->startOfMonth()->toDateString();
                $end = Carbon::parse($month)->endOfMonth()->toDateString();

                $q->whereBetween('last_updated', [$start, $end]);
            })
            ->orderBy('status_priority')
            ->orderByRaw("
                CASE severity
                    WHEN 'Line-Stop' THEN 4
                    WHEN 'High' THEN 3
                    WHEN 'Medium' THEN 2
                    ELSE 1
                END DESC
            ")
            ->orderByRaw('CASE WHEN coverage_shifts IS NULL THEN 1 ELSE 0 END')
            ->orderBy('coverage_shifts')
            ->orderByDesc('streak_days')
            ->orderByDesc('last_updated')
            ->paginate($perPage, ['*'], 'page', $page);

        $paginator->setCollection($this->enrichCurrentPageWithConsumption($paginator->getCollection()));

        return $paginator;
    }

    // -------------------------------------------------------------------------
    // Sync: query + enrich + upsert
    // -------------------------------------------------------------------------

    /**
     * Rebuild the `problematic_materials` table from the DB + consumption API.
     * Skipped when the cache key is still alive (SYNC_TTL seconds).
     * Consumption API enrichment is best-effort; sync proceeds with null values
     * for coverage/avg fields when the API is unreachable or doesn't cover a material.
     */
    private function sync(): void
    {
        if (Cache::has(self::SYNC_CACHE_KEY)) {
            return;
        }

        $materials = $this->queryProblematicMaterials();
        $consumptionMap = $this->buildConsumptionMapForMaterials($materials);
        $now       = now();

        $rows = collect($materials)
            ->map(function ($item) use ($consumptionMap, $now) {
                $key         = strtoupper(trim((string) $item['material_number']));
                $consumption = $consumptionMap[$key] ?? [];

                $shiftAvg = (float) ($consumption['shift_avg'] ?? 0);
                $dailyAvg = (float) ($consumption['daily_avg'] ?? 0);

                $coverageShifts = $shiftAvg > 0
                    ? round(((float) $item['instock']) / $shiftAvg, 1)
                    : null;

                $streakDays = (int) $item['streak_days'];
                $severity = $this->resolveSeverity($item['status'], $coverageShifts, $streakDays);

                return [
                    'material_number'  => $item['material_number'],
                    'description'      => $item['description'],
                    'pic_name'         => $item['pic_name'],
                    'status'           => $item['status'],
                    'status_priority'  => $item['status_priority'],
                    'severity'         => $severity,
                    'coverage_shifts'  => $coverageShifts,
                    'daily_avg'        => $dailyAvg ?: null,
                    'shift_avg'        => $shiftAvg ?: null,
                    'instock'          => (int) $item['instock'],
                    'streak_days'      => $streakDays,
                    'location'         => $item['location'],
                    'usage'            => $item['usage'],
                    'gentani'          => $item['gentani'],
                    'last_updated'     => $item['last_updated'],
                    'total_consumed'   => $consumption['total_usage'] ?? null,
                    'calculation_info' => json_encode($consumption['calculation_info'] ?? null),
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];
            })
            ->keyBy('material_number') // deduplicate; first (highest severity) wins due to ORDER BY
            ->values()
            ->all();

        // Upsert: insert new rows, update enriched fields on conflict.
        // estimated_gr is intentionally excluded from $updateColumns so
        // user-entered dates are never overwritten by the sync.
        $updateColumns = [
            'description',
            'pic_name',
            'status',
            'status_priority',
            'severity',
            'coverage_shifts',
            'daily_avg',
            'shift_avg',
            'instock',
            'streak_days',
            'location',
            'usage',
            'gentani',
            'last_updated',
            'total_consumed',
            'calculation_info',
            'updated_at',
        ];

        DB::transaction(function () use ($rows, $updateColumns) {
            if (!empty($rows)) {
                ProblematicMaterials::upsert($rows, ['material_number'], $updateColumns);
            }
            // Remove materials that are no longer in SHORTAGE/CAUTION
            $currentNumbers = array_column($rows, 'material_number');
            ProblematicMaterials::whereNotIn('material_number', $currentNumbers)->delete();
        });

        Cache::put(self::SYNC_CACHE_KEY, true, self::SYNC_TTL);
    }

    // -------------------------------------------------------------------------
    // DB query
    // -------------------------------------------------------------------------

    private function queryProblematicMaterials(): array
    {
        $sql = "
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
                    COUNT(*) as streak_days
                FROM streak_scan
                WHERE status = latest_status
                    AND COALESCE(previous_status_breaks, 0) = 0
                GROUP BY material_id
            )
            SELECT
                materials.id,
                materials.material_number,
                materials.description,
                materials.pic_name,
                materials.location,
                materials.usage,
                materials.gentani,
                COALESCE(latest.daily_stock, 0) as instock,
                COALESCE(latest.status, 'UNCHECKED') as status,
                latest.date as last_updated,
                COALESCE(streaks.streak_days, 0) as streak_days,
                CASE COALESCE(latest.status, 'UNCHECKED')
                    WHEN 'SHORTAGE' THEN 1
                    WHEN 'CAUTION'  THEN 2
                    ELSE 3
                END as status_priority
            FROM materials
            INNER JOIN latest ON materials.id = latest.material_id
            LEFT JOIN streaks ON materials.id = streaks.material_id
            WHERE latest.status IN ('SHORTAGE', 'CAUTION')
            ORDER BY status_priority ASC, streak_days DESC
        ";

        return collect(DB::select($sql))->map(fn($row) => [
            'id'              => $row->id,
            'material_number' => $row->material_number,
            'description'     => $row->description,
            'pic_name'        => $row->pic_name ?? '-',
            'location'        => $row->location ?? '-',
            'usage'           => $row->usage ?? '-',
            'gentani'         => $row->gentani ?? null,
            'instock'         => (int) $row->instock,
            'status'          => $row->status,
            'status_priority' => (int) $row->status_priority,
            'streak_days'     => (int) $row->streak_days,
            'last_updated'    => $row->last_updated,
        ])->all();
    }

    // -------------------------------------------------------------------------
    // External API
    // -------------------------------------------------------------------------

    private function buildConsumptionMapForMaterials(array $materials): array
    {
        return $this->buildConsumptionMapForMaterialNumbers(collect($materials)->pluck('material_number'), 0);
    }

    private function buildConsumptionMapForMaterialNumbers(Collection $materialNumbers, ?int $fallbackLimit = null): array
    {
        $map = $this->getCachedConsumptionAverages();
        $missingMaterialNumbers = $materialNumbers
            ->map(fn($number) => $this->normalizeMaterialNumber($number))
            ->filter()
            ->unique()
            ->reject(fn($number) => isset($map[$number]))
            ->values();

        if ($missingMaterialNumbers->isEmpty()) {
            return $map;
        }

        if ($fallbackLimit === 0) {
            return $map;
        }

        return array_replace($map, $this->fetchConsumptionAveragesForMaterials($missingMaterialNumbers, $fallbackLimit));
    }

    private function getCachedConsumptionAverages(): array
    {
        return Cache::get('consumption_averages_all') ?? [];
    }

    public function fetchConsumptionAveragesAll(): array
    {
        $cached = Cache::get('consumption_averages_all');
        if ($cached !== null) {
            return $cached;
        }

        $apiUrl = config('services.consumption_api.url');
        $apiKey = config('services.consumption_api.key');

        $page           = 1;
        $limit          = 500;
        $maxPagesSafety = 1000;

        $all = collect();

        try {
            while (true) {
                $response = Http::withHeaders([
                    'x-api-key' => $apiKey,
                    'Accept'    => 'application/json',
                ])
                    ->withOptions(['verify' => config('services.consumption_api.verify_ssl', false)])
                    ->timeout(20)
                    ->get($apiUrl, [
                    'months'      => 3,
                    'page'        => $page,
                    'limit'       => $limit,
                ]);

                if (!$response->successful()) {
                    Log::warning('Consumption API returned non-2xx', [
                        'status' => $response->status(),
                        'body'   => $response->body(),
                        'page'   => $page,
                    ]);
                    return [];
                }

                $rows = $response->json('data') ?? [];
                if (empty($rows)) break;

                $all = $all->concat($rows);

                $pagination = $response->json('pagination');
                if (is_array($pagination) && isset($pagination['totalPages'])) {
                    if ($page >= (int) $pagination['totalPages']) break;
                } else {
                    if (count($rows) < $limit) break;
                }

                $page++;
                if ($page > $maxPagesSafety) break;
            }

            $result = $all
                ->filter(fn($r) => !empty($r['material_id']))
                ->mapWithKeys(fn($r) => [strtoupper(trim((string) $r['material_id'])) => $r])
                ->all();

            if (!empty($result)) {
                Cache::put('consumption_averages_all', $result, 3600);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error('Consumption API unreachable', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function fetchConsumptionAveragesForMaterials(Collection $materialNumbers, ?int $limit = null): array
    {
        $limit ??= Cache::get('consumption_averages_all') === null ? 10 : 50;
        $numbers = $materialNumbers
            ->map(fn($number) => $this->normalizeMaterialNumber($number))
            ->filter()
            ->unique()
            ->take($limit)
            ->values();

        if ($numbers->isEmpty()) {
            return [];
        }

        $cachedRows = [];
        $numbersToFetch = $numbers->filter(function ($number) use (&$cachedRows) {
            $cached = Cache::get('consumption_average_material:' . $number);

            if (is_array($cached)) {
                $cachedRows[$number] = $cached;

                return false;
            }

            return true;
        })->values();

        if ($numbersToFetch->isEmpty()) {
            return $cachedRows;
        }

        $apiUrl = config('services.consumption_api.url');
        $apiKey = config('services.consumption_api.key');
        $aliasMap = [];

        try {
            $responses = Http::pool(function ($pool) use ($numbersToFetch, $apiUrl, $apiKey, &$aliasMap) {
                $requests = [];

                foreach ($numbersToFetch as $index => $materialNumber) {
                    $alias = 'material_' . $index;
                    $aliasMap[$alias] = $materialNumber;
                    $requests[] = $pool->as($alias)
                        ->withHeaders([
                            'x-api-key' => $apiKey,
                            'Accept'    => 'application/json',
                        ])
                        ->withOptions(['verify' => config('services.consumption_api.verify_ssl', false)])
                        ->timeout(5)
                        ->get($apiUrl, [
                            'months'      => 3,
                            'search'      => $materialNumber,
                            'page'        => 1,
                            'limit'       => 10,
                        ]);
                }

                return $requests;
            });
        } catch (\Throwable $e) {
            Log::error('Consumption API material lookup pool unreachable', [
                'error' => $e->getMessage(),
            ]);

            return $cachedRows;
        }

        $result = $cachedRows;

        foreach ($responses as $alias => $response) {
            $materialNumber = $aliasMap[$alias] ?? null;

            if (!$materialNumber) {
                continue;
            }

            if (!$response instanceof Response) {
                Log::warning('Consumption API material lookup failed before response', [
                    'material_number' => $materialNumber,
                    'error' => $response instanceof \Throwable ? $response->getMessage() : get_debug_type($response),
                ]);

                continue;
            }

            if (!$response->successful()) {
                Log::warning('Consumption API material lookup returned non-2xx', [
                    'status' => $response->status(),
                    'material_number' => $materialNumber,
                ]);

                continue;
            }

            $row = collect($response->json('data') ?? [])
                ->first(fn($item) => $this->normalizeMaterialNumber($item['material_id'] ?? null) === $materialNumber);

            if (!$row) {
                continue;
            }

            Cache::put('consumption_average_material:' . $materialNumber, $row, 3600);
            $result[$materialNumber] = $row;
        }

        return $result;
    }

    private function enrichCurrentPageWithConsumption(Collection $rows): Collection
    {
        $rowsNeedingConsumption = $rows->filter(
            fn($row) => $row->daily_avg === null || $row->shift_avg === null || $row->coverage_shifts === null
        );

        if ($rowsNeedingConsumption->isEmpty()) {
            return $rows;
        }

        $consumptionMap = $this->buildConsumptionMapForMaterialNumbers(
            $rowsNeedingConsumption->pluck('material_number'),
            $rowsNeedingConsumption->count()
        );

        return $rows->map(function (ProblematicMaterials $row) use ($consumptionMap) {
            $key = $this->normalizeMaterialNumber($row->material_number);
            $consumption = $consumptionMap[$key] ?? null;

            if (!$consumption) {
                return $row;
            }

            $this->applyConsumptionToRow($row, $consumption);
            $row->save();

            return $row;
        });
    }

    private function applyConsumptionToRow(ProblematicMaterials $row, array $consumption): void
    {
        $shiftAvg = (float) ($consumption['shift_avg'] ?? 0);
        $dailyAvg = (float) ($consumption['daily_avg'] ?? 0);
        $coverageShifts = $shiftAvg > 0
            ? round(((float) $row->instock) / $shiftAvg, 1)
            : null;

        $row->coverage_shifts = $coverageShifts;
        $row->daily_avg = $dailyAvg ?: null;
        $row->shift_avg = $shiftAvg ?: null;
        $row->total_consumed = $consumption['total_usage'] ?? null;
        $row->calculation_info = $consumption['calculation_info'] ?? null;
        $row->severity = $this->resolveSeverity($row->status, $coverageShifts, (int) $row->streak_days);
    }

    private function normalizeMaterialNumber($materialNumber): string
    {
        return strtoupper(trim((string) $materialNumber));
    }

    // -------------------------------------------------------------------------
    // Severity logic
    // -------------------------------------------------------------------------

    private function resolveSeverity(string $status, ?float $coverageShifts, int $streakDays): string
    {
        if ($status === 'SHORTAGE') {
            if ($coverageShifts === null) return 'High';
            if ($coverageShifts < 1)      return 'Line-Stop';
            if ($coverageShifts < 3)      return 'High';
            return 'Medium';
        }

        // CAUTION
        if ($coverageShifts !== null && $coverageShifts < 3) return 'High';
        if ($streakDays > 7) return 'High';
        if ($streakDays > 3) return 'Medium';
        return 'Low';
    }
}
