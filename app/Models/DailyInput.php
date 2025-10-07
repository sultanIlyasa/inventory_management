<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyInput extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_id',
        'date',
        'daily_stock',
        'status',
    ];

    /**
     * Each DailyInput belongs to a Material
     */
    public function material()
    {
        return $this->belongsTo(Materials::class,"material_id");
    }
}

