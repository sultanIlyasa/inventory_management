<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DailyInput extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_id',
        'date',
        'daily_stock',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'daily_stock' => 'integer',
    ];

    /**
     * Each DailyInput belongs to a Material
     */
    public function material()
    {
        return $this->belongsTo(Materials::class, "material_id");
    }
    /**
     * Scope: Filter by date range
     */
    public function scopeInDateRange(Builder $query, ?string $startDate, ?string $endDate): Builder
    {
        if ($startDate && $endDate) {
            return $query->whereBetween('date', [$startDate, $endDate]);
        }

        if ($startDate) {
            return $query->where('date', '>=', $startDate);
        }

        if ($endDate) {
            return $query->where('date', '<=', $endDate);
        }

        return $query;
    }

    /**
     * Scope: Filter by month
     */
    public function scopeInMonth(Builder $query, ?string $month): Builder
    {
        if (!$month) {
            return $query;
        }

        $startOfMonth = Carbon::parse($month)->startOfMonth()->toDateString();
        $endOfMonth = Carbon::parse($month)->endOfMonth()->toDateString();

        return $query->whereBetween('date', [$startOfMonth, $endOfMonth]);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Exclude weekends
     */
    public function scopeWeekdaysOnly(Builder $query): Builder
    {
        return $query->whereRaw('DAYOFWEEK(date) NOT IN (1, 7)');
    }

    /**
     * Scope: Get latest input for each material
     */
    public function scopeLatestPerMaterial(Builder $query): Builder
    {
        return $query->whereIn('id', function ($subQuery) {
            $subQuery->select(DB::raw('MAX(id)'))
                ->from('daily_inputs as di2')
                ->whereColumn('di2.material_id', 'daily_inputs.material_id')
                ->groupBy('di2.material_id');
        });
    }

    /**
     * Static Query: Get latest input for a specific material
     */
    public static function getLatestForMaterial(int $materialId, ?string $beforeDate = null): ?self
    {
        return self::where('material_id', $materialId)
            ->when($beforeDate, fn($q) => $q->where('date', '<=', $beforeDate))
            ->orderBy('date', 'desc')
            ->first();
    }

    /**
     * Static Query: Get status days count for a material
     * Returns: ['OK' => 10, 'CAUTION' => 5, ...]
     */
    public static function getStatusDaysForMaterial(
        int $materialId,
        ?string $month = null,
        ?string $beforeDate = null
    ): array {
        $query = self::where('material_id', $materialId)
            ->selectRaw('status, COUNT(*) as count')
            ->inMonth($month)
            ->when($beforeDate && !$month, fn($q) => $q->where('date', '<=', $beforeDate));

        $results = $query->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'OK' => $results['OK'] ?? 0,
            'CAUTION' => $results['CAUTION'] ?? 0,
            'SHORTAGE' => $results['SHORTAGE'] ?? 0,
            'OVERFLOW' => $results['OVERFLOW'] ?? 0,
            'UNCHECKED' => $results['UNCHECKED'] ?? 0,
        ];
    }

    /**
     * Static Query: Get status transition frequencies for a material
     * Uses SQL window functions for performance
     */
    public static function getStatusTransitionsForMaterial(
        int $materialId,
        ?string $month = null,
        ?string $beforeDate = null
    ): array {
        $dateCondition = '';
        $params = [$materialId];

        if ($month) {
            $startOfMonth = Carbon::parse($month)->startOfMonth()->toDateString();
            $endOfMonth = Carbon::parse($month)->endOfMonth()->toDateString();
            $dateCondition = "AND date BETWEEN ? AND ?";
            $params[] = $startOfMonth;
            $params[] = $endOfMonth;
        } elseif ($beforeDate) {
            $dateCondition = "AND date <= ?";
            $params[] = $beforeDate;
        }

        $sql = "
            SELECT
                SUM(CASE WHEN prev_status = 'OK' AND status = 'CAUTION' THEN 1 ELSE 0 END) as ok_to_caution,
                SUM(CASE WHEN prev_status = 'OK' AND status = 'SHORTAGE' THEN 1 ELSE 0 END) as ok_to_shortage,
                SUM(CASE WHEN prev_status = 'OK' AND status = 'OVERFLOW' THEN 1 ELSE 0 END) as ok_to_overflow,
                SUM(CASE WHEN prev_status = 'CAUTION' AND status = 'OK' THEN 1 ELSE 0 END) as caution_to_ok,
                SUM(CASE WHEN prev_status = 'SHORTAGE' AND status = 'OK' THEN 1 ELSE 0 END) as shortage_to_ok,
                SUM(CASE WHEN prev_status = 'OVERFLOW' AND status = 'OK' THEN 1 ELSE 0 END) as overflow_to_ok,
                SUM(CASE WHEN prev_status = 'OK' AND status != 'OK' THEN 1 ELSE 0 END) as total_from_ok,
                SUM(CASE WHEN status = 'OK' AND prev_status != 'OK' THEN 1 ELSE 0 END) as total_to_ok
            FROM (
                SELECT
                    status,
                    LAG(status) OVER (ORDER BY date) as prev_status
                FROM daily_inputs
                WHERE material_id = ? {$dateCondition}
                ORDER BY date
            ) as status_changes
        ";

        $result = DB::selectOne($sql, $params);

        return [
            'ok_to_caution' => $result->ok_to_caution ?? 0,
            'ok_to_shortage' => $result->ok_to_shortage ?? 0,
            'ok_to_overflow' => $result->ok_to_overflow ?? 0,
            'caution_to_ok' => $result->caution_to_ok ?? 0,
            'shortage_to_ok' => $result->shortage_to_ok ?? 0,
            'overflow_to_ok' => $result->overflow_to_ok ?? 0,
            'total_from_ok' => $result->total_from_ok ?? 0,
            'total_to_ok' => $result->total_to_ok ?? 0,
        ];
    }

    /**
     * Static Query: Calculate consecutive days for current status
     * Optimized: Only fetches recent records
     */
    public static function getConsecutiveDaysForMaterial(
        int $materialId,
        string $currentStatus,
        string $latestDate,
        int $lookbackLimit = 100
    ): int {
        $inputs = self::where('material_id', $materialId)
            ->where('date', '<=', $latestDate)
            ->weekdaysOnly()
            ->orderBy('date', 'desc')
            ->limit($lookbackLimit)
            ->get(['date', 'status']);

        if ($inputs->isEmpty()) {
            return 0;
        }

        $consecutiveDays = 0;
        $previousDate = null;

        foreach ($inputs as $input) {
            if ($input->status !== $currentStatus) {
                break;
            }

            $currentDate = Carbon::parse($input->date);

            if ($previousDate === null) {
                $consecutiveDays = 1;
                $previousDate = $currentDate;
            } else {
                $expectedPreviousDate = $currentDate->copy()->addWeekday();

                if ($expectedPreviousDate->isSameDay($previousDate)) {
                    $consecutiveDays++;
                    $previousDate = $currentDate;
                } else {
                    break;
                }
            }
        }

        return $consecutiveDays;
    }

    /**
     * Static Query: Get materials with status changes in date range
     */
    public static function getMaterialsWithStatusChanges(
        array $filters = [],
        ?string $month = null,
        ?string $beforeDate = null
    ): Builder {
        return self::query()
            ->select([
                'daily_inputs.material_id',
                DB::raw('COUNT(CASE WHEN prev_status IS NOT NULL AND prev_status != status THEN 1 END) as change_count')
            ])
            ->fromSub(
                function ($query) use ($filters, $month, $beforeDate) {
                    $query->select([
                        'material_id',
                        'status',
                        DB::raw('LAG(status) OVER (PARTITION BY material_id ORDER BY date) as prev_status')
                    ])
                        ->from('daily_inputs')
                        ->join('materials', 'materials.id', '=', 'daily_inputs.material_id')
                        ->inMonth($month)
                        ->when($beforeDate && !$month, fn($q) => $q->where('daily_inputs.date', '<=', $beforeDate));

                    // Apply material filters
                    if (!empty($filters['usage'])) {
                        $query->whereIn('materials.usage', (array) $filters['usage']);
                    }
                    if (!empty($filters['location'])) {
                        $query->whereIn('materials.location', (array) $filters['location']);
                    }
                    if (!empty($filters['gentani'])) {
                        $query->whereIn('materials.gentani', (array) $filters['gentani']);
                    }
                },
                'status_changes'
            )
            ->groupBy('material_id')
            ->having('change_count', '>', 0);
    }
}
