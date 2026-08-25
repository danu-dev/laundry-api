<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ServiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user()->load('outlet');
    });

    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Finance
    Route::get('/finance/summary', [FinanceController::class, 'summary']);
    Route::apiResource('expenses', ExpenseController::class);

    Route::apiResource('orders', OrderController::class);
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('services', ServiceController::class);
    Route::apiResource('inventory', InventoryController::class);
});

// Public route for tracking order
Route::get('/track/{orderNumber}', [OrderController::class, 'track']);
