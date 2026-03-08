<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Hr\ShiftController;
use App\Http\Controllers\Api\Hr\ScheduleTemplateController;
use App\Http\Controllers\Api\Hr\ShiftAssignmentController;
use App\Http\Controllers\Api\Hr\ShiftScheduleController;
use App\Http\Controllers\Api\Hr\AttendanceController;
use App\Http\Controllers\Api\Hr\OvertimeRequestController;
use App\Http\Controllers\Api\Hr\LeaveController;
use App\Http\Controllers\Api\Hr\LeaveBalanceController;
use App\Http\Controllers\Api\Hr\DashboardController;
use App\Http\Controllers\Api\Hr\ShiftSwapRequestController;

// Shifts
Route::apiResource('shifts', ShiftController::class);
Route::get('shifts/{id}/stats', [ShiftController::class, 'getStats']);

// Schedule Templates
Route::apiResource('schedule-templates', ScheduleTemplateController::class);
Route::post('schedule-templates/{id}/generate', [ScheduleTemplateController::class, 'generateSchedule']);

// Shift Assignments
// NOTE: bulk route must be before apiResource to avoid being caught by show()
Route::post('shift-assignments/bulk', [ShiftAssignmentController::class, 'bulkAssign']);
Route::apiResource('shift-assignments', ShiftAssignmentController::class);

// Shift Schedules
// NOTE: bulk route must be before apiResource to avoid being caught by show()
Route::post('shift-schedules/bulk', [ShiftScheduleController::class, 'bulkStore']);
Route::get('weekly-schedule', [ShiftScheduleController::class, 'getWeeklySchedule']);
Route::apiResource('shift-schedules', ShiftScheduleController::class);

// Attendance
// NOTE: static sub-routes must be before apiResource to avoid being caught by show()
Route::post('attendances/clock-in', [AttendanceController::class, 'clockIn']);
Route::get('attendances/report/monthly', [AttendanceController::class, 'getMonthlyReport']);
Route::get('attendances/summary', [AttendanceController::class, 'getAttendanceSummary']);
Route::apiResource('attendances', AttendanceController::class);
Route::put('attendances/{id}/clock-out', [AttendanceController::class, 'clockOut']);
Route::post('attendances/{id}/break/start', [AttendanceController::class, 'startBreak']);
Route::post('attendances/{id}/break/end', [AttendanceController::class, 'endBreak']);
Route::get('attendance/by-employee-number', [AttendanceController::class, 'getAttendanceByEmployeeNumber']);
Route::get('attendance/by-employee-number-paginated', [AttendanceController::class, 'getPaginatedAttendanceByEmployeeNumber']);

// Overtime Requests
Route::apiResource('overtime-requests', OvertimeRequestController::class);
Route::put('overtime-requests/{id}/approve', [OvertimeRequestController::class, 'approve']);
Route::put('overtime-requests/{id}/reject', [OvertimeRequestController::class, 'reject']);

// Leaves
Route::apiResource('leaves', LeaveController::class);
Route::put('leaves/{id}/approve', [LeaveController::class, 'approve']);
Route::put('leaves/{id}/reject', [LeaveController::class, 'reject']);
Route::put('leaves/{id}/cancel', [LeaveController::class, 'cancel']);
Route::get('users/{employeeId}/leaves', [LeaveController::class, 'getUserLeaves']);
Route::get('users/{employeeId}/leaves/upcoming', [LeaveController::class, 'getUpcomingLeaves']);
Route::get('users/{employeeId}/leaves/statistics', [LeaveController::class, 'getUserLeaveStatistics']);

// Leave Balances
Route::apiResource('leave-balances', LeaveBalanceController::class);
Route::get('employees/{employeeId}/leave-balance/{year?}', [LeaveBalanceController::class, 'getEmployeeBalance']);
Route::post('leave-balances/carry-over', [LeaveBalanceController::class, 'carryOver']);

// Dashboard
Route::get('dashboard/today', [DashboardController::class, 'getTodayStats']);
Route::get('dashboard/weekly-attendance', [DashboardController::class, 'getWeeklyAttendance']);

// Shift Swap Requests
// NOTE: named routes must be declared BEFORE apiResource to avoid being caught by show()
Route::get('shift-swap-requests/my-pending', [ShiftSwapRequestController::class, 'myPendingRequests']);
Route::apiResource('shift-swap-requests', ShiftSwapRequestController::class)->except(['update', 'destroy']);
Route::put('shift-swap-requests/{id}/accept', [ShiftSwapRequestController::class, 'accept']);
Route::put('shift-swap-requests/{id}/reject', [ShiftSwapRequestController::class, 'reject']);
Route::put('shift-swap-requests/{id}/cancel', [ShiftSwapRequestController::class, 'cancel']);
