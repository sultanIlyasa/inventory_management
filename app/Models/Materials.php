<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
