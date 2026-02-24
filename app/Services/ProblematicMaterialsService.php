<?php

namespace App\Services;

use App\Models\ProblematicMaterials;
use Illuminate\Pagination\LengthAwarePaginator;
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
    public function getProblematicMaterials(int $page = 1, int $perPage = 10, ?string $status = null): LengthAwarePaginator
    {
        $this->sync();

        return ProblematicMaterials::when($status, fn($q) => $q->where('status', $status))
            ->orderBy('status_priority')
            ->orderByDesc('streak_days')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    // -------------------------------------------------------------------------
    // Sync: query + enrich + upsert
    // -------------------------------------------------------------------------

    /**
     * Rebuild the `problematic_materials` table from the DB + consumption API.
     * Skipped when the cache key is still alive (SYNC_TTL seconds).
     * Skipped when the consumption API returns no data (keeps stale rows intact).
     */
    private function sync(): void
    {
        if (Cache::has(self::SYNC_CACHE_KEY)) {
            return;
        }

        $consumptionMap = $this->fetchConsumptionAveragesAll();

        if (empty($consumptionMap)) {
            Log::warning('ProblematicMaterials sync skipped: consumption map is empty');
            return;
        }

        $materials = $this->queryProblematicMaterials();
        $now       = now();

        $rows = collect($materials)
            ->filter(fn($item) => isset($consumptionMap[strtoupper(trim((string) $item['material_number']))]))
            ->map(function ($item) use ($consumptionMap, $now) {
                $key         = strtoupper(trim((string) $item['material_number']));
                $consumption = $consumptionMap[$key];

                $shiftAvg = (float) ($consumption['shift_avg'] ?? 0);
                $dailyAvg = (float) ($consumption['daily_avg'] ?? 0);

                $coverageShifts = $shiftAvg > 0
                    ? round(((float) $item['instock']) / $shiftAvg, 1)
                    : null;

                $streakDays = (int) $item['streak_days'];

                return [
                    'material_number'  => $item['material_number'],
                    'description'      => $item['description'],
                    'pic_name'         => $item['pic_name'],
                    'status'           => $item['status'],
                    'status_priority'  => $item['status_priority'],
                    'severity'         => $this->resolveSeverity($item['status'], $coverageShifts, $streakDays),
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
            'description', 'pic_name', 'status', 'status_priority', 'severity',
            'coverage_shifts', 'daily_avg', 'shift_avg', 'instock', 'streak_days',
            'location', 'usage', 'gentani', 'last_updated',
            'total_consumed', 'calculation_info', 'updated_at',
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
                COALESCE(
                    CASE
                        WHEN latest.date IS NULL THEN 0
                        ELSE (
                            DATEDIFF(CURDATE(), latest.date)
                            - (WEEK(CURDATE()) - WEEK(latest.date)) * 2
                            - CASE WHEN DAYOFWEEK(latest.date) = 1 THEN 1 ELSE 0 END
                            - CASE WHEN DAYOFWEEK(CURDATE()) = 7 THEN 1 ELSE 0 END
                        )
                    END,
                0) as streak_days,
                CASE COALESCE(latest.status, 'UNCHECKED')
                    WHEN 'SHORTAGE' THEN 1
                    WHEN 'CAUTION'  THEN 2
                    ELSE 3
                END as status_priority
            FROM materials
            LEFT JOIN (
                SELECT material_id, status, daily_stock, date
                FROM daily_inputs
                WHERE id IN (
                    SELECT MAX(id) FROM daily_inputs GROUP BY material_id
                )
            ) as latest ON materials.id = latest.material_id
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

    public function fetchConsumptionAveragesAll(): array
    {
        $cached = Cache::get('consumption_averages_all');
        if ($cached !== null) {
            return $cached;
        }

        $apiUrl = config('services.consumption_api.url');
        $apiKey = config('services.consumption_api.key');

        $page           = 1;
        $limit          = 200;
        $maxPagesSafety = 1000;

        $all = collect();

        try {
            while (true) {
                $response = Http::withHeaders([
                    'x-api-key' => $apiKey,
                    'Accept'    => 'application/json',
                ])->timeout(20)->get($apiUrl, [
                    'location_id' => 1,
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

    // -------------------------------------------------------------------------
    // Severity logic
    // -------------------------------------------------------------------------

    private function resolveSeverity(string $status, ?float $coverageShifts, int $streakDays): string
    {
        if ($status === 'SHORTAGE') {
            if ($coverageShifts === null) return 'High';
            if ($coverageShifts < 1)      return 'Line-Stop Risk';
            if ($coverageShifts < 3)      return 'High';
            return 'Medium';
        }

        // CAUTION
        if ($streakDays > 7) return 'High';
        if ($streakDays > 3) return 'Medium';
        return 'Low';
    }
}
