<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Attendance;
use App\Models\Hr\Employee;
use App\Models\Hr\ShiftSchedule;
use App\Models\Hr\Shift;
use App\Models\Hr\PayPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Get all attendances with filtering and pagination
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $query = Attendance::with(['employee', 'shift', 'schedule'])
            ->whereHas('employee', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            });

        // Filter by employee
        if ($request->has('employee_id') && $request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('attendance_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('attendance_date', '<=', $request->end_date);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Search by employee name
        if ($request->has('search') && $request->search) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('fname', 'like', "%{$request->search}%")
                    ->orWhere('lname', 'like', "%{$request->search}%");
            });
        }

        // Filter by month and year
        if ($request->has('month') && $request->month && $request->has('year') && $request->year) {
            $query->whereMonth('attendance_date', $request->month)
                ->whereYear('attendance_date', $request->year);
        }

        $attendances = $query->orderBy('attendance_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        // Calculate summary stats
        $summary = $this->calculateSummary($query);

        return response()->json([
            'success' => true,
            'data' => $attendances,
            'summary' => $summary
        ]);
    }

    /**
     * Calculate summary statistics
     */
    private function calculateSummary($query)
    {
        $allRecords = $query->get();

        return [
            'present' => $allRecords->whereIn('status', ['present'])->count(),
            'late' => $allRecords->where('status', 'late')->count(),
            'absent' => $allRecords->where('status', 'absent')->count(),
            'on_leave' => $allRecords->where('status', 'on_leave')->count(),
            'half_day' => $allRecords->where('status', 'half_day')->count(),
            'holiday' => $allRecords->where('status', 'holiday')->count(),
            'total' => $allRecords->count()
        ];
    }

    /**
     * Store new attendance record
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'schedule_id' => 'nullable|exists:shift_schedules,id',
            'attendance_date' => 'required|date',
            'clock_in' => 'nullable|date',
            'clock_out' => 'nullable|date|after:clock_in',
            'clock_in_method' => 'sometimes|in:biometric,mobile,web,manual',
            'clock_out_method' => 'sometimes|in:biometric,mobile,web,manual',
            'status' => 'sometimes|in:present,absent,late,half_day,on_leave,holiday',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify employee belongs to user's store
        $employee = Employee::where('id', $request->employee_id)
            ->where('store_id', $storeId)
            ->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee does not belong to your store'
            ], 422);
        }

        // Check for duplicate
        $exists = Attendance::where('employee_id', $request->employee_id)
            ->whereDate('attendance_date', $request->attendance_date)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance record already exists for this date'
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Auto-assign schedule if not provided
            if (!$request->schedule_id) {
                $schedule = ShiftSchedule::where('employee_id', $request->employee_id)
                    ->whereDate('schedule_date', $request->attendance_date)
                    ->first();

                if ($schedule) {
                    $request->merge(['schedule_id' => $schedule->id]);
                    $request->merge(['shift_id' => $schedule->shift_id]);
                }
            }

            // Auto-determine status if not provided
            if (!$request->has('status')) {
                if ($request->clock_in && $request->clock_out) {
                    $request->merge(['status' => 'present']);
                } elseif ($request->clock_in) {
                    $request->merge(['status' => 'present']);
                } else {
                    $request->merge(['status' => 'absent']);
                }
            }

            $attendance = Attendance::create($request->all());

            // Calculate late if clock_in is provided
            if ($attendance->clock_in && $attendance->shift_id) {
                $attendance->calculateLate();
            }

            // Calculate total worked if both times are provided
            if ($attendance->clock_in && $attendance->clock_out) {
                $attendance->calculateTotalWorked();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Attendance recorded successfully',
                'data' => $attendance->load(['employee', 'shift', 'schedule'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to record attendance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single attendance record
     */
    public function show($id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $attendance = Attendance::with([
            'employee',
            'shift',
            'schedule',
            'approver',
            'otApprover',
            'overtimeRequest'
        ])
            ->whereHas('employee', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->find($id);

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance record not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $attendance
        ]);
    }

    /**
     * Update attendance record
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $attendance = Attendance::whereHas('employee', function ($q) use ($storeId) {
            $q->where('store_id', $storeId);
        })
            ->find($id);

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance record not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'clock_in' => 'nullable|date',
            'clock_out' => 'nullable|date|after:clock_in',
            'clock_in_method' => 'sometimes|in:biometric,mobile,web,manual',
            'clock_out_method' => 'sometimes|in:biometric,mobile,web,manual',
            'break_start' => 'nullable|date',
            'break_end' => 'nullable|date|after:break_start',
            'status' => 'sometimes|in:present,absent,late,half_day,on_leave,holiday',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $attendance->update($request->all());

            // Recalculate if times changed
            if (
                $request->has('clock_in') || $request->has('clock_out') ||
                $request->has('break_start') || $request->has('break_end')
            ) {
                $attendance->calculateTotalWorked();
                $attendance->calculateLate();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Attendance updated successfully',
                'data' => $attendance->fresh(['employee', 'shift'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update attendance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete attendance record
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $attendance = Attendance::whereHas('employee', function ($q) use ($storeId) {
            $q->where('store_id', $storeId);
        })
            ->find($id);

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance record not found'
            ], 404);
        }

        $attendance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attendance deleted successfully'
        ]);
    }

    /**
     * Get monthly report for an employee
     */
    public function getMonthlyReport(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $employee = Employee::where('id', $request->employee_id)
            ->where('store_id', $storeId)
            ->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee does not belong to your store'
            ], 422);
        }

        $attendances = Attendance::where('employee_id', $request->employee_id)
            ->whereMonth('attendance_date', $request->month)
            ->whereYear('attendance_date', $request->year)
            ->with('shift')
            ->orderBy('attendance_date')
            ->get();

        $summary = [
            'total_days' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'half_day' => $attendances->where('status', 'half_day')->count(),
            'on_leave' => $attendances->where('status', 'on_leave')->count(),
            'holiday' => $attendances->where('status', 'holiday')->count(),
            'total_worked_minutes' => $attendances->sum('total_worked_minutes'),
            'total_late_minutes' => $attendances->sum('late_minutes'),
            'total_overtime_minutes' => $attendances->sum('overtime_minutes'),
            'total_worked_hours' => round($attendances->sum('total_worked_minutes') / 60, 2)
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'employee_id' => $request->employee_id,
                'employee_name' => $employee->fname . ' ' . $employee->lname,
                'month' => $request->month,
                'year' => $request->year,
                'summary' => $summary,
                'attendances' => $attendances
            ]
        ]);
    }

    /**
     * Get attendance summary for pay period
     */
    public function getAttendanceSummary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'pay_period_id' => 'required|exists:pay_periods,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $period = PayPeriod::find($request->pay_period_id);

        if (!$period) {
            return response()->json([
                'success' => false,
                'message' => 'Pay period not found'
            ], 404);
        }

        $attendances = Attendance::where('employee_id', $request->employee_id)
            ->whereBetween('attendance_date', [$period->start_date, $period->end_date])
            ->with('shift')
            ->get();

        $summary = [
            'total_days' => $attendances->count(),
            'present_days' => $attendances->whereIn('status', ['present', 'late'])->count(),
            'absent_days' => $attendances->where('status', 'absent')->count(),
            'late_days' => $attendances->where('status', 'late')->count(),
            'leave_days' => $attendances->where('status', 'on_leave')->count(),
            'holiday_days' => $attendances->where('status', 'holiday')->count(),
            'total_hours_worked' => round($attendances->sum('total_worked_minutes') / 60, 2),
            'total_overtime_hours' => round($attendances->sum('overtime_minutes') / 60, 2),
            'total_late_minutes' => $attendances->sum('late_minutes'),
            'total_night_diff_hours' => round($attendances->sum('night_differential_minutes') / 60, 2),
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
            'attendances' => $attendances
        ]);
    }

    public function clockIn(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        // Validate
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'method' => 'sometimes|in:biometric,mobile,web,manual',
            'location' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Find employee
        $employee = Employee::where('user_id', $request->user_id)
            ->where('store_id', $storeId)
            ->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found'
            ], 200);
        }

        $today = now()->format('Y-m-d');
        $now = now();

        // Check if already clocked in today
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->first();

        // ✅ IMPORTANT: If already clocked in, return 200 with data
        if ($attendance && $attendance->clock_in) {
            return response()->json([
                'success' => true,
                'message' => 'Already clocked in today',
                'already_clocked_in' => true,
                'data' => [
                    'id' => $attendance->id,
                    'clock_in' => $attendance->clock_in->format('Y-m-d H:i:s'),
                    'clock_in_formatted' => $attendance->clock_in->format('h:i A'),
                    'status' => $attendance->status,
                    'late_minutes' => $attendance->late_minutes,
                    'employee_name' => $employee->fname . ' ' . $employee->lname
                ]
            ], 200); // ✅ 200 status, not 422
        }

        DB::beginTransaction();

        try {
            // Get today's schedule
            $schedule = ShiftSchedule::with('shift')
                ->where('employee_id', $employee->id)
                ->whereDate('schedule_date', $today)
                ->first();

            // Create new attendance
            $attendance = Attendance::create([
                'employee_id' => $employee->id,
                'schedule_id' => $schedule->id ?? null,
                'shift_id' => $schedule->shift_id ?? null,
                'attendance_date' => $today,
                'clock_in' => $now,
                'clock_in_method' => $request->method ?? 'manual',
                'clock_in_location' => $request->location,
                'status' => 'present', // Will be updated by calculateLate
            ]);

            // Calculate late minutes (simplest version)
            // Calculate late minutes - FIX: Handle both time-only and datetime formats
            if ($attendance->shift && $attendance->shift->start_time) {
                $startTime = $attendance->shift->start_time;

                // Check if start_time already contains a date
                if (preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $startTime)) {
                    // start_time is already a full datetime
                    $shiftStart = Carbon::parse($startTime);
                } else {
                    // start_time is just a time, concatenate with today's date
                    $shiftStart = Carbon::parse($today . ' ' . $startTime);
                }

                $minutesLate = $shiftStart->diffInMinutes($now, false);
                $gracePeriod = $attendance->shift->grace_period_minutes ?? 15;

                if ($minutesLate > $gracePeriod) {
                    $attendance->late_minutes = $minutesLate - $gracePeriod;
                    $attendance->status = 'late';
                } else {
                    $attendance->late_minutes = 0;
                    $attendance->status = 'present';
                }

                $attendance->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Clocked in successfully',
                'already_clocked_in' => false,
                'data' => [
                    'id' => $attendance->id,
                    'clock_in' => $attendance->clock_in->format('Y-m-d H:i:s'),
                    'clock_in_formatted' => $attendance->clock_in->format('h:i A'),
                    'status' => $attendance->status,
                    'late_minutes' => $attendance->late_minutes,
                    'shift_name' => $schedule->shift->name ?? 'No Shift',
                    'employee_name' => $employee->fname . ' ' . $employee->lname
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to clock in',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function clockOut(Request $request, $id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $attendance = Attendance::whereHas('employee', function ($q) use ($storeId) {
            $q->where('store_id', $storeId);
        })
            ->find($id);

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance record not found'
            ], 404);
        }

        if ($attendance->clock_out) {
            return response()->json([
                'success' => false,
                'message' => 'Already clocked out'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'method' => 'sometimes|in:biometric,mobile,web,manual',
            'location' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $now = now();

            $attendance->update([
                'clock_out' => $now,
                'clock_out_method' => $request->method ?? 'manual',
                'clock_out_location' => $request->location
            ]);

            // Calculate total worked minutes
            $attendance->calculateTotalWorked();

            // Refresh to get calculated values
            $attendance->refresh();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Clocked out successfully',
                'data' => [
                    'id' => $attendance->id,
                    'clock_in' => $attendance->clock_in->format('Y-m-d H:i:s'),
                    'clock_out' => $attendance->clock_out->format('Y-m-d H:i:s'),
                    'total_worked_minutes' => $attendance->total_worked_minutes,
                    'break_minutes' => $attendance->break_minutes ?? 0,
                    'status' => $attendance->status,
                    'employee_name' => $attendance->employee->fname . ' ' . $attendance->employee->lname,
                    'shift_name' => $attendance->shift->name ?? 'No Shift'
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to clock out',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function startBreak($id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $attendance = Attendance::whereHas('employee', function ($q) use ($storeId) {
            $q->where('store_id', $storeId);
        })
            ->find($id);

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance record not found'
            ], 404);
        }

        if (!$attendance->clock_in) {
            return response()->json([
                'success' => false,
                'message' => 'Must clock in first'
            ], 422);
        }

        if ($attendance->break_start && !$attendance->break_end) {
            return response()->json([
                'success' => false,
                'message' => 'Already on break'
            ], 422);
        }

        $attendance->update([
            'break_start' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Break started',
            'data' => $attendance
        ]);
    }

    public function endBreak($id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $attendance = Attendance::whereHas('employee', function ($q) use ($storeId) {
            $q->where('store_id', $storeId);
        })
            ->find($id);

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance record not found'
            ], 404);
        }

        if (!$attendance->break_start) {
            return response()->json([
                'success' => false,
                'message' => 'No active break'
            ], 422);
        }

        if ($attendance->break_end) {
            return response()->json([
                'success' => false,
                'message' => 'Break already ended'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $attendance->update([
                'break_end' => now()
            ]);

            if ($attendance->break_start && $attendance->break_end) {
                $breakMinutes = $attendance->break_end->diffInMinutes($attendance->break_start);
                $attendance->break_minutes = $breakMinutes;
                $attendance->save();
                $attendance->calculateTotalWorked();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Break ended',
                'data' => $attendance->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to end break',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attendance list by employee number with date filtering
     */
    public function getAttendanceByEmployeeNumber(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Find employee by employee number
        $employee = Employee::where('id', $request->employee_id)
            ->where('store_id', $storeId)
            ->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found with the provided employee number'
            ], 404);
        }

        // Build attendance query
        $query = Attendance::with(['shift', 'schedule'])
            ->where('employee_id', $employee->id)
            ->orderBy('attendance_date', 'desc');

        // Apply date filters
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('attendance_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('attendance_date', '<=', $request->end_date);
        }

        // If no date range provided, default to current month
        if (!$request->has('start_date') && !$request->has('end_date')) {
            $query->whereMonth('attendance_date', now()->month)
                ->whereYear('attendance_date', now()->year);
        }

        $attendances = $query->get();

        // Calculate summary statistics
        $summary = [
            'total_days' => $attendances->count(),
            'present' => $attendances->whereIn('status', ['present'])->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'half_day' => $attendances->where('status', 'half_day')->count(),
            'on_leave' => $attendances->where('status', 'on_leave')->count(),
            'holiday' => $attendances->where('status', 'holiday')->count(),
            'total_worked_minutes' => $attendances->sum('total_worked_minutes'),
            'total_worked_hours' => round($attendances->sum('total_worked_minutes') / 60, 2),
            'total_late_minutes' => $attendances->sum('late_minutes'),
            'total_overtime_minutes' => $attendances->sum('overtime_minutes'),
            'total_night_differential_minutes' => $attendances->sum('night_differential_minutes'),
        ];

        // Format attendance records for response
        $formattedAttendances = $attendances->map(function ($attendance) {
            return [
                'id' => $attendance->id,
                'date' => $attendance->attendance_date->format('Y-m-d'),
                'date_formatted' => $attendance->attendance_date->format('M d, Y'),
                'day' => $attendance->attendance_date->format('l'),
                'clock_in' => $attendance->clock_in ? $attendance->clock_in->format('h:i A') : null,
                'clock_out' => $attendance->clock_out ? $attendance->clock_out->format('h:i A') : null,
                'clock_in_raw' => $attendance->clock_in ? $attendance->clock_in->format('Y-m-d H:i:s') : null,
                'clock_out_raw' => $attendance->clock_out ? $attendance->clock_out->format('Y-m-d H:i:s') : null,
                'status' => $attendance->status,
                'status_label' => $attendance->status_label,
                'shift_name' => $attendance->shift->name ?? 'No Shift',
                'shift_start' => $attendance->shift ? $attendance->shift->start_time : null,
                'shift_end' => $attendance->shift ? $attendance->shift->end_time : null,
                'late_minutes' => $attendance->late_minutes,
                'overtime_minutes' => $attendance->overtime_minutes,
                'total_worked_minutes' => $attendance->total_worked_minutes,
                'total_worked_hours' => $attendance->total_hours_worked,
                'break_minutes' => $attendance->break_minutes ?? 0,
                'night_differential_minutes' => $attendance->night_differential_minutes,
                'is_late' => $attendance->isLate(),
                'has_overtime' => $attendance->hasOvertime(),
                'clock_in_method' => $attendance->clock_in_method,
                'clock_out_method' => $attendance->clock_out_method,
                'notes' => $attendance->notes,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'employee' => [
                    'id' => $employee->id,
                    'employee_id' => $employee->employee_id,
                    'name' => $employee->fname . ' ' . $employee->lname,
                    'department' => $employee->department->name ?? null,
                    'position' => $employee->position->name ?? null,
                ],
                'date_range' => [
                    'start_date' => $request->start_date ?? now()->startOfMonth()->toDateString(),
                    'end_date' => $request->end_date ?? now()->endOfMonth()->toDateString(),
                ],
                'summary' => $summary,
                'attendances' => $formattedAttendances
            ]
        ]);
    }
    /**
     * Get attendance list by employee number with date filtering
     */
 /**
 * Get paginated attendance list by employee number with date filtering
 */
public function getPaginatedAttendanceByEmployeeNumber(Request $request)
{
    $user = Auth::user();
    $storeId = $user->store_id;

    $validator = Validator::make($request->all(), [
        'employee_id' => 'required|string',
        'start_date' => 'nullable|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
        'per_page' => 'nullable|integer|min:1|max:100',
        'page' => 'nullable|integer|min:1',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    // Find employee by employee number
    $employee = Employee::where('employee_id', $request->employee_id)
        ->where('store_id', $storeId)
        ->first();

    if (!$employee) {
        return response()->json([
            'success' => false,
            'message' => 'Employee not found with the provided employee number'
        ], 404);
    }

    // Build attendance query
    $query = Attendance::with(['shift', 'schedule'])
        ->where('employee_id', $employee->id)
        ->orderBy('attendance_date', 'desc');

    // Apply date filters
    if ($request->has('start_date') && $request->start_date) {
        $query->whereDate('attendance_date', '>=', $request->start_date);
    }

    if ($request->has('end_date') && $request->end_date) {
        $query->whereDate('attendance_date', '<=', $request->end_date);
    }

    // If no date range provided, default to current month
    if (!$request->has('start_date') && !$request->has('end_date')) {
        $query->whereMonth('attendance_date', now()->month)
            ->whereYear('attendance_date', now()->year);
    }

    $perPage = $request->input('per_page', 15);
    $paginatedAttendances = $query->paginate($perPage);

    // Calculate summary for the filtered records (not just paginated)
    $allFilteredRecords = $query->get();
    $summary = [
        'total_days' => $allFilteredRecords->count(),
        'present' => $allFilteredRecords->whereIn('status', ['present'])->count(),
        'late' => $allFilteredRecords->where('status', 'late')->count(),
        'absent' => $allFilteredRecords->where('status', 'absent')->count(),
        'half_day' => $allFilteredRecords->where('status', 'half_day')->count(),
        'on_leave' => $allFilteredRecords->where('status', 'on_leave')->count(),
        'holiday' => $allFilteredRecords->where('status', 'holiday')->count(),
        'total_worked_minutes' => $allFilteredRecords->sum('total_worked_minutes'),
        'total_worked_hours' => round($allFilteredRecords->sum('total_worked_minutes') / 60, 2),
        'total_late_minutes' => $allFilteredRecords->sum('late_minutes'),
        'total_overtime_minutes' => $allFilteredRecords->sum('overtime_minutes'),
    ];

    // Format paginated attendance records
    $formattedAttendances = collect($paginatedAttendances->items())->map(function ($attendance) {
        return [
            'id' => $attendance->id,
            'date' => $attendance->attendance_date->format('Y-m-d'),
            'date_formatted' => $attendance->attendance_date->format('M d, Y'),
            'day' => $attendance->attendance_date->format('l'),
            'clock_in' => $attendance->clock_in ? $attendance->clock_in->format('h:i A') : null,
            'clock_out' => $attendance->clock_out ? $attendance->clock_out->format('h:i A') : null,
            'status' => $attendance->status,
            'status_label' => $attendance->status_label,
            'shift_name' => $attendance->shift->name ?? 'No Shift',
            'late_minutes' => $attendance->late_minutes,
            'total_worked_hours' => $attendance->total_hours_worked,
            'is_late' => $attendance->isLate(),
            'has_overtime' => $attendance->hasOvertime(),
        ];
    });

    return response()->json([
        'success' => true,
        'data' => [
            'employee' => [
                'id' => $employee->id,
                'employee_id' => $employee->employee_id,
                'name' => $employee->fname . ' ' . $employee->lname,
            ],
            'date_range' => [
                'start_date' => $request->start_date ?? now()->startOfMonth()->toDateString(),
                'end_date' => $request->end_date ?? now()->endOfMonth()->toDateString(),
            ],
            'summary' => $summary,
            'attendances' => $formattedAttendances,
            'pagination' => [
                'current_page' => $paginatedAttendances->currentPage(),
                'per_page' => $paginatedAttendances->perPage(),
                'total' => $paginatedAttendances->total(),
                'last_page' => $paginatedAttendances->lastPage(),
                'from' => $paginatedAttendances->firstItem(),
                'to' => $paginatedAttendances->lastItem(),
            ]
        ]
    ]);
}
}
