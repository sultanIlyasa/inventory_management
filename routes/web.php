<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\CheckComplianceController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushNotificationController;
use App\Http\Controllers\OverdueDaysController;
use App\Http\Controllers\RecoveryDaysController;
use App\Http\Controllers\StatusChangeController;
use App\Http\Controllers\WarehouseMonitoringController;
use App\Http\Controllers\MaterialBulkController;
use App\Http\Controllers\DiscrepancyController;
use App\Http\Controllers\AnnualInventoryController;




use App\Models\DailyInput;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home/index', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('homepage.index');

Route::get('/testpage', function () {
    return Inertia::render('Testing/index');
})->name('testpage.index');



Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/admin/materials-bulk', [AdminDashboardController::class, 'materialBulk'])->middleware(['auth', 'verified'])->name('material-bulk');
Route::get('/admin/vendor/all', [AdminDashboardController::class, 'getAllVendorsAdminApi'])->middleware(['auth', 'verified'])->name('admin-vendor-api');
Route::post('/admin/vendors', [AdminDashboardController::class, 'vendorStore'])->name('admin.vendors.store');
Route::patch('/admin/vendors/{id}', [AdminDashboardController::class, 'updateVendorz'])->name('admin.vendors.update');
Route::delete('/admin/vendors/{id}', [AdminDashboardController::class, 'destroyVendor'])->name('admin.vendors.destroy');
Route::post('/admin/vendors/{vendorId}/materials', [AdminDashboardController::class, 'materialStore'])->name('admin.materials.store');
Route::patch('/admin/materials/{id}', [AdminDashboardController::class, 'update'])->name('admin.materials.update');
Route::delete('/admin/materials/{id}', [AdminDashboardController::class, 'destroyMaterial'])->name('admin.materials.destroy');
Route::patch('/admin/materials/{id}/remove', [AdminDashboardController::class, 'removeMaterials'])->name('admin.materials.remove');
Route::patch('/admin/materials/{id}/attach', [AdminDashboardController::class, 'attachMaterialToVendor'])->name('admin.materials.attach');

// vendorless materials list (search + pagination)
Route::get('/admin/materials/vendorless', [AdminDashboardController::class, 'vendorlessMaterials'])->name('materials.vendorless');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/admin/materials/export', [MaterialBulkController::class, 'export'])->name('admin.materials.export');
    Route::post('/admin/materials/import', [MaterialBulkController::class, 'import'])->name('admin.materials.import');
    Route::get('/materials/search', [AdminDashboardController::class, 'searchMaterials'])
        ->name('admin.materials.search');
});


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');;
});

Route::get('/daily-input', function () {
    return Inertia::render('DailyInput/index');
})->name('daily-input.index');

// Annual Inventory Routes
Route::get('/annual-inventory', function () {
    return Inertia::render('AnnualInventory/index');
})->name('annual-inventory.index');

Route::get('/annual-inventory/discrepancy', function () {
    return Inertia::render('AnnualInventory/discrepancy');
})->name('annual-inventory.discrepancy');

Route::get('/annual-inventory/{pid}', function ($pid) {
    return Inertia::render('AnnualInventory/show', ['pid' => $pid]);
})->name('annual-inventory.show')->where('pid', '.*');

// Discrepancy Dashboard
Route::get('/discrepancy-dashboard', [DiscrepancyController::class, 'index'])->name('discrepancy.index');

// Discrepancy API Routes
Route::prefix('api/discrepancy')->name('api.discrepancy.')->group(function () {
    Route::get('/', [DiscrepancyController::class, 'getDiscrepancyData'])->name('data');
    Route::get('/template', [DiscrepancyController::class, 'downloadTemplate'])->name('template');
    Route::get('/export', [DiscrepancyController::class, 'export'])->name('export');
    Route::post('/import', [DiscrepancyController::class, 'import'])->name('import');
    Route::post('/sync', [DiscrepancyController::class, 'sync'])->name('sync');
    Route::post('/bulk-update', [DiscrepancyController::class, 'bulkUpdate'])->name('bulk-update');
    Route::patch('/{materialId}', [DiscrepancyController::class, 'update'])->name('update');
});


Route::prefix('/warehouse-monitoring')->group(function () {
    Route::get('/', [WarehouseMonitoringController::class, 'index'])->name('warehouse-monitoring.index');

    Route::get('/overdue-days', [OverdueDaysController::class, 'index'])
        ->name('warehouse-monitoring.overdue-days');

    Route::get('/check-compliance', [CheckComplianceController::class, 'index'])
        ->name('warehouse-monitoring.check-compliance');

    Route::get('/leaderboard', [LeaderboardController::class, 'index'])
        ->name('warehouse-monitoring.leaderboard');
    Route::get('/recovery-days', [RecoveryDaysController::class, 'index'])
        ->name('warehouse-monitoring.recovery-days');
    Route::get('/status-change', [StatusChangeController::class, 'index'])
        ->name('warehouse-monitoring.status-change');
    Route::prefix('api')->name('warehouse-monitoring.api.')->group(function () {
        Route::get('/caution', [LeaderboardController::class, 'cautionApi'])->name('caution');
        Route::get('/shortage', [LeaderboardController::class, 'shortageApi'])->name('shortage');
        Route::get('/recovery-days', [RecoveryDaysController::class, 'recoveryApi'])->name('recovery-days');
        Route::get('/status-change-api', [StatusChangeController::class, 'statusChangeApi'])
            ->name('status-change-api');
        Route::get('/dashboard', [WarehouseMonitoringController::class, 'dashboardApi'])
            ->name('dashboard');
    });
});

// ✅ NO auth middleware - anyone can subscribe!
Route::prefix('api')->group(function () {
    // Push notification routes
    Route::post('/push-subscribe', [PushNotificationController::class, 'subscribe']);
    Route::post('/push-unsubscribe', [PushNotificationController::class, 'unsubscribe']);
    Route::post('/test-notification', [PushNotificationController::class, 'testNotification']);
});


require __DIR__ . '/auth.php';
