<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Materials extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_number',
        'description',
        'pic_name',
        'stock_minimum',
        'stock_maximum',
        'unit_of_measure',
    ];

    /**
     * One Material can have many DailyInputs
     */
    public function dailyInputs()
    {
        return $this->hasMany(DailyInput::class);
    }
}
