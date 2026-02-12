<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiscalYearConfig extends Model
{
    protected $fillable = ['fiscal_year', 'start_month'];
}
