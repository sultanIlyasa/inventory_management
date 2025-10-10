<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Materials extends Model
{
    use HasFactory;
    protected $casts = [
        'emails' => 'array',
    ];


    protected $fillable = [
        'material_number',
        'description',
        'pic_name',
        'stock_minimum',
        'stock_maximum',
        'unit_of_measure',
        'rack_address',
        'vendor_name',
        'emails',
        'phone_number'
    ];

    /**
     * One Material can have many DailyInputs
     */
    public function dailyInputs()
    {
        return $this->hasMany(DailyInput::class, 'material_id', 'id');
    }
}
