<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Leave;
use App\Models\Hr\LeaveBalance;
use App\Models\Hr\Employee;
use App\Models\Hr\Attendance;
use App\Models\Core\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $query = Leave::with(['employee', 'approver', 'handoverTo'])
            ->whereHas('employee', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            });

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('leave_type')) {
            $query->where('leave_type', $request->leave_type);
        }

        if ($request->has('start_date')) {
            $query->where('start_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('end_date', '<=', $request->end_date);
        }

        $leaves = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $leaves
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'leave_type' => 'required|in:sick,vacation,personal,maternity,paternity,bereavement,others',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
            'attachment_path' => 'nullable|string',
            'is_paid' => 'boolean',
            'handover_notes' => 'nullable|array',
            'handover_to' => 'nullable|exists:employees,id'
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

        // Calculate total days
        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        $totalDays = $end->diffInDays($start) + 1;

        // Check leave balance
        $balance = LeaveBalance::where('employee_id', $request->employee_id)
            ->where('leave_type', $request->leave_type)
            ->where('year', $start->year)
            ->first();

        if ($request->is_paid && $balance && $balance->remaining_days < $totalDays) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient leave balance. Available: ' . $balance->remaining_days . ' days'
            ], 422);
        }

        // Check for overlapping leaves
        $overlap = Leave::where('employee_id', $request->employee_id)
            ->where('status', 'approved')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(function ($qr) use ($start, $end) {
                        $qr->where('start_date', '<=', $start)
                            ->where('end_date', '>=', $end);
                    });
            })
            ->exists();

        if ($overlap) {
            return response()->json([
                'success' => false,
                'message' => 'Employee already has approved leave for this period'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $leave = Leave::create([
                'employee_id' => $request->employee_id,
                'leave_type' => $request->leave_type,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'total_days' => $totalDays,
                'reason' => $request->reason,
                'attachment_path' => $request->attachment_path,
                'is_paid' => $request->is_paid ?? true,
                'handover_notes' => $request->handover_notes,
                'handover_to' => $request->handover_to,
                'status' => 'pending'
            ]);

            // Update pending days in balance
            if ($request->is_paid && $balance) {
                $balance->pending_days += $totalDays;
                $balance->updateRemainingDays();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Leave request submitted successfully',
                'data' => $leave->load(['employee', 'handoverTo'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit leave request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $leave = Leave::with(['employee', 'approver', 'handoverTo'])
            ->whereHas('employee', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->find($id);

        if (!$leave) {
            return response()->json([
                'success' => false,
                'message' => 'Leave request not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $leave
        ]);
    }

    public function approve($id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $leave = Leave::whereHas('employee', function ($q) use ($storeId) {
            $q->where('store_id', $storeId);
        })
            ->find($id);

        if (!$leave) {
            return response()->json([
                'success' => false,
                'message' => 'Leave request not found'
            ], 404);
        }

        if ($leave->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Request is already ' . $leave->status
            ], 422);
        }

        DB::beginTransaction();

        try {
            $leave->approve(auth()->id());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Leave request approved successfully',
                'data' => $leave->fresh(['employee', 'approver'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve leave request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $leave = Leave::whereHas('employee', function ($q) use ($storeId) {
            $q->where('store_id', $storeId);
        })
            ->find($id);

        if (!$leave) {
            return response()->json([
                'success' => false,
                'message' => 'Leave request not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'rejected_reason' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if ($leave->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Request is already ' . $leave->status
            ], 422);
        }

        DB::beginTransaction();

        try {
            $leave->reject(auth()->id(), $request->rejected_reason);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Leave request rejected',
                'data' => $leave->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject leave request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function cancel($id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $leave = Leave::whereHas('employee', function ($q) use ($storeId) {
            $q->where('store_id', $storeId);
        })
            ->find($id);

        if (!$leave) {
            return response()->json([
                'success' => false,
                'message' => 'Leave request not found'
            ], 404);
        }

        if (!in_array($leave->status, ['pending', 'approved'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel request with status: ' . $leave->status
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Remove pending days from balance
            if ($leave->is_paid && $leave->status === 'pending') {
                $balance = LeaveBalance::where('employee_id', $leave->employee_id)
                    ->where('leave_type', $leave->leave_type)
                    ->where('year', $leave->start_date->year)
                    ->first();

                if ($balance) {
                    $balance->pending_days -= $leave->total_days;
                    $balance->updateRemainingDays();
                }
            }

            $leave->update(['status' => 'cancelled']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Leave request cancelled successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel leave request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get leaves created by a specific user/employee
     */
    public function getUserLeaves(Request $request, $employeeId)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        // Verify employee belongs to user's store
        $employee = Employee::where('id', $employeeId)
            ->where('store_id', $storeId)
            ->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found or does not belong to your store'
            ], 404);
        }

        $query = Leave::with(['approver', 'handoverTo'])
            ->where('employee_id', $employeeId);

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by leave type
        if ($request->has('leave_type') && $request->leave_type) {
            $query->where('leave_type', $request->leave_type);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->start_date) {
            $query->where('start_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->where('end_date', '<=', $request->end_date);
        }

        // Filter by year
        if ($request->has('year') && $request->year) {
            $query->whereYear('start_date', $request->year);
        }

        // Search in reason
        if ($request->has('search') && $request->search) {
            $query->where('reason', 'like', "%{$request->search}%");
        }

        // Get statistics
        $statistics = [
            'total_leaves' => $query->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
            'total_days' => (clone $query)->where('status', 'approved')->sum('total_days'),
            'sick_leaves' => (clone $query)->where('leave_type', 'sick')->count(),
            'vacation_leaves' => (clone $query)->where('leave_type', 'vacation')->count(),
            'personal_leaves' => (clone $query)->where('leave_type', 'personal')->count(),
        ];

        // Get leave balances
        $currentYear = $request->year ?? date('Y');
        $balances = LeaveBalance::where('employee_id', $employeeId)
            ->where('year', $currentYear)
            ->get()
            ->keyBy('leave_type');

        // Paginate results
        $perPage = $request->input('per_page', 15);
        $leaves = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Add formatted fields
        $leaves->getCollection()->transform(function ($leave) {
            return [
                'id' => $leave->id,
                'leave_type' => $leave->leave_type,
                'leave_type_label' => ucfirst(str_replace('_', ' ', $leave->leave_type)),
                'start_date' => $leave->start_date->format('Y-m-d'),
                'end_date' => $leave->end_date->format('Y-m-d'),
                'start_date_formatted' => $leave->start_date->format('M d, Y'),
                'end_date_formatted' => $leave->end_date->format('M d, Y'),
                'total_days' => $leave->total_days,
                'duration' => $leave->duration,
                'reason' => $leave->reason,
                'status' => $leave->status,
                'status_label' => ucfirst($leave->status),
                'status_badge' => $leave->status_badge,
                'is_paid' => $leave->is_paid,
                'is_paid_label' => $leave->is_paid ? 'Paid' : 'Unpaid',
                'deduct_from_balance' => $leave->deduct_from_balance,
                'handover_notes' => $leave->handover_notes,
                'handover_to' => $leave->handoverTo ? [
                    'id' => $leave->handoverTo->id,
                    'name' => $leave->handoverTo->fname . ' ' . $leave->handoverTo->lname,
                    'position' => $leave->handoverTo->position->name ?? null,
                ] : null,
                'approved_by' => $leave->approver ? [
                    'id' => $leave->approver->id,
                    'name' => $leave->approver->name,
                ] : null,
                'approved_at' => $leave->approved_at ? $leave->approved_at->format('Y-m-d H:i:s') : null,
                'approved_at_formatted' => $leave->approved_at ? $leave->approved_at->format('M d, Y h:i A') : null,
                'rejected_reason' => $leave->rejected_reason,
                'attachment_path' => $leave->attachment_path,
                'created_at' => $leave->created_at->format('Y-m-d H:i:s'),
                'created_at_formatted' => $leave->created_at->format('M d, Y'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->fname . ' ' . $employee->lname,
                    'employee_number' => $employee->employee_number ?? null,
                    'department' => $employee->department->name ?? null,
                    'position' => $employee->position->name ?? null,
                ],
                'statistics' => $statistics,
                'balances' => $balances,
                'leaves' => $leaves,
            ]
        ]);
    }

    /**
     * Get leave statistics for a user
     */
    public function getUserLeaveStatistics(Request $request, $employeeId)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $employee = Employee::where('id', $employeeId)
            ->where('store_id', $storeId)
            ->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found'
            ], 404);
        }

        $year = $request->input('year', date('Y'));

        // Get monthly breakdown
        $monthlyBreakdown = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthlyLeaves = Leave::where('employee_id', $employeeId)
                ->whereYear('start_date', $year)
                ->whereMonth('start_date', $month)
                ->where('status', 'approved')
                ->get();

            $monthlyBreakdown[] = [
                'month' => date('F', mktime(0, 0, 0, $month, 1)),
                'month_num' => $month,
                'total_days' => $monthlyLeaves->sum('total_days'),
                'count' => $monthlyLeaves->count(),
                'leaves' => $monthlyLeaves->map(function ($leave) {
                    return [
                        'type' => $leave->leave_type,
                        'days' => $leave->total_days,
                        'dates' => $leave->start_date->format('M d') . ' - ' . $leave->end_date->format('M d'),
                    ];
                }),
            ];
        }

        // Get leave type breakdown
        $typeBreakdown = [];
        $leaveTypes = ['sick', 'vacation', 'personal', 'maternity', 'paternity', 'bereavement', 'others'];

        foreach ($leaveTypes as $type) {
            $typeLeaves = Leave::where('employee_id', $employeeId)
                ->whereYear('start_date', $year)
                ->where('leave_type', $type)
                ->where('status', 'approved')
                ->get();

            $typeBreakdown[$type] = [
                'total_days' => $typeLeaves->sum('total_days'),
                'count' => $typeLeaves->count(),
            ];
        }

        // Get status breakdown
        $statusBreakdown = [
            'pending' => Leave::where('employee_id', $employeeId)->whereYear('start_date', $year)->where('status', 'pending')->count(),
            'approved' => Leave::where('employee_id', $employeeId)->whereYear('start_date', $year)->where('status', 'approved')->count(),
            'rejected' => Leave::where('employee_id', $employeeId)->whereYear('start_date', $year)->where('status', 'rejected')->count(),
            'cancelled' => Leave::where('employee_id', $employeeId)->whereYear('start_date', $year)->where('status', 'cancelled')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'monthly_breakdown' => $monthlyBreakdown,
                'type_breakdown' => $typeBreakdown,
                'status_breakdown' => $statusBreakdown,
            ]
        ]);
    }
}
