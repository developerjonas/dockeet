<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware('throttle:api')->group(function () {
    
    // Auth - More restrictive rate limit for login attempts
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    // Protected Routes
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Products
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{product}', [ProductController::class, 'show']);

        // Customers
        Route::apiResource('customers', CustomerController::class);

        // Orders
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
        Route::post('/orders', [OrderController::class, 'store']);

        // Analytics & Reports (Rate Limited)
        Route::middleware('throttle:10,1')->group(function () {
            Route::get('/analytics/dashboard', [AnalyticsController::class, 'dashboard']);
            Route::get('/analytics/sales', [AnalyticsController::class, 'sales']);
            Route::get('/reports/inventory', [ReportController::class, 'inventory']);
        });

        // Notifications
        Route::get('/notifications', [NotificationControllers::class, 'index']);
        Route::get('/notifications/unread', [NotificationController::class, 'unread']);
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    });
});
