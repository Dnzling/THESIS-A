<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductCatalog\CategoryController;
use App\Http\Controllers\Api\ProductCatalog\ProductController;
use App\Http\Controllers\Api\ProductCatalog\ProductAssetController;
use App\Http\Controllers\Api\ProductCatalog\ProductVariationController;
use App\Http\Controllers\Api\ProductCatalog\TagController;
use App\Http\Controllers\Api\ProductCatalog\AttributeController;
use App\Http\Controllers\Api\ProductCatalog\DashboardController;

// Product Catalog Routes
Route::prefix('product-catalog')->group(function () {

    Route::get('dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('dashboard/activity', [DashboardController::class, 'activityLog']);

    // Categories
    Route::apiResource('categories', CategoryController::class);
    Route::get('categories/tree/all', [CategoryController::class, 'tree']);
    Route::post('categories/reorder', [CategoryController::class, 'reorder']);
    Route::post('categories/bulk/delete', [CategoryController::class, 'bulkDelete']);

    // Products
    Route::apiResource('products', ProductController::class);
    Route::get('products/{id}/3d-data', [ProductController::class, 'get3dData']);
    Route::post('products/bulk/status', [ProductController::class, 'bulkStatus']);
    Route::post('products/bulk/delete', [ProductController::class, 'bulkDelete']);

    // Product Variations
    Route::apiResource('variations', ProductVariationController::class);
    Route::get('products/{productId}/variations', [ProductVariationController::class, 'getByProduct']);
    Route::post('variations/bulk/stock', [ProductVariationController::class, 'bulkUpdateStock']);

    // Product Assets (3D models, images, etc.)
    Route::prefix('assets')->group(function () {
        Route::get('/{id}/serve', [ProductAssetController::class, 'serve']);
        Route::get('/', [ProductAssetController::class, 'index']);
        Route::get('/{id}', [ProductAssetController::class, 'show']);
        Route::post('/upload', [ProductAssetController::class, 'store']);
        Route::put('/{id}', [ProductAssetController::class, 'update']);
        Route::delete('/{id}', [ProductAssetController::class, 'destroy']);
        Route::post('/reorder', [ProductAssetController::class, 'reorder']);
        Route::get('/product/{productId}', [ProductAssetController::class, 'getByProduct']);
    });

    // Attributes
    Route::apiResource('attributes', AttributeController::class);
    Route::post('attributes/assign-value', [AttributeController::class, 'assignValue']);

    // Tags
    Route::apiResource('tags', TagController::class);
    Route::post('tags/assign-to-product', [TagController::class, 'assignToProduct']);
    Route::post('tags/bulk/delete', [TagController::class, 'bulkDelete']);
});


// Public routes (for 3D viewer, no auth required)
Route::prefix('public/product-catalog')->group(function () {
    Route::get('products/{id}/3d-view', [ProductController::class, 'get3dData']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{id}', [ProductController::class, 'show']);
    Route::get('products/{productId}/variations', [ProductVariationController::class, 'getByProduct']);
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/tree', [CategoryController::class, 'tree']);
});
