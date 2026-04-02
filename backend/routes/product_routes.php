<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductCatalog\CategoryController;
use App\Http\Controllers\Api\ProductCatalog\ProductController;
use App\Http\Controllers\Api\ProductCatalog\ProductAssetController;
use App\Http\Controllers\Api\ProductCatalog\ProductVariationController;
use App\Http\Controllers\Api\ProductCatalog\TagController;
use App\Http\Controllers\Api\ProductCatalog\AttributeController;
use App\Http\Controllers\Api\ProductCatalog\DashboardController;
use App\Http\Controllers\Api\ProductCatalog\DeliveryFeeSettingController;
use App\Http\Controllers\Api\ProductCatalog\Product3DReconstructionController;

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
    Route::post('products/{id}/price/approve', [ProductController::class, 'approvePrice']);
    Route::post('products/{id}/price/reject', [ProductController::class, 'rejectPrice']);
    Route::post('products/bulk/status', [ProductController::class, 'bulkStatus']);
    Route::post('products/bulk/delete', [ProductController::class, 'bulkDelete']);

    // Product Variations
    Route::apiResource('variations', ProductVariationController::class);
    Route::get('products/{productId}/variations', [ProductVariationController::class, 'getByProduct']);
    Route::post('variations/bulk/stock', [ProductVariationController::class, 'bulkUpdateStock']);

    // Product Assets (3D models, images, etc.)
    Route::prefix('assets')->group(function () {
        Route::get('/', [ProductAssetController::class, 'index']);
        Route::get('/{id}', [ProductAssetController::class, 'show']);
        Route::post('/upload', [ProductAssetController::class, 'store']);
        Route::put('/{id}', [ProductAssetController::class, 'update']);
        Route::delete('/{id}', [ProductAssetController::class, 'destroy']);
        Route::post('/reorder', [ProductAssetController::class, 'reorder']);
        Route::get('/product/{productId}', [ProductAssetController::class, 'getByProduct']);
    });

    // 3D Reconstruction (photo set -> generated 3D model)
    Route::prefix('3d-reconstructions')->group(function () {
        Route::get('/', [Product3DReconstructionController::class, 'index']);
        Route::post('/', [Product3DReconstructionController::class, 'store']);
        Route::get('/{id}', [Product3DReconstructionController::class, 'show']);
        Route::get('/{id}/status', [Product3DReconstructionController::class, 'status']);
        Route::post('/{id}/cancel', [Product3DReconstructionController::class, 'cancel']);
        Route::get('/{id}/result', [Product3DReconstructionController::class, 'result']);
    });

    // Attributes
    Route::apiResource('attributes', AttributeController::class);
    Route::post('attributes/assign-value', [AttributeController::class, 'assignValue']);

    // Tags
    Route::apiResource('tags', TagController::class);
    Route::post('tags/assign-to-product', [TagController::class, 'assignToProduct']);
    Route::post('tags/bulk/delete', [TagController::class, 'bulkDelete']);

    // Delivery Fee Settings (hybrid: fixed + distance + surcharges)
    Route::get('delivery-fee-settings', [DeliveryFeeSettingController::class, 'show']);
    Route::put('delivery-fee-settings', [DeliveryFeeSettingController::class, 'update']);
    Route::post('delivery-fee-settings/estimate', [DeliveryFeeSettingController::class, 'estimate']);
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
