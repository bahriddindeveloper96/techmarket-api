<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // File uploads
    Route::post('/upload', [FileController::class, 'upload']);
    Route::post('/delete-file', [FileController::class, 'delete']);

    // Categories
    Route::apiResource('categories', CategoryController::class);
    Route::get('/categories/{category}/products', [CategoryController::class, 'products']);

    // Products
    Route::apiResource('products', ProductController::class);
    Route::get('/featured-products', [ProductController::class, 'featured']);
    
    // Product variants
    Route::prefix('products')->group(function () {
        // Variant stock management
        Route::get('{productId}/variants/{variantId}/stock', [ProductController::class, 'getVariantStock']);
        Route::put('{productId}/variants/{variantId}/stock', [ProductController::class, 'updateVariantStock']);
        
        // Variant price management
        Route::put('{productId}/variants/{variantId}/price', [ProductController::class, 'updateVariantPrice']);
    });
});