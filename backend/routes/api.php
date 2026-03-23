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
use App\Http\Controllers\Api\ProfileController as ApiProfileController;
use App\Http\Controllers\Api\Store\StoreController;
use App\Http\Controllers\Api\Store\BranchController;

use App\Http\Controllers\Api\Hr\EmployeeController;
use App\Http\Controllers\Api\Hr\PayPeriodController;
use App\Http\Controllers\Api\Hr\DeductionTypeController;
use App\Http\Controllers\Api\Hr\PayrollController;
use App\Http\Controllers\Api\Hr\DepartmentController;
use App\Http\Controllers\Api\UserNavigationController;
use App\Http\Controllers\Api\Store\RoleController as StoreRoleController;
use App\Http\Controllers\Api\Admin\CustomerValidationController;
use App\Http\Controllers\Api\Admin\CustomerManagementController;
use App\Http\Controllers\Api\Admin\SubscriptionManagementController;
use App\Http\Controllers\Api\Admin\StoreManagementController;
use App\Http\Controllers\Api\Customer\CustomerVerificationTriggerController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\Ecommerce\EcommerceController;
use App\Http\Controllers\Api\ProductCatalog\ProductAssetController;


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

require __DIR__ . '/job_portal_routes.php';

// Public ecommerce browsing (guest-friendly)
Route::prefix('ecommerce')->group(function () {
    Route::get('/products', [EcommerceController::class, 'products']);
    Route::get('/products/{id}', [EcommerceController::class, 'productShow']);
    Route::get('/stores', [EcommerceController::class, 'storeDirectory']);
    Route::get('/stores/{storeId}', [EcommerceController::class, 'storeProfile']);
    Route::get('/stores/{storeId}/products', [EcommerceController::class, 'storeProducts']);
    Route::get('/stores/{storeId}/reviews', [EcommerceController::class, 'storeReviews']);
});

// Public 3D/image asset serve route (must stay outside auth middleware)
Route::get('/product-catalog/assets/{id}/serve', [ProductAssetController::class, 'serve']);

// ========== PROTECTED ROUTES ==========

Route::prefix('locations')->group(function () {
    Route::get('/provinces', [\App\Http\Controllers\Api\Location\PSGCController::class, 'provinces']);
    Route::get('/cities', [\App\Http\Controllers\Api\Location\PSGCController::class, 'cities']);
    Route::get('/barangays', [\App\Http\Controllers\Api\Location\PSGCController::class, 'barangays']);
});

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/user/navigation', [UserNavigationController::class, 'getUserNavigation']);
    Route::post('/user/check-permission', [UserNavigationController::class, 'checkPermission']);
    Route::post('/customer-verification/trigger', [CustomerVerificationTriggerController::class, 'trigger']);
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);

    Route::prefix('admin')->group(function () {
        Route::get('/roles', [RolePermissionController::class, 'getRoles']);
        Route::get('/roles/{id}/permissions', [RolePermissionController::class, 'getRolePermissions']);
        Route::post('/roles/{id}/permissions', [RolePermissionController::class, 'updateRolePermissions']);
        Route::get('/roles/export', [RolePermissionController::class, 'exportRoles']);
        Route::post('/roles/import', [RolePermissionController::class, 'importRoles']);

        // Permissions
        Route::get('/permissions', [RolePermissionController::class, 'getPermissions']);
        Route::post('/permissions', [RolePermissionController::class, 'createPermission']);
        Route::put('/permissions/{id}', [RolePermissionController::class, 'updatePermission']);
        Route::delete('/permissions/{id}', [RolePermissionController::class, 'deletePermission']);
        Route::get('/permissions/export', [RolePermissionController::class, 'exportPermissions']);
        Route::post('/permissions/import', [RolePermissionController::class, 'importPermissions']);

        // Navigation Items
        Route::get('/navigation-items', [RolePermissionController::class, 'getNavigationItems']);
        Route::post('/navigation-items', [RolePermissionController::class, 'createNavigationItem']);
        Route::put('/navigation-items/{id}', [RolePermissionController::class, 'updateNavigationItem']);
        Route::delete('/navigation-items/{id}', [RolePermissionController::class, 'deleteNavigationItem']);

        // Customer Validation
        Route::get('/customer-validations', [CustomerValidationController::class, 'index']);
        Route::get('/customer-validations/{id}', [CustomerValidationController::class, 'show']);
        Route::post('/customer-validations/{id}/review', [CustomerValidationController::class, 'review']);

        // Customer Management
        Route::get('/customers', [CustomerManagementController::class, 'index']);
        Route::post('/customers/{id}/require-verification', [CustomerManagementController::class, 'requireVerification']);
        Route::post('/customers/require-verification-bulk', [CustomerManagementController::class, 'requireVerificationBulk']);

        // Subscription Management
        Route::get('/subscriptions', [SubscriptionManagementController::class, 'index']);
        Route::get('/subscriptions/stats', [SubscriptionManagementController::class, 'stats']);
        Route::put('/subscriptions/{store}', [SubscriptionManagementController::class, 'update']);
        Route::post('/subscriptions/{store}/extend', [SubscriptionManagementController::class, 'extend']);

        // Store Management
        Route::get('/stores', [StoreManagementController::class, 'index']);
        Route::get('/stores/{store}', [StoreManagementController::class, 'show']);
});

    // ========== STORE ROLES & PERMISSIONS ==========
    Route::prefix('store')->group(function () {
        Route::get('/roles', [StoreRoleController::class, 'index']);
        Route::post('/roles', [StoreRoleController::class, 'store']);
        Route::put('/roles/{id}', [StoreRoleController::class, 'update']);
        Route::delete('/roles/{id}', [StoreRoleController::class, 'destroy']);
        Route::get('/permissions', [StoreRoleController::class, 'getPermissions']);
        Route::get('/roles/{id}/permissions', [StoreRoleController::class, 'getRolePermissions']);
        Route::post('/roles/{id}/permissions', [StoreRoleController::class, 'updateRolePermissions']);
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
    Route::prefix('profile')->controller(ApiProfileController::class)->group(function () {
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
    Route::get('/employees/me', [EmployeeController::class, 'me']);
    Route::get('/employees/{id}/details', [EmployeeController::class, 'getEmployeeDetails']);
    Route::get('/employees/{id}/details/{year}', [EmployeeController::class, 'getEmployeeDetails']);

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
        Route::get('/{id}/payslip/pdf', [PayrollController::class, 'downloadPayslipPdf']);
        Route::get('/{id}/payslip/print', [PayrollController::class, 'printPayslip']);

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

    // Payslip PDF aliases (legacy frontend usage)
    Route::get('/payrolls/{id}/payslip/pdf', [PayrollController::class, 'downloadPayslipPdf']);
    Route::get('/payrolls/{id}/payslip/print', [PayrollController::class, 'printPayslip']);

    Route::prefix('hr/dashboard')->controller(\App\Http\Controllers\Api\Hr\DashboardController::class)->group(function () {
        Route::get('/today-stats', 'getTodayStats');
        Route::get('/weekly-attendance', 'getWeeklyAttendance');
        Route::get('/monthly-summary', 'getMonthlySummary');
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
    Route::get('store-verifications', [StoreVerificationController::class, 'index']);
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
    require __DIR__ . '/supplier_routes.php';
    require __DIR__ . '/supplier_portal_routes.php';
    require __DIR__ . '/inventory_routes.php';
    require __DIR__ . '/ecommerce_routes.php';
    require __DIR__ . '/sales_routes.php';
    require __DIR__ . '/job_hiring_routes.php';
    require __DIR__ . '/finance_routes.php';

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
