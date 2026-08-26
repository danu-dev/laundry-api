<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BusinessController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ExtraController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PublicTrackingController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\ServiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public read-only tracking endpoint with aggressive throttling (10 per minute)
    Route::get('/public/orders/{trackingToken}', [PublicTrackingController::class, 'show'])->middleware('throttle:10,1');

    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware([])->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    Route::middleware([])->group(function () {
        Route::get('/business', [BusinessController::class, 'show']);
        Route::patch('/business', [BusinessController::class, 'update']);

        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('services', ServiceController::class);
        Route::apiResource('extras', ExtraController::class);

        Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
        Route::post('/orders/{order}/payments', [PaymentController::class, 'store']);

        Route::get('/dashboard', [DashboardController::class, 'show']);
        Route::get('/reports/summary', [ReportController::class, 'summary']);

        Route::apiResource('inventory', InventoryController::class)->parameters(['inventory' => 'item']);
        Route::post('/inventory/{item}/adjust', [InventoryController::class, 'adjust']);
    });
});
