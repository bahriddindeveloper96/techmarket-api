<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\DeliveryMethodController;
use App\Http\Controllers\Admin\FileController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\HomeController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    // Auth routes
    Route::post('login', [AuthController::class, 'login']);

    // Protected admin routes
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        // Auth
        Route::get('auth/user', [AuthController::class, 'user']);

        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::get('dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('dashboard/chart-data', [DashboardController::class, 'chartData']);

        // Users management
        
        Route::apiResource('users', AdminUserController::class);
        Route::post('users/{user}/toggle-active', [AdminUserController::class, 'toggleActive']);

        // Categories management
        Route::apiResource('categories', CategoryController::class);
        Route::post('categories/reorder', [CategoryController::class, 'reorder']);
        Route::get('categories/{category}/child-categories', [CategoryController::class, 'childCategories']);
        Route::get('categories/{category}/products', [CategoryController::class, 'products']);
        Route::get('categories/attributes/by-group', [CategoryController::class, 'getAttributesByGroup']);

        // Products management
        Route::prefix('products')->group(function () {
            Route::get('/', [ProductController::class, 'index']);
            Route::post('/', [ProductController::class, 'store']);
            Route::get('/{id}', [ProductController::class, 'show']);
            Route::put('/{id}', [ProductController::class, 'update']);
            Route::delete('/{id}', [ProductController::class, 'destroy']);
            Route::get('/attributes/{categoryId}', [ProductController::class, 'getAttributesByCategory']);
            Route::post('/upload-images', [ProductController::class, 'uploadImages']);
            Route::post('/{product}/toggle-active', [ProductController::class, 'toggleActive']);
            Route::post('/{product}/toggle-featured', [ProductController::class, 'toggleFeatured']);
            Route::post('/bulk-delete', [ProductController::class, 'bulkDelete']);
            Route::post('/bulk-update', [ProductController::class, 'bulkUpdate']);
            Route::get('/{product}/variants', [ProductController::class, 'getVariants']);
            Route::post('/{product}/variants', [ProductController::class, 'addVariant']);
            Route::get('/{product}/variants/{variant}', [ProductController::class, 'getVariant']);
            Route::put('/{product}/variants/{variant}', [ProductController::class, 'updateVariant']);
            Route::delete('/{product}/variants/{variant}', [ProductController::class, 'deleteVariant']);
        });

         // File uploads
        Route::post('/upload', [FileController::class, 'upload']);
        Route::post('/delete-file', [FileController::class, 'delete']);

        // Orders management
        Route::apiResource('orders', OrderController::class);
        Route::post('orders/{order}/status', [OrderController::class, 'updateStatus']);
        Route::get('orders/export', [OrderController::class, 'export']);

        // // Reviews management
        // Route::apiResource('reviews', ReviewController::class);
        // Route::post('reviews/{review}/approve', [ReviewController::class, 'approve']);
        // Route::post('reviews/{review}/reject', [ReviewController::class, 'reject']);

        // // Attributes management
        // Route::apiResource('attributes', AttributeController::class);
        // Route::post('attributes/reorder', [AttributeController::class, 'reorder']);

        // Homepage
        Route::get('/homepage', [HomeController::class, 'index']);
        Route::post('/homepage', [HomeController::class, 'update']);

        // Delivery methods
        Route::apiResource('delivery-methods', DeliveryMethodController::class);
        Route::post('delivery-methods/reorder', [DeliveryMethodController::class, 'reorder']);

        // Payment methods
        Route::apiResource('payment-methods', PaymentMethodController::class);
        Route::post('payment-methods/reorder', [PaymentMethodController::class, 'reorder']);
    });
});
