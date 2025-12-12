<?php

use Illuminate\Http\Request;
use App\Http\Controllers\DailyInputController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\MaterialsController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\AdminDashboardController;

use Illuminate\Support\Facades\Route;
// Material Routes
Route::get('/materials', [MaterialsController::class, 'index']);
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

// Report Routes
Route::prefix('reports')->group(function () {
    Route::get('/general', [ReportController::class, 'getGeneralReport']);
    Route::get('/current-status', [ReportController::class, 'currentStatusReport']);
    Route::get('/caution-leaderboard', [ReportController::class, 'getCautionLeaderboard']);
    Route::get('/shortage-leaderboard', [ReportController::class, 'getShortageLeaderboard']);
    Route::get('/overdue-status', [ReportController::class, 'getOverdueStatus']);
    Route::get('/recovery-days', [ReportController::class, 'getRecoveryDays']);
    Route::get('recovery-trend', [ReportController::class, 'getRecoveryTrend']);
});

// Vendor Routes
Route::get('/vendors', [VendorController::class, 'index']);
Route::get('/vendors/materials/{vendor_number}', [VendorController::class, 'materialsByVendor']);

//
