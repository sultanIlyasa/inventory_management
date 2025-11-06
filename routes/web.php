<?php

use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushNotificationController;
use App\Http\Controllers\RecoveryDaysController;
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

Route::get('/dashboard', function () {
    return Inertia::render('DashboardAdmin');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');;
});

Route::get('/daily-input', function () {
    return Inertia::render('DailyInput/index');
})->name('daily-input.index');

Route::prefix('/warehouse-monitoring')->group(function () {
    Route::get('/', function () {
        return Inertia::render('WarehouseMonitoring/index');
    })->name('warehouse-monitoring.index');

    Route::get('/leaderboard', [LeaderboardController::class, 'index'])
        ->name('warehouse-monitoring.leaderboard');
    Route::get('/recovery-days', [RecoveryDaysController::class, 'index'])
        ->name('warehouse-monitoring.recovery-days');

    Route::get('/status-change', function () {
        return Inertia::render('WarehouseMonitoring/StatusChange');
    })->name('warehouse-monitoring.status-change');

    Route::prefix('api')->name('warehouse-monitoring.api.')->group(function () {
        Route::get('/caution', [LeaderboardController::class, 'cautionApi'])->name('caution');
        Route::get('/shortage', [LeaderboardController::class, 'shortageApi'])->name('shortage');
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
