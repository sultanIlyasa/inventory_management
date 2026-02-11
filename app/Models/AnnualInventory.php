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
        'sloc',
        'group_leader',
        'pic_input',
        'pic_name_signature',
        'group_leader_signature',
        'pic_input_signature',
    ];
    protected static $slocMapping = [
        '2000 - Warehouse Consummable, Chemical, & Raw Material' => [
            'pic_input' => 'ADE N',
            'group_leader' => 'DEDY S',
        ],
        '2300 - Warehouse Consummable & Tools' => [
            'pic_input' => 'EKO P',
            'group_leader' => 'AHMAD J',
        ],
        '5002 - Mach Kaizen' => [
            'pic_input' => 'EKO P',
            'group_leader' => 'AHMAD J',
        ],
    ];
    protected static $plantMapping = [
        '2000 - Warehouse Consummable, Chemical, & Raw Material' => [
            'sloc' => '2000 - Sunter 2'
        ],
        '2300 - Warehouse Consummable & Tools' => [
            'sloc' => '1000 - Sunter 1'
        ],
        '5002 - Mach Kaizen' => [
            'sloc' => '1000 - Sunter 1'
        ],
    ];

    protected static function booted()
    {
        static::saving(function (AnnualInventory $model) {
            $model->assignPicAndGroupLeader();
        });
    }

    public function assignPicAndGroupLeader()
    {
        if ($this->location && isset(static::$slocMapping[$this->location])) {
            $mapping = static::$slocMapping[$this->location];
            $plantMapping = static::$plantMapping[$this->location];
            $this->pic_input = $mapping['pic_input'];
            $this->group_leader = $mapping['group_leader'];
            $this->sloc = $plantMapping['sloc'];
        }
    }



    public function items()
    {
        return $this->hasMany(AnnualInventoryItems::class, 'annual_inventory_id', 'id');
    }
}
