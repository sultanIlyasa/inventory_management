<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vendors extends Model
{
    use hasFactory;
    protected $casts = [
        'emails' => 'array',
    ];
    protected $fillable = [
        'vendor_number',
        'vendor_name',
        'contact_person',
        'phone_number',
        'emails',
    ];

    // one vendor can supply many materials
    public function materials()
    {
        return $this->hasMany(Materials::class, 'vendor_id', 'id');
    }
}
