<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\ShiftSchedule;
use App\Models\Hr\Employee;
use App\Models\Hr\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ShiftScheduleController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $query = ShiftSchedule::with(['employee', 'shift', 'assignedBy'])
            ->whereHas('employee', function($q) use ($storeId) {
                $q->where('store_id', $storeId);
            });

        // Filter by date
        if ($request->has('date')) {
            $query->where('schedule_date', $request->date);
        }

        // Filter by date range
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('schedule_date', [$request->from_date, $request->to_date]);
        }

        // Filter by employee
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $schedules = $query->orderBy('schedule_date', 'asc')->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $schedules
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'shift_id' => 'required|exists:shifts,id',
            'schedule_date' => 'required|date',
            'status' => 'nullable|in:scheduled,completed,cancelled,absent',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify employee belongs to store
        $employee = Employee::where('id', $request->employee_id)
            ->where('store_id', $storeId)
            ->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee does not belong to your store'
            ], 422);
        }

        // Verify shift belongs to store
        $shift = Shift::where('id', $request->shift_id)
            ->where('store_id', $storeId)
            ->first();

        if (!$shift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift does not belong to your store'
            ], 422);
        }

        // Check for duplicate schedule (same employee, same date)
        $existing = ShiftSchedule::where('employee_id', $request->employee_id)
            ->where('schedule_date', $request->schedule_date)
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Employee already has a shift scheduled for this date'
            ], 422);
        }

        // Check if date is not in the past
        if (strtotime($request->schedule_date) < strtotime(date('Y-m-d'))) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot schedule shifts in the past'
            ], 422);
        }

        $schedule = ShiftSchedule::create([
            'employee_id' => $request->employee_id,
            'shift_id' => $request->shift_id,
            'schedule_date' => $request->schedule_date,
            'status' => $request->status ?? 'scheduled',
            'notes' => $request->notes,
            'assigned_by' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shift scheduled successfully',
            'data' => $schedule->load(['employee', 'shift'])
        ], 201);
    }

    public function bulkStore(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $validator = Validator::make($request->all(), [
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
            'shift_id' => 'required|exists:shifts,id',
            'schedule_dates' => 'required|array',
            'schedule_dates.*' => 'date'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Verify all employees belong to store
        $employeesCount = Employee::whereIn('id', $request->employee_ids)
            ->where('store_id', $storeId)
            ->count();

        if ($employeesCount !== count($request->employee_ids)) {
            return response()->json(['success' => false, 'message' => 'One or more employees do not belong to your store'], 422);
        }

        DB::beginTransaction();
        $created = [];
        $errors = [];

        try {
            foreach ($request->employee_ids as $employeeId) {
                foreach ($request->schedule_dates as $date) {
                    // Check for duplicate
                    $exists = ShiftSchedule::where('employee_id', $employeeId)
                        ->where('schedule_date', $date)
                        ->exists();

                    if ($exists) {
                        $errors[] = "Employee $employeeId already has shift on $date";
                        continue;
                    }

                    $schedule = ShiftSchedule::create([
                        'employee_id' => $employeeId,
                        'shift_id' => $request->shift_id,
                        'schedule_date' => $date,
                        'status' => 'scheduled',
                        'assigned_by' => auth()->id()
                    ]);
                    $created[] = $schedule;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($created) . ' shifts scheduled',
                'data' => $created,
                'errors' => $errors
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to schedule shifts: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $schedule = ShiftSchedule::with(['employee', 'shift', 'assignedBy', 'attendance'])
            ->whereHas('employee', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $schedule
        ]);
    }

    public function getWeeklySchedule(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        // Default to current week (Monday-Sunday)
        $startOfWeek = $request->has('start_date')
            ? \Carbon\Carbon::parse($request->start_date)->startOfDay()
            : \Carbon\Carbon::now()->startOfWeek(\Carbon\Carbon::MONDAY);

        $endOfWeek = $startOfWeek->copy()->addDays(6)->endOfDay();

        $schedules = ShiftSchedule::with(['employee', 'shift'])
            ->whereHas('employee', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->whereBetween('schedule_date', [
                $startOfWeek->toDateString(),
                $endOfWeek->toDateString()
            ])
            ->orderBy('schedule_date')
            ->orderBy('employee_id')
            ->get();

        // Group by date
        $grouped = $schedules->groupBy(function ($s) {
            return $s->schedule_date->toDateString();
        });

        // Build a day-keyed map for the full week
        $week = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i)->toDateString();
            $week[$date] = $grouped->get($date, collect())->values();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'week_start' => $startOfWeek->toDateString(),
                'week_end'   => $endOfWeek->toDateString(),
                'schedule'   => $week,
            ]
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $schedule = ShiftSchedule::whereHas('employee', function($q) use ($storeId) {
            $q->where('store_id', $storeId);
        })->find($id);

        if (!$schedule) {
            return response()->json(['success' => false, 'message' => 'Schedule not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'shift_id' => 'sometimes|exists:shifts,id',
            'schedule_date' => 'sometimes|date',
            'status' => 'sometimes|in:scheduled,completed,cancelled,absent',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // If changing date or employee, check for conflicts
        if ($request->has('schedule_date') || $request->has('employee_id')) {
            $employeeId = $request->employee_id ?? $schedule->employee_id;
            $date = $request->schedule_date ?? $schedule->schedule_date;

            $conflict = ShiftSchedule::where('employee_id', $employeeId)
                ->where('schedule_date', $date)
                ->where('id', '!=', $id)
                ->exists();

            if ($conflict) {
                return response()->json(['success' => false, 'message' => 'Employee already has a shift on this date'], 422);
            }
        }

        $schedule->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Schedule updated successfully',
            'data' => $schedule->fresh(['employee', 'shift'])
        ]);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $schedule = ShiftSchedule::whereHas('employee', function($q) use ($storeId) {
            $q->where('store_id', $storeId);
        })->find($id);

        if (!$schedule) {
            return response()->json(['success' => false, 'message' => 'Schedule not found'], 404);
        }

        // Check if there's a pending swap request for this schedule
        if ($schedule->swapRequestsAsRequestor()->where('status', 'pending')->exists() ||
            $schedule->swapRequestsAsReceiver()->where('status', 'pending')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete: There is a pending swap request for this shift'
            ], 422);
        }

        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Schedule deleted successfully'
        ]);
    }
}