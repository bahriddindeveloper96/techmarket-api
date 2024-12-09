<?php

use App\Http\Controllers\Seller\AuthController;
use App\Http\Controllers\Seller\DashboardController;
use App\Http\Controllers\Seller\ProductController;
use App\Http\Controllers\Seller\CategoryController;
use App\Http\Controllers\Seller\OrderController;
use App\Http\Controllers\Seller\ReviewController;
use App\Http\Controllers\Seller\AttributeController;
use App\Http\Controllers\Seller\DeliveryMethodController;
use App\Http\Controllers\Seller\FileController;
use App\Http\Controllers\Seller\PaymentMethodController;
use App\Http\Controllers\Seller\UserController as SellerUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('seller')->group(function () {
    // Auth routes
    Route::post('login', [AuthController::class, 'login']);

    // Protected admin routes
    Route::middleware(['auth:sanctum', 'seller'])->group(function () {
        // Auth
        Route::get('auth/user', [AuthController::class, 'user']);

        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::get('dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('dashboard/chart-data', [DashboardController::class, 'chartData']);

       

        // Products management
        Route::apiResource('products', ProductController::class);
        Route::post('products/{product}/toggle-active', [ProductController::class, 'toggleActive']);
        Route::post('products/{product}/toggle-featured', [ProductController::class, 'toggleFeatured']);
        Route::post('products/bulk-delete', [ProductController::class, 'bulkDelete']);
        Route::post('products/bulk-update', [ProductController::class, 'bulkUpdate']);

         // File uploads
        Route::post('/upload', [FileController::class, 'upload']);
        Route::post('/delete-file', [FileController::class, 'delete']);

        // Categories management
        Route::apiResource('categories', CategoryController::class);
        Route::post('categories/reorder', [CategoryController::class, 'reorder']);

        // Orders management
        Route::apiResource('orders', OrderController::class);
        Route::post('orders/{order}/status', [OrderController::class, 'updateStatus']);
        Route::get('orders/export', [OrderController::class, 'export']);
        
    });
});
