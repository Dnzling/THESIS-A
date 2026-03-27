<?php

use App\Http\Controllers\StoreVerificationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


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

Route::get('/customer/login', function () {
    return Inertia::render('Auth/CustomerLogin');
})->name('customer.login');

Route::get('/customer/register', function () {
    return Inertia::render('Auth/CustomerRegister');
})->name('customer.register');

Route::get('/verify-otp', function () {
    return Inertia::render('Auth/VerifyOtp');
})->name('verify-otp');

Route::get('/trial-onboarding', function () {
    return Inertia::render('Auth/TrialOnboarding');
})->name('trial-onboarding');

Route::get('/system/store/verification', function () {
    return Inertia::render('Auth/VerifyStore');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
