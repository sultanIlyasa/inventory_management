<?php

use Illuminate\Http\Request;
use App\Http\Controllers\DailyInputController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\MaterialsController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AnnualInventoryController;

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
Route::post('/daily-input/sync-status', [DailyInputController::class, 'syncDailyInputStatus']);

// Vendor Routes
Route::get('/vendors', [VendorController::class, 'index']);
Route::get('/vendors/materials/{vendor_number}', [VendorController::class, 'materialsByVendor']);

// Annual Inventory Routes
Route::get('/annual-inventory', [AnnualInventoryController::class, 'index']);
Route::get('/annual-inventory/search', [AnnualInventoryController::class, 'search']);
Route::get('/annual-inventory/statistics', [AnnualInventoryController::class, 'statistics']);
Route::get('/annual-inventory/locations', [AnnualInventoryController::class, 'locations']);
Route::get('/annual-inventory/template', [AnnualInventoryController::class, 'pidTemplate']);
Route::get('/annual-inventory/discrepancy', [AnnualInventoryController::class, 'discrepancy']);
Route::get('/annual-inventory/discrepancy/template', [AnnualInventoryController::class, 'discrepancyTemplate']);
Route::post('/annual-inventory/discrepancy/import', [AnnualInventoryController::class, 'discrepancyImport']);
Route::post('/annual-inventory/discrepancy/bulk-update', [AnnualInventoryController::class, 'bulkUpdateDiscrepancy']);
Route::get('/annual-inventory/pids-dropdown', [AnnualInventoryController::class, 'pidsDropdown']);
Route::get('/annual-inventory/export', [AnnualInventoryController::class, 'export']);
Route::get('/annual-inventory/pid/{pid}', [AnnualInventoryController::class, 'showByPID']);
Route::get('/annual-inventory/by-pid/{pid}', [AnnualInventoryController::class, 'showByPIDWithPagination']);
Route::post('/annual-inventory/importpid', [AnnualInventoryController::class, 'importPID']);
Route::put('/annual-inventory/items/{itemId}', [AnnualInventoryController::class, 'updateItem']);
Route::post('/annual-inventory/items/bulk-update', [AnnualInventoryController::class, 'bulkUpdateItems']);
Route::post('/annual-inventory/items/{itemId}/upload-image', [AnnualInventoryController::class, 'uploadImage']);
Route::get('/annual-inventory/{id}', [AnnualInventoryController::class, 'show'])->where('id', '[0-9]+');
Route::put('/annual-inventory/{id}', [AnnualInventoryController::class, 'update'])->where('id', '[0-9]+');
Route::delete('/annual-inventory/{id}', [AnnualInventoryController::class, 'destroy'])->where('id', '[0-9]+');
