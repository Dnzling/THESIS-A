<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\ShiftAssignment;
use App\Models\Hr\Employee;
use App\Models\Hr\Shift;
use App\Models\Hr\ScheduleTemplate;
use App\Models\Core\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ShiftAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $query = ShiftAssignment::with(['employee', 'shift', 'template'])
            ->whereHas('employee', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            });

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('shift_id')) {
            $query->where('shift_id', $request->shift_id);
        }

        if ($request->has('assignment_type')) {
            $query->where('assignment_type', $request->assignment_type);
        }

        if ($request->has('active')) {
            $query->active($request->active_date ?? now());
        }

        $assignments = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'shift_id' => 'required|exists:shifts,id',
            'template_id' => 'nullable|exists:schedule_templates,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'assignment_type' => 'required|in:permanent,temporary,cover',
            'cover_for' => 'nullable|exists:employees,id',
            'recurring_pattern' => 'nullable|array',
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

        // Verify shift belongs to user's store
        $shift = Shift::where('id', $request->shift_id)
            ->where('store_id', $storeId)
            ->first();

        if (!$shift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift does not belong to your store'
            ], 422);
        }

        // Check for overlapping assignments
        $overlap = ShiftAssignment::where('employee_id', $request->employee_id)
            ->where(function ($q) use ($request) {
                $q->whereBetween('start_date', [$request->start_date, $request->end_date ?? '9999-12-31'])
                    ->orWhereBetween('end_date', [$request->start_date, $request->end_date ?? '9999-12-31'])
                    ->orWhere(function ($qr) use ($request) {
                        $qr->where('start_date', '<=', $request->start_date)
                            ->where('end_date', '>=', $request->end_date ?? '9999-12-31');
                    });
            })
            ->exists();

        if ($overlap) {
            return response()->json([
                'success' => false,
                'message' => 'Employee already has an assignment for this period'
            ], 422);
        }

        $assignment = ShiftAssignment::create(array_merge(
            $request->all(),
            ['created_by' => auth()->id()]
        ));

        // Generate schedules if start_date and end_date are provided
        if ($request->start_date && $request->end_date) {
            $this->generateSchedules($assignment, $request->all());
        }

        return response()->json([
            'success' => true,
            'message' => 'Shift assignment created successfully',
            'data' => $assignment->load(['employee', 'shift', 'template'])
        ], 201);
    }

    /**
     * Generate shift schedules from assignment
     */
    private function generateSchedules($assignment, $data)
    {
        $start = \Carbon\Carbon::parse($data['start_date']);
        $end = \Carbon\Carbon::parse($data['end_date']);

        // Simple logic: create schedule for each day in range
        // You can adjust this based on recurring_pattern or shift days
        while ($start->lte($end)) {
            // Check if schedule already exists
            $exists = \App\Models\Hr\ShiftSchedule::where('employee_id', $assignment->employee_id)
                ->where('schedule_date', $start->format('Y-m-d'))
                ->exists();

            if (!$exists) {
                \App\Models\Hr\ShiftSchedule::create([
                    'employee_id' => $assignment->employee_id,
                    'shift_id' => $assignment->shift_id,
                    'schedule_date' => $start->format('Y-m-d'),
                    'status' => 'scheduled',
                    'assignment_id' => $assignment->id,
                    'assigned_by' => auth()->id()
                ]);
            }

            $start->addDay();
        }
    }

    public function show($id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $assignment = ShiftAssignment::with([
            'employee',
            'shift',
            'template',
            'coverFor',
            'creator',
            'schedules' => function ($q) {
                $q->latest('schedule_date')->limit(10);
            }
        ])
            ->whereHas('employee', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->find($id);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $assignment
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $assignment = ShiftAssignment::whereHas('employee', function ($q) use ($storeId) {
            $q->where('store_id', $storeId);
        })
            ->find($id);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'shift_id' => 'sometimes|exists:shifts,id',
            'template_id' => 'nullable|exists:schedule_templates,id',
            'end_date' => 'nullable|date|after:start_date',
            'assignment_type' => 'sometimes|in:permanent,temporary,cover',
            'cover_for' => 'nullable|exists:employees,id',
            'recurring_pattern' => 'nullable|array',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->has('shift_id')) {
            $shift = Shift::where('id', $request->shift_id)
                ->where('store_id', $storeId)
                ->first();

            if (!$shift) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shift does not belong to your store'
                ], 422);
            }
        }

        // Check for overlapping if dates change
        if ($request->has('start_date') || $request->has('end_date')) {
            $startDate = $request->start_date ?? $assignment->start_date;
            $endDate = $request->end_date ?? $assignment->end_date;

            $overlap = ShiftAssignment::where('employee_id', $assignment->employee_id)
                ->where('id', '!=', $id)
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date', [$startDate, $endDate ?? '9999-12-31'])
                        ->orWhereBetween('end_date', [$startDate, $endDate ?? '9999-12-31'])
                        ->orWhere(function ($qr) use ($startDate, $endDate) {
                            $qr->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate ?? '9999-12-31');
                        });
                })
                ->exists();

            if ($overlap) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee already has an assignment for this period'
                ], 422);
            }
        }

        $assignment->update(array_merge(
            $request->all(),
            ['updated_by' => auth()->id()]
        ));

        // If shift changed, update future schedules
        if ($request->has('shift_id')) {
            \App\Models\Hr\ShiftSchedule::where('assignment_id', $assignment->id)
                ->where('schedule_date', '>=', now()->toDateString())
                ->update(['shift_id' => $request->shift_id]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Assignment updated successfully',
            'data' => $assignment->fresh(['employee', 'shift', 'template'])
        ]);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $assignment = ShiftAssignment::whereHas('employee', function ($q) use ($storeId) {
            $q->where('store_id', $storeId);
        })
            ->find($id);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found'
            ], 404);
        }

        // Delete associated future schedules
        $futureSchedules = $assignment->schedules()
            ->where('schedule_date', '>=', now()->toDateString())
            ->get();

        if ($futureSchedules->isNotEmpty()) {
            // Check for pending swap requests
            foreach ($futureSchedules as $schedule) {
                if (
                    $schedule->swapRequestsAsRequestor()->where('status', 'pending')->exists() ||
                    $schedule->swapRequestsAsReceiver()->where('status', 'pending')->exists()
                ) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot delete: There are pending swap requests for future shifts in this assignment'
                    ], 422);
                }
            }

            // Delete the schedules
            $assignment->schedules()
                ->where('schedule_date', '>=', now()->toDateString())
                ->delete();
        }

        $assignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Assignment deleted successfully'
        ]);
    }

    public function bulkAssign(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $validator = Validator::make($request->all(), [
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
            'shift_id' => 'required|exists:shifts,id',
            'template_id' => 'nullable|exists:schedule_templates,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'assignment_type' => 'required|in:permanent,temporary,cover'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify all employees belong to user's store
        $employeesCount = Employee::whereIn('id', $request->employee_ids)
            ->where('store_id', $storeId)
            ->count();

        if ($employeesCount !== count($request->employee_ids)) {
            return response()->json([
                'success' => false,
                'message' => 'One or more employees do not belong to your store'
            ], 422);
        }

        // Verify shift belongs to user's store
        $shift = Shift::where('id', $request->shift_id)
            ->where('store_id', $storeId)
            ->first();

        if (!$shift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift does not belong to your store'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $created = [];
            $errors = [];

            foreach ($request->employee_ids as $employeeId) {
                // Check overlap
                $overlap = ShiftAssignment::where('employee_id', $employeeId)
                    ->where(function ($q) use ($request) {
                        $q->whereBetween('start_date', [$request->start_date, $request->end_date ?? '9999-12-31'])
                            ->orWhereBetween('end_date', [$request->start_date, $request->end_date ?? '9999-12-31'])
                            ->orWhere(function ($qr) use ($request) {
                                $qr->where('start_date', '<=', $request->start_date)
                                    ->where('end_date', '>=', $request->end_date ?? '9999-12-31');
                            });
                    })
                    ->exists();

                if ($overlap) {
                    $errors[] = "Employee ID $employeeId already has an assignment for this period";
                    continue;
                }

                $assignment = ShiftAssignment::create([
                    'employee_id' => $employeeId,
                    'shift_id' => $request->shift_id,
                    'template_id' => $request->template_id,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'assignment_type' => $request->assignment_type,
                    'created_by' => auth()->id()
                ]);

                $created[] = $assignment;

                // Generate schedules
                $this->generateSchedules($assignment, $request->all());
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($created) . ' assignments created',
                'data' => $created,
                'errors' => $errors
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create assignments',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
