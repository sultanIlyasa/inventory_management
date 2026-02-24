<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProblematicMaterials extends Model
{
    protected $fillable = [
        'material_number',
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
        'estimated_gr',
        'total_consumed',
        'calculation_info',
    ];

    protected $casts = [
        'coverage_shifts' => 'float',
        'daily_avg'       => 'float',
        'shift_avg'       => 'float',
        'instock'         => 'integer',
        'streak_days'     => 'integer',
        'status_priority' => 'integer',
        'last_updated'    => 'date',
        'estimated_gr'    => 'date',
        'total_consumed'  => 'integer',
        'calculation_info' => 'array',
    ];

    public function scopeFilterByUsage(Builder $query, $usage): Builder
    {
        if (empty($usage)) {
            return $query;
        }

        $values = is_array($usage)
            ? $usage
            : array_map('trim', explode(',', $usage));

        return $query->whereIn('usage', $values);
    }

    public function scopeFilterByLocation(Builder $query, $location): Builder
    {
        if (empty($location)) {
            return $query;
        }

        $values = is_array($location)
            ? $location
            : array_map('trim', explode(',', $location));

        return $query->whereIn('location', $values);
    }

    public function scopeFilterByGentani(Builder $query, $gentani): Builder
    {
        if (empty($gentani)) {
            return $query;
        }

        $values = is_array($gentani)
            ? $gentani
            : array_map('trim', explode(',', $gentani));

        return $query->whereIn('gentani', $values);
    }

    public function scopeFilterByPIC(Builder $query, $pic): Builder
    {
        if (empty($pic)) {
            return $query;
        }

        $values = is_array($pic)
            ? $pic
            : array_map('trim', explode(',', $pic));

        return $query->whereIn('pic_name', $values);
    }

    public function scopeApplyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->filterByUsage($filters['usage'] ?? null)
            ->filterByLocation($filters['location'] ?? null)
            ->filterByGentani($filters['gentani'] ?? null)
            ->filterByPIC($filters['pic_name'] ?? null);
    }
}
