<?php

use App\Http\Controllers\Api\Admin\RolePermissionController;
use App\Http\Controllers\Api\Store\StoreVerificationController;

use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\Store\StoreController;
use App\Http\Controllers\Api\Store\BranchController;

use App\Http\Controllers\Api\Hr\EmployeeController;
use App\Http\Controllers\Api\Hr\PayPeriodController;
use App\Http\Controllers\Api\Hr\DeductionTypeController;
use App\Http\Controllers\Api\Hr\PayrollController;
use App\Http\Controllers\Api\Hr\DepartmentController;
use App\Http\Controllers\Api\UserNavigationController;


// ========== RATE LIMITING ==========
RateLimiter::for('login', fn(Request $request) => Limit::perMinute(100)->by($request->ip()));
RateLimiter::for('api', fn(Request $request) => Limit::perMinute(1000)->by($request->user()?->id ?: $request->ip()));
RateLimiter::for('register', fn(Request $request) => Limit::perHour(100)->by($request->ip()));
RateLimiter::for('password-reset', fn(Request $request) => Limit::perHour(5)->by($request->ip()));

// ========== PUBLIC ROUTES ==========
Route::prefix('auth')->group(function () {
    Route::middleware('throttle:login')->post('login', [AuthController::class, 'login']);
    Route::middleware('throttle:login-with-clock-in')->post('login-with-clock-in', [AuthController::class, 'loginWithClockIn']);
    Route::middleware('throttle:register')->post('register', [AuthController::class, 'register']);
    Route::middleware('throttle:password-reset')->post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::middleware('throttle:password-reset')->post('reset-password', [AuthController::class, 'resetPassword']);

    // Email Verification (public routes with temporary token)
    Route::post('verify-otp', [VerifyEmailController::class, 'verifyOtpApi']);
    Route::post('resend-otp', [VerifyEmailController::class, 'resendOtpApi']);
});

// ========== PROTECTED ROUTES ==========
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/user/navigation', [UserNavigationController::class, 'getUserNavigation']);
    Route::post('/user/check-permission', [UserNavigationController::class, 'checkPermission']);

    Route::prefix('admin')->group(function () {
        Route::get('/roles', [RolePermissionController::class, 'getRoles']);
        Route::get('/roles/{id}/permissions', [RolePermissionController::class, 'getRolePermissions']);
        Route::post('/roles/{id}/permissions', [RolePermissionController::class, 'updateRolePermissions']);

        // Permissions
        Route::get('/permissions', [RolePermissionController::class, 'getPermissions']);
        Route::post('/permissions', [RolePermissionController::class, 'createPermission']);
        Route::put('/permissions/{id}', [RolePermissionController::class, 'updatePermission']);
        Route::delete('/permissions/{id}', [RolePermissionController::class, 'deletePermission']);

        // Navigation Items
        Route::get('/navigation-items', [RolePermissionController::class, 'getNavigationItems']);
        Route::post('/navigation-items', [RolePermissionController::class, 'createNavigationItem']);
        Route::put('/navigation-items/{id}', [RolePermissionController::class, 'updateNavigationItem']);
        Route::delete('/navigation-items/{id}', [RolePermissionController::class, 'deleteNavigationItem']);
    });



    // ========== AUTHENTICATION ==========
    Route::prefix('auth')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-with-clock-out', [AuthController::class, 'logoutWithClockOut']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('change-password', [AuthController::class, 'changePassword']);

        // User info
        Route::get('user', fn(Request $request) => response()->json([
            'success' => true,
            'user' => $request->user(),
            'email_verified' => $request->user()->hasVerifiedEmail()
        ]));
    });

    // ========== PROFILE ==========
    Route::prefix('profile')->controller(ProfileController::class)->group(function () {
        Route::get('/', 'show');
        Route::put('/', 'update');
        Route::post('avatar', 'updateAvatar');
        Route::delete('avatar', 'removeAvatar');
    });

    // ========== USER MANAGEMENT ==========
    Route::apiResource('users', UserController::class);

    Route::prefix('users')->group(function () {});

    // =========== HR ==============
    Route::apiResource('employees', EmployeeController::class);
    Route::get('/employees/{id}/details', [EmployeeController::class, 'getEmployeeDetails']);

    // Simplified employee summary (for dashboard/widgets)
    Route::get('/employees/{id}/summary', [EmployeeController::class, 'getEmployeeSummary']);

    // Optional: With year filter
    Route::get('/employees/{id}/details/{year}', [EmployeeController::class, 'getEmployeeDetails']);
    // Route::get('roles')


    // Departments
    Route::apiResource('departments', DepartmentController::class);
    Route::get('departments-statistics', [DepartmentController::class, 'statistics']);
    Route::get('departments-options', [DepartmentController::class, 'options']);
    Route::post('departments-bulk-destroy', [DepartmentController::class, 'bulkDestroy']);


    Route::prefix('payroll')->group(function () {
        // Pay Periods
        Route::prefix('periods')->group(function () {
            Route::get('/', [PayPeriodController::class, 'index']);
            Route::post('/', [PayPeriodController::class, 'store']);
            Route::put('/{id}', [PayPeriodController::class, 'update']);
            Route::delete('/{id}', [PayPeriodController::class, 'destroy']);
            Route::post('/{id}/close', [PayPeriodController::class, 'close']);
        });

        // Payroll Overview
        Route::get('/overview', [PayrollController::class, 'overview']);

        // Payroll
        Route::get('/pay-periods', [PayPeriodController::class, 'getAllPayPeriods']);
        Route::get('/pay-periods/{id}/payroll', [PayPeriodController::class, 'getPayrollPerPeriod']);
        Route::get('/pay-periods/{id}/export', [PayPeriodController::class, 'exportPayrollPeriod']);
        Route::get('/pay-periods/{id}', [PayPeriodController::class, 'show']);

        Route::post('/generate', [PayrollController::class, 'generate']);
        Route::post('/bulk-submit', [PayrollController::class, 'bulkSubmitForApproval']);
        Route::post('/bulk-approve', [PayrollController::class, 'bulkApprove']);
        Route::get('/payslip/{employeeId}', [PayrollController::class, 'getEmployeePayslips']);

        Route::get('/', [PayrollController::class, 'index']);
        Route::get('/getEmployeesBasicSalary', [PayrollController::class, 'getEmployeeBasicSalary']);
        Route::get('/report/summary', [PayrollController::class, 'report']);
        Route::post('/calculate', [PayrollController::class, 'testCalculatePayroll']);
        Route::get('/{id}', [PayrollController::class, 'show']);
        Route::put('/{id}', [PayrollController::class, 'update']);
        Route::post('/{id}/submit', [PayrollController::class, 'submit']);
        Route::post('/{id}/approve', [PayrollController::class, 'approve']);
        Route::post('/{id}/mark-paid', [PayrollController::class, 'markPaid']);
    });

    // Deductions
    Route::prefix('deductions')->group(function () {
        Route::get('/deduction-types', [DeductionTypeController::class, 'index']);
        Route::post('/deduction-types', [DeductionTypeController::class, 'store']);
        Route::get('/deduction-types/{id}', [DeductionTypeController::class, 'show']);
        Route::put('/deduction-types/{id}', [DeductionTypeController::class, 'update']);
        Route::delete('/deduction-types/{id}', [DeductionTypeController::class, 'destroy']);
        Route::post('/deduction-types/{id}/toggle-active', [DeductionTypeController::class, 'toggleActive']);
        Route::get('/deduction-types/by-category', [DeductionTypeController::class, 'getByCategory']);
    });


    // ========== STORE MANAGEMENT ==========
    Route::get('pending-verification', [StoreVerificationController::class, 'getPendingVerifications']);
    Route::post('store-verification/{verification}/review', [StoreVerificationController::class, 'reviewVerification']);
    Route::prefix('stores')->controller(StoreController::class)->group(function () {
        Route::get('hasStore', 'hasStore');
        Route::post('register', 'store'); // Store registration

        // Specific Store Operations
        Route::prefix('{store}')->group(function () {
            Route::get('/', 'show');
            Route::delete('/', 'destroy');
            Route::put('/', 'update');

            // Store Verification
            Route::prefix('verification')->group(function () {
                Route::post('submit', [StoreVerificationController::class, 'submitDocuments']);
                Route::get('status', [StoreVerificationController::class, 'getStatus']);
                Route::get('documents', [StoreVerificationController::class, 'getDocuments']);
            });



            // Store Assignment
            Route::post('assign', [UserController::class, 'assignToStore'])->middleware('role:admin');
        });
    });

    // Store Branches
    Route::prefix('branches')->controller(BranchController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('{branch}', 'show');
        Route::put('{branch}', 'update');
        Route::delete('{branch}', 'destroy');
    });

    require __DIR__ . '/attendance_routes.php';
    require __DIR__ . '/product_routes.php';
    require __DIR__ . '/procurement_routes.php';
    require __DIR__ . '/inventory_routes.php';

    // ========== TEST ROUTES (Remove in production) ==========
    Route::prefix('test')->group(function () {
        Route::get('users/{id}', function ($id) {
            return response()->json([
                'received_id' => $id,
                'type' => gettype($id),
                'test' => 'working'
            ]);
        });
    });
}); // ✅ Also add a public route (if you want unauthenticated access)