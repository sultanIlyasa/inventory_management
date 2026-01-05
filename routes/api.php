<?php

use Illuminate\Http\Request;
use App\Http\Controllers\DailyInputController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\MaterialsController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\AdminDashboardController;

use Illuminate\Support\Facades\Route;
// Material Routes
Route::get('/materials', [MaterialsController::class, 'index'])->name('api-get-materials');;
Route::post('/materials', [MaterialsController::class, 'store']);
Route::put('/materials/{id}', [MaterialsController::class, 'update']);
Route::delete('/materials/{id}', [MaterialsController::class, 'destroy']);
Route::get('/materials/{id}', [MaterialsController::class, 'show']);

// Daily Input Routes
Route::get('/daily-input', [DailyInputController::class, 'index']);
Route::post('/daily-input', [DailyInputController::class, 'store']);
Route::get('/daily-input/missing', [DailyInputController::class, 'missing']);
Route::get('/daily-input/status', [DailyInputController::class, 'dailyStatus']);
Route::delete('/daily-input/delete/{id}', [DailyInputController::class, 'destroy']);
Route::get('/daily-input/weekly-status',[DailyInputController::class,'weeklyStatus']);
Route::get('/daily-input/export', [DailyInputController::class, 'export']);

// Vendor Routes
Route::get('/vendors', [VendorController::class, 'index']);
Route::get('/vendors/materials/{vendor_number}', [VendorController::class, 'materialsByVendor']);

//
