<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnualInventoryItems extends Model
{
    protected $fillable = [
        'annual_inventory_id',
        'material_number',
        'description',
        'rack_address',
        'unit_of_measure',
        'system_qty',
        'actual_qty',
        'final_counted_qty',
        'price',
        'price_updated_at',
        'soh',
        'soh_updated_at',
        'outstanding_gr',
        'outstanding_gi',
        'error_movement',
        'final_discrepancy',
        'final_discrepancy_amount',
        'status',
        'counted_by',
        'counted_at',
        'image_path',
        'notes',
        'actual_qty_history'
    ];
    protected $casts = [
        'counted_at' => 'datetime',
        'price_updated_at' => 'datetime',
        'soh_updated_at' => 'datetime',
        'system_qty' => 'decimal:2',
        'actual_qty' => 'decimal:2',
        'price' => 'decimal:2',
        'outstanding_gr' => 'decimal:2',
        'outstanding_gi' => 'decimal:2',
        'error_movement' => 'decimal:2',
        'final_discrepancy' => 'decimal:2',
        'final_discrepancy_amount' => 'decimal:2',
        'actual_qty_history' => 'array'
    ];
    public function annualInventory()
    {
        return $this->belongsTo(AnnualInventory::class, 'annual_inventory_id');
    }
}
