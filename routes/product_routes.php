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

    Route::get('dashboard/stats', [DashboardController::class, 'stats'])->middleware('can:merchandising.dashboard.view');
    Route::get('dashboard/activity', [DashboardController::class, 'activityLog'])->middleware('can:merchandising.dashboard.view');

    // Categories
    Route::apiResource('categories', CategoryController::class)
        ->middleware([
            'index' => 'can:merchandising.categories.view',
            'show' => 'can:merchandising.categories.view',
            'store' => 'can:merchandising.categories.manage',
            'update' => 'can:merchandising.categories.manage',
            'destroy' => 'can:merchandising.categories.manage',
        ]);
    Route::get('categories/tree/all', [CategoryController::class, 'tree'])->middleware('can:merchandising.categories.view');
    Route::post('categories/reorder', [CategoryController::class, 'reorder'])->middleware('can:merchandising.categories.manage');
    Route::post('categories/bulk/delete', [CategoryController::class, 'bulkDelete'])->middleware('can:merchandising.categories.manage');

    // Products
    Route::apiResource('products', ProductController::class)
        ->middleware([
            'index' => 'can:merchandising.products.view',
            'show' => 'can:merchandising.products.view',
            'store' => 'can:merchandising.products.manage',
            'update' => 'can:merchandising.products.manage',
            'destroy' => 'can:merchandising.products.manage',
        ]);
    Route::get('products/{id}/3d-data', [ProductController::class, 'get3dData'])->middleware('can:merchandising.products.view');
    Route::post('products/{id}/price/approve', [ProductController::class, 'approvePrice'])->middleware('can:merchandising.products.manage');
    Route::post('products/{id}/price/reject', [ProductController::class, 'rejectPrice'])->middleware('can:merchandising.products.manage');
    Route::post('products/bulk/status', [ProductController::class, 'bulkStatus'])->middleware('can:merchandising.products.manage');
    Route::post('products/bulk/delete', [ProductController::class, 'bulkDelete'])->middleware('can:merchandising.products.delete');

    // Product Variations
    Route::apiResource('variations', ProductVariationController::class)
        ->middleware([
            'index' => 'can:merchandising.variations.view',
            'show' => 'can:merchandising.variations.view',
            'store' => 'can:merchandising.variations.manage',
            'update' => 'can:merchandising.variations.manage',
            'destroy' => 'can:merchandising.variations.manage',
        ]);
    Route::get('products/{productId}/variations', [ProductVariationController::class, 'getByProduct'])->middleware('can:merchandising.variations.view');
    Route::post('variations/bulk/stock', [ProductVariationController::class, 'bulkUpdateStock'])->middleware('can:merchandising.variations.manage');

    // Product Assets (3D models, images, etc.)
    Route::prefix('assets')->group(function () {
        Route::get('/', [ProductAssetController::class, 'index'])->middleware('can:merchandising.assets.view');
        Route::get('/{id}', [ProductAssetController::class, 'show'])->middleware('can:merchandising.assets.view');
        Route::post('/upload', [ProductAssetController::class, 'store'])->middleware('can:merchandising.assets.manage');
        Route::put('/{id}', [ProductAssetController::class, 'update'])->middleware('can:merchandising.assets.manage');
        Route::delete('/{id}', [ProductAssetController::class, 'destroy'])->middleware('can:merchandising.assets.manage');
        Route::post('/reorder', [ProductAssetController::class, 'reorder'])->middleware('can:merchandising.assets.manage');
        Route::get('/product/{productId}', [ProductAssetController::class, 'getByProduct'])->middleware('can:merchandising.assets.view');
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
    Route::apiResource('attributes', AttributeController::class)->middleware([
        'index' => 'can:merchandising.attributes.view',
        'show' => 'can:merchandising.attributes.view',
        'store' => 'can:merchandising.attributes.manage',
        'update' => 'can:merchandising.attributes.manage',
        'destroy' => 'can:merchandising.attributes.manage',
    ]);
    Route::post('attributes/assign-value', [AttributeController::class, 'assignValue']);

    // Tags
    Route::apiResource('tags', TagController::class)->middleware([
        'index' => 'can:merchandising.tags.view',
        'show' => 'can:merchandising.tags.view',
        'store' => 'can:merchandising.tags.manage',
        'update' => 'can:merchandising.tags.manage',
        'destroy' => 'can:merchandising.tags.manage',
    ]);
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
