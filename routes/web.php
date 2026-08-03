<?php

use App\Http\Controllers\Api\Store\StoreVerificationController;
use App\Http\Controllers\Api\Inventory\InventoryTransactionController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Middleware\VerifyCsrfToken;


Route::prefix('stores/{store}')->group(function () {
    // Store owner submits verification documents
    Route::post('/verification/submit', [StoreVerificationController::class, 'submitDocuments']);

    // Store owner gets verification status
    Route::get('/verification/status', [StoreVerificationController::class, 'getStatus']);
});

Route::get('/', function () {
    return Inertia::render('Marketing/Home', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/about', function () {
    return Inertia::render('Marketing/About');
});

Route::get('/pricing', function () {
    return Inertia::render('Marketing/Pricing');
});

Route::get('/subscription-plans', function () {
    return Inertia::render('Auth/SubscriptionPlans');
})->name('subscription.plans');

Route::get('/subscription-checkout', function () {
    return redirect('/subscription-plans');
})->name('subscription.checkout');

Route::get('/store/registration', function () {
    return Inertia::render('Auth/StoreRegistration');
})->name('store.registration');

Route::get('/customer/login', function () {
    return Inertia::render('Auth/CustomerLogin');
})->name('customer.login');

Route::get('/super-admin/login', function () {
    return Inertia::render('Auth/SuperAdminLogin');
})->name('super-admin.login');

Route::get('/customer/register', function () {
    return Inertia::render('Auth/CustomerRegister');
})->name('customer.register');

Route::get('/supplier/register', function () {
    return Inertia::render('Auth/SupplierRegister');
})->name('supplier.register');

Route::get('/verify-otp', function () {
    return Inertia::render('Auth/VerifyOtp');
})->name('verify-otp');

Route::get('/unauthorized', function () {
    return Inertia::render('Auth/Unauthorized');
})->name('unauthorized');

Route::get('/system/store/verification', function () {
    return Inertia::render('Auth/VerifyStore');
});

Route::get('/dashboard', function () {
    return redirect('/store/index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/inventory/transactions/{id}/print', [InventoryTransactionController::class, 'print'])
        ->name('inventory.transactions.print');
});

Route::post('/logout-no-csrf', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('logout.nocsrf');

require __DIR__ . '/inertia.php';
require __DIR__ . '/auth.php';

// Legacy URL compatibility: keep old path but redirect to canonical route
Route::middleware(['auth', 'role:super_admin'])
    ->get('/admin/supplier-verification', fn() => redirect('/admin/verification/suppliers'));

// Compatibility routes used by the new frontend pages
Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::get('/system/admin/supplier-verifications', function () {
        return Inertia::render('System/Admin/SupplierVerification', ['title' => 'Supplier Verifications']);
    })->name('system.admin.supplier-verifications');

    Route::get('/system/admin/supplier-verifications/{id}', function ($id) {
        return Inertia::render('System/Admin/SupplierVerificationShow', ['id' => $id]);
    })->name('system.admin.supplier-verifications.show');
});
