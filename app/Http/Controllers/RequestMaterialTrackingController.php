<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class RequestMaterialTrackingController extends Controller
{
    public function index()
    {
        return Inertia::render('RequestMaterialTracking/index');
    }
}
