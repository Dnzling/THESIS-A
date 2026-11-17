<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Store\RoleController as StoreRoleController;
use App\Http\Controllers\Api\Store\StoreScopedRoleController;

// Attendance, leave, shifts, holidays, and related HR endpoints.
require __DIR__ . '/attendance_routes.php';

// HR role and position lookups. Controllers enforce store scoping.
Route::prefix('hr/roles')->group(function () {
    Route::get('/', [StoreRoleController::class, 'index']);
    Route::post('/', [StoreRoleController::class, 'store']);
    Route::put('/{id}', [StoreRoleController::class, 'update']);
    Route::delete('/{id}', [StoreRoleController::class, 'destroy']);
    Route::get('/store-specific', [StoreRoleController::class, 'storeSpecific']);
    Route::get('/scoped', [StoreScopedRoleController::class, 'index']);
    Route::get('/scoped-by-department', [StoreScopedRoleController::class, 'scopedByDepartment']);
});
