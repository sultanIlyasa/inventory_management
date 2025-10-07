<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialTracking extends Model
{
    protected $fillable = ['material_id', 'status', 'started_at', 'ended_at'];

    public function material()
    {
        return $this->belongsTo(Materials::class);
    }
}
