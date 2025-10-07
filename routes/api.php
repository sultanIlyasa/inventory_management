<?php

use Illuminate\Http\Request;
use App\Http\Controllers\DailyInputController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\MaterialTrackingController;
use App\Http\Controllers\MaterialsController;

use Illuminate\Foundation\Application;
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

// Report Routes
Route::prefix('reports')->group(function () {
    Route::get('/yearly', [ReportController::class, 'yearly']);
    Route::get('/monthly', [ReportController::class, 'monthly']);
    Route::get('/daily', [ReportController::class, 'daily']);
});

// Material Tracking Routes
Route::prefix('tracking')->group(function () {
    Route::get('/active', [MaterialTrackingController::class, 'active']);
    Route::get('/history', [MaterialTrackingController::class, 'history']);
    Route::post('/start', [MaterialTrackingController::class, 'start']);
    Route::post('/end', [MaterialTrackingController::class, 'end']);
});
