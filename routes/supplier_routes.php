<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Supplier\SupplierController;
use App\Http\Controllers\Api\Supplier\SupplierPerformanceController;
use App\Http\Controllers\Api\Supplier\SupplierPaymentController;
use App\Http\Controllers\Api\Supplier\SupplierRecommendationController;

Route::middleware(['api', 'auth:sanctum'])->prefix('suppliers')->group(function () {
    // ==================== SUPPLIER MANAGEMENT ====================
    Route::get('/', [SupplierController::class, 'index']);
    Route::post('/', [SupplierController::class, 'store']);
    Route::get('/search', [SupplierController::class, 'search']);
    Route::get('/{id}', [SupplierController::class, 'show']);
    Route::put('/{id}', [SupplierController::class, 'update']);
    Route::delete('/{id}', [SupplierController::class, 'destroy']);
    Route::get('/category/{category}', [SupplierController::class, 'getByCategory']);

    // ==================== SUPPLIER PERFORMANCE ====================
    Route::get('/{id}/performance', [SupplierPerformanceController::class, 'getPerformanceMetrics']);
    Route::get('/{id}/performance-history', [SupplierPerformanceController::class, 'getPerformanceHistory']);
    Route::get('/at-risk', [SupplierPerformanceController::class, 'getAtRiskSuppliers']);
    Route::get('/top-performers', [SupplierPerformanceController::class, 'getTopPerformers']);

    // ==================== SUPPLIER PAYMENTS ====================
    Route::get('/{id}/payments', [SupplierPaymentController::class, 'getPaymentHistory']);
    Route::post('/{id}/payments/record', [SupplierPaymentController::class, 'recordPayment']);
    Route::get('/{id}/payments/aging', [SupplierPaymentController::class, 'getAgingReport']);
    Route::get('/{id}/payment-status', [SupplierPaymentController::class, 'getPaymentStatus']);

    // ==================== SUPPLIER RECOMMENDATIONS ====================
    Route::get('/product/{productId}/recommended', [SupplierRecommendationController::class, 'getRecommendedSuppliers']);
    Route::get('/category/{category}/list', [SupplierRecommendationController::class, 'getSuppliersForCategory']);
    Route::get('/{id}/alternatives', [SupplierRecommendationController::class, 'getAlternativeSuppliers']);
});

// Dashboard endpoint
Route::middleware(['api', 'auth:sanctum'])->get('/suppliers/dashboard', function () {
    $totalSuppliers = \App\Models\Supplier::count();
    $activeSuppliers = \App\Models\Supplier::where('status', 'active')->count();
    $inactiveSuppliers = \App\Models\Supplier::where('status', 'inactive')->count();
    $blacklistedSuppliers = \App\Models\Supplier::where('status', 'blacklisted')->count();

    $avgQuality = \App\Models\Supplier::avg('quality_score') ?? 0;
    $avgRating = \App\Models\Supplier::avg('rating') ?? 0;

    $topPerformers = \App\Models\Supplier::where('status', 'active')
        ->orderBy('quality_score', 'desc')
        ->limit(5)
        ->get(['id', 'supplier_name', 'quality_score', 'rating', 'on_time_deliveries']);

    return response()->json([
        'total_suppliers' => $totalSuppliers,
        'active_suppliers' => $activeSuppliers,
        'inactive_suppliers' => $inactiveSuppliers,
        'blacklisted_suppliers' => $blacklistedSuppliers,
        'average_quality' => round($avgQuality, 2),
        'average_rating' => round($avgRating, 2),
        'top_performers' => $topPerformers
    ]);
});
