<?php

namespace App\Http\Controllers;

use App\Services\MaterialReportService;



use Illuminate\Http\Request;

class OverdueStatusDaysController extends Controller
{
    protected $reportService;

    public function __construct(MaterialReportService $reportService)
    {
        $this->reportService = $reportService;
    }
    public function index() {

    }
}
