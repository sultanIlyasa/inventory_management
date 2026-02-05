<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\AnnualInventoryItems;


class AnnualInventory extends Model
{
    use HasFactory;
    protected $casts = [
        'date' => 'date',
    ];

    protected $table = 'annual_inventories';

    protected $fillable = [
        'pid',
        'date',
        'status',
        'pic_name',
        'location',
        'sloc'
    ];
    public function items()
    {
        return $this->hasMany(AnnualInventoryItems::class, 'annual_inventory_id', 'id');
    }
}
