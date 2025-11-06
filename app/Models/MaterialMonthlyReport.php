<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialMonthlyReport extends Model
{
    protected $fillable = [

        'material_id',
        'month',
        'avg_recovery_days',
        'total_overdue_days',

    ];

    /**
     * Relationship: MaterialMonthlyReport belongs to a Material
     */
    public function material()
    {
        return $this->belongsTo(Materials::class, 'material_id', 'id');
    }
}
