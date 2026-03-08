<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\OvertimeRequest;
use App\Models\Hr\Attendance;
use App\Models\Hr\Employee;
use App\Models\Core\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OvertimeRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $query = OvertimeRequest::with(['employee', 'attendance', 'approver'])
            ->whereHas('employee', function($q) use ($storeId) {
                $q->where('store_id', $storeId);
            });
        
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('start_date')) {
            $query->where('ot_start', '>=', $request->start_date);
        }
        
        if ($request->has('end_date')) {
            $query->where('ot_end', '<=', $request->end_date);
        }
        
        $requests = $query->orderBy('created_at', 'desc')->paginate(15);
        
        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'attendance_id' => 'required|exists:attendances,id',
            'ot_start' => 'required|date',
            'ot_end' => 'required|date|after:ot_start',
            'ot_type' => 'required|in:regular,holiday,rest_day',
            'rate_multiplier' => 'required|numeric|min:1|max:3',
            'reason' => 'required|string'
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

        // Verify attendance belongs to the employee and is from user's store
        $attendance = Attendance::where('id', $request->attendance_id)
            ->where('employee_id', $request->employee_id)
            ->whereHas('employee', function($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->first();

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance record not found or does not belong to employee'
            ], 422);
        }

        // Calculate OT minutes
        $start = \Carbon\Carbon::parse($request->ot_start);
        $end = \Carbon\Carbon::parse($request->ot_end);
        $otMinutes = $end->diffInMinutes($start);

        // Check for overlapping OT requests
        $overlap = OvertimeRequest::where('employee_id', $request->employee_id)
            ->where('status', 'pending')
            ->where(function($q) use ($start, $end) {
                $q->whereBetween('ot_start', [$start, $end])
                  ->orWhereBetween('ot_end', [$start, $end]);
            })
            ->exists();

        if ($overlap) {
            return response()->json([
                'success' => false,
                'message' => 'Overlapping overtime request exists'
            ], 422);
        }

        $otRequest = OvertimeRequest::create([
            'employee_id' => $request->employee_id,
            'attendance_id' => $request->attendance_id,
            'ot_start' => $request->ot_start,
            'ot_end' => $request->ot_end,
            'ot_minutes' => $otMinutes,
            'ot_type' => $request->ot_type,
            'rate_multiplier' => $request->rate_multiplier,
            'reason' => $request->reason,
            'status' => 'pending'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Overtime request submitted successfully',
            'data' => $otRequest->load(['employee', 'attendance'])
        ], 201);
    }

    public function show($id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $otRequest = OvertimeRequest::with(['employee', 'attendance', 'approver'])
            ->whereHas('employee', function($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->find($id);
        
        if (!$otRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Overtime request not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $otRequest
        ]);
    }

    public function approve($id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $otRequest = OvertimeRequest::whereHas('employee', function($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->find($id);
        
        if (!$otRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Overtime request not found'
            ], 404);
        }

        if ($otRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Request is already ' . $otRequest->status
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            $otRequest->approve(auth()->id());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Overtime request approved successfully',
                'data' => $otRequest->fresh(['employee', 'approver'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $otRequest = OvertimeRequest::whereHas('employee', function($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->find($id);
        
        if (!$otRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Overtime request not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if ($otRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Request is already ' . $otRequest->status
            ], 422);
        }

        $otRequest->reject(auth()->id(), $request->rejection_reason);

        return response()->json([
            'success' => true,
            'message' => 'Overtime request rejected',
            'data' => $otRequest->fresh()
        ]);
    }
}