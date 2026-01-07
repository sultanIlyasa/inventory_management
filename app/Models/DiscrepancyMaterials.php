<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscrepancyMaterials extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_id',
        'price',
        'soh',
        'outstanding_gr',
        'outstanding_gi',
        'error_moving',
        'last_synced_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'soh' => 'integer',
        'outstanding_gr' => 'integer',
        'outstanding_gi' => 'integer',
        'error_moving' => 'integer',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get the material associated with this discrepancy record
     */
    public function material()
    {
        return $this->belongsTo(Materials::class, 'material_id');
    }

    /**
     * Get the latest daily input for this material
     */
    public function latestDailyInput()
    {
        return $this->hasOneThrough(
            DailyInput::class,
            Materials::class,
            'id',           // Foreign key on Materials
            'material_id',  // Foreign key on DailyInput
            'material_id',  // Local key on DiscrepancyMaterials
            'id'            // Local key on Materials
        )->latest('date');
    }

    /**
     * Calculate the initial discrepancy (Qty Actual - SoH)
     */
    public function getInitialDiscrepancy(): int
    {
        $latestInput = DailyInput::getLatestForMaterial($this->material_id);
        $qtyActual = $latestInput ? $latestInput->daily_stock : 0;

        return $qtyActual - $this->soh;
    }

    /**
     * Calculate the final discrepancy after adjustments
     */
    public function getFinalDiscrepancy(): int
    {
        $initialDiscrepancy = $this->getInitialDiscrepancy();
        $explained = $this->outstanding_gr + $this->outstanding_gi + $this->error_moving;

        return $initialDiscrepancy + $explained;
    }

    /**
     * Calculate the final discrepancy amount
     */
    public function getFinalDiscrepancyAmount(): float
    {
        return $this->getFinalDiscrepancy() * $this->price;
    }
}
