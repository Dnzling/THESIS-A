<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\ShiftSwapRequest;
use App\Models\Hr\ShiftSchedule;
use App\Models\Hr\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ShiftSwapRequestController extends Controller
{
    /**
     * Check if user is HR/Admin
     */
    private function isHrUser($user)
    {
        $hrAdminRoleIds = [1, 2, 4];
        return in_array($user->role_id, $hrAdminRoleIds);
    }
    /**
     * Display a listing of swap requests
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;
        $isHr = $this->isHrUser($user);

        $query = ShiftSwapRequest::with([
            'requestor',
            'receiver',
            'requestorSchedule.shift',
            'receiverSchedule.shift',
            'approver'
        ])
            ->whereHas('requestor', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            });

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by employee
        if ($request->has('employee_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('requestor_id', $request->employee_id)
                    ->orWhere('receiver_id', $request->employee_id);
            });
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereHas('requestorSchedule', function ($q) use ($request) {
                $q->where('schedule_date', '>=', $request->from_date);
            });
        }

        if ($request->has('to_date')) {
            $query->whereHas('requestorSchedule', function ($q) use ($request) {
                $q->where('schedule_date', '<=', $request->to_date);
            });
        }

        $swapRequests = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $swapRequests
        ]);
    }

    /**
     * Store a newly created swap request
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|exists:employees,id',
            'requestor_schedule_id' => 'required|exists:shift_schedules,id',
            'receiver_schedule_id' => 'required|exists:shift_schedules,id',
            'swap_type' => 'required|in:full_swap,give_away,pick_up',
            'reason' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Get the authenticated user's employee record
        $requestor = Employee::where('user_id', $user->id)
            ->where('store_id', $storeId)
            ->first();

        if (!$requestor) {
            return response()->json([
                'success' => false,
                'message' => 'Employee record not found'
            ], 404);
        }

        // Cannot swap with yourself
        if ($requestor->id == $request->receiver_id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot request a swap with yourself'
            ], 422);
        }

        // Verify receiver belongs to same store
        $receiver = Employee::where('id', $request->receiver_id)
            ->where('store_id', $storeId)
            ->first();

        if (!$receiver) {
            return response()->json([
                'success' => false,
                'message' => 'Receiver does not belong to your store'
            ], 422);
        }

        // Verify requestor's schedule exists and belongs to requestor
        $requestorSchedule = ShiftSchedule::where('id', $request->requestor_schedule_id)
            ->where('employee_id', $requestor->id)
            ->where('status', 'scheduled')
            ->where('schedule_date', '>=', now()->toDateString())
            ->first();

        if (!$requestorSchedule) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or past schedule for your shift'
            ], 422);
        }

        // Verify receiver's schedule exists and belongs to receiver
        $receiverSchedule = ShiftSchedule::where('id', $request->receiver_schedule_id)
            ->where('employee_id', $receiver->id)
            ->where('status', 'scheduled')
            ->where('schedule_date', '>=', now()->toDateString())
            ->first();

        if (!$receiverSchedule) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or past schedule for receiver'
            ], 422);
        }

        // Check if dates match for full_swap
        if (
            $request->swap_type === 'full_swap' &&
            $requestorSchedule->schedule_date != $receiverSchedule->schedule_date
        ) {
            return response()->json([
                'success' => false,
                'message' => 'For full swap, both shifts must be on the same date'
            ], 422);
        }

        // Check for existing pending request
        $existing = ShiftSwapRequest::where('requestor_id', $requestor->id)
            ->where('receiver_id', $receiver->id)
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending swap request with this employee'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $swapRequest = ShiftSwapRequest::create([
                'requestor_id' => $requestor->id,
                'receiver_id' => $receiver->id,
                'requestor_schedule_id' => $requestorSchedule->id,
                'receiver_schedule_id' => $receiverSchedule->id,
                'swap_type' => $request->swap_type,
                'reason' => $request->reason,
                'status' => 'pending'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Shift swap request created successfully',
                'data' => $swapRequest->load(['requestor', 'receiver', 'requestorSchedule.shift', 'receiverSchedule.shift'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create swap request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified swap request
     */
    public function show($id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $swapRequest = ShiftSwapRequest::with([
            'requestor',
            'receiver',
            'requestorSchedule.shift',
            'receiverSchedule.shift',
            'approver'
        ])
            ->whereHas('requestor', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->find($id);

        if (!$swapRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Swap request not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $swapRequest
        ]);
    }

    /**
     * Accept a swap request
     */
    public function accept($id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;
        $isHr = $this->isHrUser($user);

        // Find the employee record for the authenticated user
        $employee = !$isHr ? Employee::where('user_id', $user->id)
            ->where('store_id', $storeId)
            ->first() : null;

        $swapRequest = ShiftSwapRequest::with(['requestorSchedule', 'receiverSchedule'])
            ->find($id);

        if (!$swapRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Swap request not found'
            ], 404);
        }

        if ($swapRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This request is no longer pending'
            ], 422);
        }

        // Authorization: HR can accept OR Receiver can accept
        $canAccept = $isHr || ($employee && $swapRequest->receiver_id === $employee->id);

        if (!$canAccept) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to accept this request'
            ], 403);
        }

        // Conflict Checking
        if ($swapRequest->swap_type === 'full_swap') {
            // Check receiver conflict
            $receiverConflict = ShiftSchedule::where('employee_id', $swapRequest->receiver_id)
                ->where('schedule_date', $swapRequest->requestorSchedule->schedule_date)
                ->where('id', '!=', $swapRequest->receiverSchedule->id)
                ->exists();

            if ($receiverConflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot accept: Receiver already has a shift on that date'
                ], 422);
            }

            // Check requestor conflict
            $requestorConflict = ShiftSchedule::where('employee_id', $swapRequest->requestor_id)
                ->where('schedule_date', $swapRequest->receiverSchedule->schedule_date)
                ->where('id', '!=', $swapRequest->requestorSchedule->id)
                ->exists();

            if ($requestorConflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot accept: Requestor already has a shift on that date'
                ], 422);
            }
        } elseif ($swapRequest->swap_type === 'give_away') {
            // Check if receiver already has a shift on that date
            $conflict = ShiftSchedule::where('employee_id', $swapRequest->receiver_id)
                ->where('schedule_date', $swapRequest->requestorSchedule->schedule_date)
                ->where('id', '!=', $swapRequest->requestorSchedule->id)
                ->exists();

            if ($conflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot accept: You already have a shift on that date'
                ], 422);
            }
        } elseif ($swapRequest->swap_type === 'pick_up') {
            // Check if requestor already has a shift on that date
            $conflict = ShiftSchedule::where('employee_id', $swapRequest->requestor_id)
                ->where('schedule_date', $swapRequest->receiverSchedule->schedule_date)
                ->where('id', '!=', $swapRequest->receiverSchedule->id)
                ->exists();

            if ($conflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot accept: Requestor already has a shift on that date'
                ], 422);
            }
        }

        DB::beginTransaction();

        try {
            $swapRequest->accept($user->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Swap request accepted successfully',
                'data' => $swapRequest->fresh(['requestor', 'receiver', 'requestorSchedule', 'receiverSchedule'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept swap request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a swap request
     */
    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;
        $isHr = $this->isHrUser($user);

        $employee = !$isHr ? Employee::where('user_id', $user->id)
            ->where('store_id', $storeId)
            ->first() : null;

        $swapRequest = ShiftSwapRequest::find($id);

        if (!$swapRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Swap request not found'
            ], 404);
        }

        if ($swapRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This request is no longer pending'
            ], 422);
        }

        // Authorization: HR can reject OR Receiver can reject
        $canReject = $isHr || ($employee && $swapRequest->receiver_id === $employee->id);

        if (!$canReject) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to reject this request'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $swapRequest->reject($user->id);

            if ($request->has('reason') && !empty($request->reason)) {
                $swapRequest->update(['reason' => $request->reason]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Swap request rejected',
                'data' => $swapRequest->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject swap request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel a swap request (only by requestor or HR)
     */
    public function cancel($id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;
        $isHr = $this->isHrUser($user);

        $employee = !$isHr ? Employee::where('user_id', $user->id)
            ->where('store_id', $storeId)
            ->first() : null;

        $swapRequest = ShiftSwapRequest::find($id);

        if (!$swapRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Swap request not found'
            ], 404);
        }

        if ($swapRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This request is no longer pending'
            ], 422);
        }

        // Authorization: HR can cancel OR Requestor can cancel
        $canCancel = $isHr || ($employee && $swapRequest->requestor_id === $employee->id);

        if (!$canCancel) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to cancel this request'
            ], 403);
        }

        DB::beginTransaction();

        try {
            $swapRequest->cancel();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Swap request cancelled successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel swap request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending requests for the current user
     */
    public function myPendingRequests()
    {
        $user = Auth::user();
        $storeId = $user->store_id;
        $isHr = $this->isHrUser($user);

        // If HR, return all pending in the store
        if ($isHr) {
            $pending = ShiftSwapRequest::with(['requestor', 'receiver', 'requestorSchedule.shift'])
                ->whereHas('requestor', function ($q) use ($storeId) {
                    $q->where('store_id', $storeId);
                })
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $pending
            ]);
        }

        // Regular employee
        $employee = Employee::where('user_id', $user->id)
            ->where('store_id', $storeId)
            ->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee record not found'
            ], 404);
        }

        $pending = ShiftSwapRequest::with(['requestor', 'receiver', 'requestorSchedule.shift'])
            ->where('receiver_id', $employee->id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $pending
        ]);
    }
}
