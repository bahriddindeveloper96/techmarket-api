<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\DeliveryMethodController;
use App\Http\Controllers\Admin\PaymentMethodController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    // Auth routes
    Route::post('login', [AuthController::class, 'login']);
    
    // Protected admin routes
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
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

        // Categories management
        Route::apiResource('categories', CategoryController::class);
        Route::post('categories/reorder', [CategoryController::class, 'reorder']);

        // Orders management
        Route::apiResource('orders', OrderController::class);
        Route::post('orders/{order}/status', [OrderController::class, 'updateStatus']);
        Route::get('orders/export', [OrderController::class, 'export']);

        // Users management
        Route::apiResource('users', UserController::class);
        Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive']);

        // Reviews management
        Route::apiResource('reviews', ReviewController::class);
        Route::post('reviews/{review}/approve', [ReviewController::class, 'approve']);
        Route::post('reviews/{review}/reject', [ReviewController::class, 'reject']);

        // Attributes management
        Route::apiResource('attributes', AttributeController::class);
        Route::apiResource('attribute-groups', AttributeController::class);

        // Delivery methods
        Route::apiResource('delivery-methods', DeliveryMethodController::class);
        Route::post('delivery-methods/{method}/toggle-active', [DeliveryMethodController::class, 'toggleActive']);

        // Payment methods
        Route::apiResource('payment-methods', PaymentMethodController::class);
        Route::post('payment-methods/{method}/toggle-active', [PaymentMethodController::class, 'toggleActive']);

        // File management
        Route::post('upload', [FileController::class, 'upload']);
        Route::delete('files/{file}', [FileController::class, 'destroy']);
    });
});
