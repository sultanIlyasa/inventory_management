<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProblematicMaterials extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_id',
        'estimated_gr',
        'severity',
        'durability',
    ];

    protected $casts = [
        'estimated_gr' => 'date'
    ];

    public function material()
    {
        return $this->belongsTo(Materials::class, 'material_id');
    }

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

    /**
     * Apply all supported filters.
     * This is still "language", not business meaning.
     */
    public function scopeApplyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->filterByUsage($filters['usage'] ?? null)
            ->filterByLocation($filters['location'] ?? null)
            ->filterByGentani($filters['gentani'] ?? null)
            ->filterByPIC($filters['pic_name'] ?? null);
    }
}
