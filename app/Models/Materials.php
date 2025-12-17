<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Materials extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'material_number',
        'description',
        'pic_name',
        'stock_minimum',
        'stock_maximum',
        'unit_of_measure',
        'rack_address',
        'usage',
        'location',
        'gentani'
    ];

    /**
     * One Material can have many DailyInputs
     */
    public function dailyInputs()
    {
        return $this->hasMany(DailyInput::class, 'material_id', 'id');
    }

    // each material belongs to one vendor
    public function vendor()
    {
        return $this->belongsTo(Vendors::class, 'vendor_number');
    }
    /**
     * Scope: Apply usage filter
     */
    /**
     * Scope: Apply usage filter
     */
    public function scopeFilterByUsage(Builder $query, $usage): Builder
    {
        if (empty($usage)) {
            return $query;
        }

        $usages = is_array($usage) ? $usage : array_map('trim', explode(',', $usage));
        return $query->whereIn('usage', $usages);
    }

    /**
     * Scope: Apply location filter
     */
    public function scopeFilterByLocation(Builder $query, $location): Builder
    {
        if (empty($location)) {
            return $query;
        }

        $locations = is_array($location) ? $location : array_map('trim', explode(',', $location));
        return $query->whereIn('location', $locations);
    }

    /**
     * Scope: Apply gentani filter
     */
    public function scopeFilterByGentani(Builder $query, $gentani): Builder
    {
        if (empty($gentani)) {
            return $query;
        }

        $gentanis = is_array($gentani) ? $gentani : array_map('trim', explode(',', $gentani));
        return $query->whereIn('gentani', $gentanis);
    }
    /**
     * Scope: Apply pic filter
     */
    public function scopeFilterByPIC(Builder $query, $pic): Builder
    {
        if (empty($pic)) {
            return $query;
        }

        $pics = is_array($pic) ? $pic : array_map('trim', explode(',', $pic));
        return $query->whereIn('pic_name', $pics);
    }

    /**
     * Scope: Apply all common filters at once
     */
    public function scopeApplyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->filterByUsage($filters['usage'] ?? null)
            ->filterByLocation($filters['location'] ?? null)
            ->filterByGentani($filters['gentani'] ?? null)
            ->filterByPIC($filters['pic_name'] ?? null);
    }

    /**
     * Static Query Builder: Get leaderboard query for a specific status
     * This is the "Fat Model" principle - all DB logic in the model
     *
     * SQL-first approach: Uses joins and aggregation at database level
     */
    public static function getLeaderboardQuery(string $status, array $filters = [])
    {
        return DB::table('materials')
            ->select([
                'materials.id',
                'materials.material_number',
                'materials.description',
                'materials.pic_name as pic',
                'materials.usage',
                'materials.location',
                'materials.gentani',
                DB::raw('COUNT(DISTINCT daily_inputs.date) as days'),
                DB::raw('MAX(daily_inputs.daily_stock) as current_stock'),
            ])
            ->join('daily_inputs', 'materials.id', '=', 'daily_inputs.material_id')
            ->where('daily_inputs.status', $status)
            ->when(!empty($filters['usage']), function ($query) use ($filters) {
                $usages = is_array($filters['usage']) ? $filters['usage'] : array_map('trim', explode(',', $filters['usage']));
                return $query->whereIn('materials.usage', $usages);
            })
            ->when(!empty($filters['location']), function ($query) use ($filters) {
                $locations = is_array($filters['location']) ? $filters['location'] : array_map('trim', explode(',', $filters['location']));
                return $query->whereIn('materials.location', $locations);
            })
            ->when(!empty($filters['gentani']), function ($query) use ($filters) {
                $gentanis = is_array($filters['gentani']) ? $filters['gentani'] : array_map('trim', explode(',', $filters['gentani']));
                return $query->whereIn('materials.gentani', $gentanis);
            })
            ->groupBy([
                'materials.id',
                'materials.material_number',
                'materials.description',
                'materials.pic_name',
                'materials.usage',
                'materials.location',
                'materials.gentani',
            ])
            ->orderByDesc('days');
    }

    /**
     * Query Builder: Get materials with latest status
     * Returns materials with their most recent daily input status
     */
    public static function getWithLatestStatus(array $filters = []): Builder
    {
        $latestInputSubquery = DailyInput::select([
            'material_id',
            'daily_stock',
            'status',
            'date'
        ])
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('daily_inputs as di2')
                    ->whereColumn('di2.material_id', 'daily_inputs.material_id')
                    ->groupBy('di2.material_id');
            });

        return self::query()
            ->select([
                'materials.id',
                'materials.material_number',
                'materials.description',
                'materials.pic_name',
                'materials.usage',
                'materials.location',
                'materials.gentani',
                'latest_inputs.daily_stock',
                'latest_inputs.status',
                'latest_inputs.date as last_updated',
            ])
            ->joinSub($latestInputSubquery, 'latest_inputs', function ($join) {
                $join->on('materials.id', '=', 'latest_inputs.material_id');
            })
            ->applyFilters($filters);
    }

    /**
     * Query Builder: Get materials by current status
     */
    public static function getByCurrentStatus(string $status, array $filters = []): Builder
    {
        return self::getWithLatestStatus($filters)
            ->where('latest_inputs.status', $status);
    }

    /**
     * Query Builder: Get unchecked materials
     */
    public static function getUnchecked(array $filters = []): Builder
    {
        return self::query()
            ->select(['materials.*'])
            ->leftJoin('daily_inputs', 'materials.id', '=', 'daily_inputs.material_id')
            ->whereNull('daily_inputs.id')
            ->applyFilters($filters)
            ->distinct();
    }

    /**
     * Get status distribution (for charts)
     */
    public static function getStatusDistribution(array $filters = []): array
    {
        // Get latest status for each material
        $statusCounts = DB::table('daily_inputs')
            ->select('status', DB::raw('COUNT(DISTINCT material_id) as count'))
            ->join('materials', 'materials.id', '=', 'daily_inputs.material_id')
            ->whereIn('daily_inputs.id', function ($subQuery) {
                $subQuery->select(DB::raw('MAX(id)'))
                    ->from('daily_inputs as di2')
                    ->whereColumn('di2.material_id', 'daily_inputs.material_id')
                    ->groupBy('di2.material_id');
            })
            ->when(!empty($filters['usage']), function ($q) use ($filters) {
                $usages = is_array($filters['usage'])
                    ? $filters['usage']
                    : array_map('trim', explode(',', $filters['usage']));
                $q->whereIn('materials.usage', $usages);
            })
            ->when(!empty($filters['location']), function ($q) use ($filters) {
                $locations = is_array($filters['location'])
                    ? $filters['location']
                    : array_map('trim', explode(',', $filters['location']));
                $q->whereIn('materials.location', $locations);
            })
            ->when(!empty($filters['gentani']), function ($q) use ($filters) {
                $gentanis = is_array($filters['gentani'])
                    ? $filters['gentani']
                    : array_map('trim', explode(',', $filters['gentani']));
                $q->whereIn('materials.gentani', $gentanis);
            })
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Count unchecked
        $totalMaterials = self::applyFilters($filters)->count();
        $checkedCount = array_sum($statusCounts);

        return [
            'SHORTAGE' => $statusCounts['SHORTAGE'] ?? 0,
            'CAUTION' => $statusCounts['CAUTION'] ?? 0,
            'OK' => $statusCounts['OK'] ?? 0,
            'OVERFLOW' => $statusCounts['OVERFLOW'] ?? 0,
            'UNCHECKED' => max(0, $totalMaterials - $checkedCount),
        ];
    }
}
