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
use App\Http\Controllers\Api\ProductCatalog\PromotionController;
use App\Http\Controllers\Api\ProductCatalog\DiscountRequestController;
use App\Http\Controllers\Api\CRM\VoucherController;
use App\Http\Controllers\Api\Merchandising\Model3dRequestController;
use App\Http\Controllers\Api\Inventory\ProductController as InventoryProductController;

// Product Catalog Routes
Route::prefix('product-catalog')->group(function () {

    // Merchandising reports and store 3D model requests belong to Product Catalog.
    Route::get('reports', [DashboardController::class, 'reports'])->middleware('can:merchandising.reports.view');
    Route::get('3d-model-requests', [Model3dRequestController::class, 'index'])->middleware('can:merchandising.3d.manage');
    Route::post('3d-model-requests', [Model3dRequestController::class, 'store'])->middleware('can:merchandising.3d.manage');
    Route::get('3d-model-requests/{modelRequest}', [Model3dRequestController::class, 'show'])->middleware('can:merchandising.3d.manage');
    Route::get('3d-model-requests/{modelRequest}/download', [Model3dRequestController::class, 'download'])->middleware('can:merchandising.3d.manage');
    Route::post('3d-model-requests/{modelRequest}/store-action', [Model3dRequestController::class, 'storeAction'])->middleware('can:merchandising.3d.manage');
    Route::post('3d-model-requests/{modelRequest}/admin-action', [Model3dRequestController::class, 'adminAction'])->middleware('can:merchandising.3d.manage');

    Route::get('dashboard/stats', [DashboardController::class, 'stats'])->middleware('can:merchandising.dashboard.view');
    Route::get('dashboard/overview', [DashboardController::class, 'overview'])->middleware('can:merchandising.dashboard.view');
    Route::get('dashboard/trend', [\App\Http\Controllers\Api\DashboardTrendController::class, 'ecommerce'])->middleware('can:merchandising.dashboard.view');
    Route::get('dashboard/activity', [DashboardController::class, 'activityLog'])->middleware('can:merchandising.dashboard.view');

    // Categories
    Route::apiResource('categories', CategoryController::class)
        ->only(['index', 'show'])->middleware('can:merchandising.categories.view')
        ;
    Route::apiResource('categories', CategoryController::class)
        ->except(['index', 'show'])->middleware('can:merchandising.categories.manage')
        ;
    Route::get('categories/tree/all', [CategoryController::class, 'tree']);
    Route::post('categories/reorder', [CategoryController::class, 'reorder']);
    Route::post('categories/bulk/delete', [CategoryController::class, 'bulkDelete']);

    // Products
    Route::get('products/archived', [InventoryProductController::class, 'archived'])->middleware('can:merchandising.products.view');
    Route::apiResource('products', ProductController::class)
        ->only(['index', 'show'])->middleware('can:merchandising.products.view')
        ;
    Route::apiResource('products', ProductController::class)
        ->except(['index', 'show', 'store'])->middleware('can:merchandising.products.manage');
    // Use the full merchandising creation workflow while keeping its API in Product Catalog.
    Route::post('products', [InventoryProductController::class, 'store'])
        ->middleware(['subscription.capacity:products', 'can:merchandising.products.manage']);
        ;
    Route::post('products/{productId}/3d-model', [ProductAssetController::class, 'storeForProductForm'])
        ->middleware('can:merchandising.assets.manage')->name('products.3d-model.store');
    Route::post('products/{id}/price/approve', [ProductController::class, 'approvePrice'])->middleware('can:merchandising.products.approve');
    Route::post('products/{id}/price/reject', [ProductController::class, 'rejectPrice'])->middleware('can:merchandising.products.approve');
    Route::post('products/{id}/archive', [InventoryProductController::class, 'archive'])->whereNumber('id')->middleware('can:merchandising.products.manage');
    Route::post('products/{id}/restore', [InventoryProductController::class, 'restore'])->whereNumber('id')->middleware('can:merchandising.products.manage');
    Route::delete('products/{id}/purge', [InventoryProductController::class, 'purgeArchived'])->whereNumber('id')->middleware('can:merchandising.products.delete');
    Route::get('products/{id}/3d-data', [ProductController::class, 'get3dData'])->middleware('can:merchandising.products.view');
    Route::post('products/bulk/status', [ProductController::class, 'bulkStatus'])->middleware('can:merchandising.products.manage');
    Route::post('products/bulk/delete', [ProductController::class, 'bulkDelete'])->middleware('can:merchandising.products.delete');

    // Product Variations
    Route::get('variation-requests', [ProductVariationController::class, 'requests'])->middleware('can:merchandising.variations.view');
    Route::apiResource('variations', ProductVariationController::class)
        ->only(['index', 'show'])->middleware('can:merchandising.variations.view')
        ;
    Route::apiResource('variations', ProductVariationController::class)
        ->only(['store', 'update'])->middleware('can:merchandising.variations.manage')
        ;
    Route::post('variations/{id}/archive', [ProductVariationController::class, 'archive'])->middleware('can:merchandising.variations.manage');
    Route::get('products/{productId}/variations', [ProductVariationController::class, 'getByProduct'])->middleware('can:merchandising.variations.view');
    Route::post('products/{productId}/variations/initialize-standard', [ProductVariationController::class, 'initializeStandard'])->middleware('can:merchandising.variations.manage');

    // Product Assets (3D models, images, etc.)
    Route::prefix('assets')->group(function () {
        Route::get('/', [ProductAssetController::class, 'index'])->middleware('can:merchandising.assets.view');
        Route::get('/{id}', [ProductAssetController::class, 'show'])->middleware('can:merchandising.assets.view');
        Route::post('/upload', [ProductAssetController::class, 'store'])->middleware('can:merchandising.assets.manage');
        Route::put('/{id}', [ProductAssetController::class, 'update'])->middleware('can:merchandising.assets.manage');
        Route::delete('/{id}', [ProductAssetController::class, 'destroy'])->middleware(['can:merchandising.assets.delete']);
        Route::post('/reorder', [ProductAssetController::class, 'reorder'])->middleware('can:merchandising.assets.manage');
        Route::get('/product/{productId}', [ProductAssetController::class, 'getByProduct'])->middleware('can:merchandising.assets.view');
    });

    // 3D Reconstruction (photo set -> generated 3D model)
    Route::prefix('3d-reconstructions')->middleware('can:merchandising.assets.view')->group(function () {
        Route::get('/', [Product3DReconstructionController::class, 'index']);
        Route::post('/', [Product3DReconstructionController::class, 'store'])->middleware('can:merchandising.3d.manage');
        Route::get('/{id}', [Product3DReconstructionController::class, 'show']);
        Route::get('/{id}/status', [Product3DReconstructionController::class, 'status']);
        Route::post('/{id}/cancel', [Product3DReconstructionController::class, 'cancel'])->middleware('can:merchandising.3d.manage');
        Route::get('/{id}/result', [Product3DReconstructionController::class, 'result']);
    });

    // Attributes
    Route::apiResource('attributes', AttributeController::class)
        ->only(['index', 'show'])->middleware('can:merchandising.attributes.view')
        ;
    Route::apiResource('attributes', AttributeController::class)
        ->except(['index', 'show'])->middleware('can:merchandising.attributes.manage')
        ;
    Route::post('attributes/assign-value', [AttributeController::class, 'assignValue'])->middleware('can:merchandising.attributes.manage');

    // Tags
    Route::apiResource('tags', TagController::class)->middlewareFor(['index', 'show'], 'can:merchandising.tags.view')->middlewareFor(['store', 'update', 'destroy'], 'can:merchandising.tags.manage');
    Route::post('tags/assign-to-product', [TagController::class, 'assignToProduct'])->middleware('can:merchandising.tags.manage');
    Route::post('tags/bulk/delete', [TagController::class, 'bulkDelete'])->middleware('can:merchandising.tags.manage');

    // Store-scoped promotions
    Route::get('discount-requests', [DiscountRequestController::class, 'index']);
    Route::post('discount-requests', [DiscountRequestController::class, 'store']);
    Route::get('promotions/options', [PromotionController::class, 'options']);
    Route::post('promotions/{id}/toggle', [PromotionController::class, 'toggle']);
    Route::post('promotions/{id}/duplicate', [PromotionController::class, 'duplicate']);
    Route::apiResource('promotions', PromotionController::class)->except(['show']);

    // Vouchers are customer-entered offers, managed separately from promotions.
    Route::get('vouchers', [VoucherController::class, 'index'])->middleware('can:merchandising.vouchers.view');
    Route::post('vouchers', [VoucherController::class, 'store'])->middleware('can:merchandising.vouchers.manage');
    Route::get('vouchers/{voucher}', [VoucherController::class, 'show'])->middleware('can:merchandising.vouchers.view');
    Route::put('vouchers/{voucher}', [VoucherController::class, 'update'])->middleware('can:merchandising.vouchers.manage');

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
