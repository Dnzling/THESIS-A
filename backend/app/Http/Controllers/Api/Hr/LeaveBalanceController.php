<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\LeaveBalance;
use App\Models\Hr\Employee;
use App\Models\Store\Store;
use App\Models\Core\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class LeaveBalanceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $query = LeaveBalance::with(['employee', 'store'])
            ->where('store_id', $storeId);
        
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        
        if ($request->has('year')) {
            $query->where('year', $request->year);
        }
        
        if ($request->has('leave_type')) {
            $query->where('leave_type', $request->leave_type);
        }
        
        $balances = $query->paginate(15);
        
        return response()->json([
            'success' => true,
            'data' => $balances
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'leave_type' => 'required|in:sick,vacation,personal,maternity,paternity,bereavement,others',
            'yearly_quota' => 'required|numeric|min:0',
            'carried_over' => 'nullable|numeric|min:0',
            'year' => 'required|integer|min:2000|max:2100',
            'expiry_date' => 'nullable|date',
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

        // Check for existing balance
        $exists = LeaveBalance::where('employee_id', $request->employee_id)
            ->where('leave_type', $request->leave_type)
            ->where('year', $request->year)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Leave balance already exists for this employee, year, and leave type'
            ], 422);
        }

        $balance = LeaveBalance::create([
            'store_id' => $storeId,
            'employee_id' => $request->employee_id,
            'created_by' => auth()->id(),
            'leave_type' => $request->leave_type,
            'yearly_quota' => $request->yearly_quota,
            'used_days' => 0,
            'pending_days' => 0,
            'remaining_days' => $request->yearly_quota + ($request->carried_over ?? 0),
            'carried_over' => $request->carried_over ?? 0,
            'expired_days' => 0,
            'year' => $request->year,
            'expiry_date' => $request->expiry_date,
            'notes' => $request->notes,
            'status' => 'active'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Leave balance created successfully',
            'data' => $balance->load(['employee', 'store'])
        ], 201);
    }

    public function show($id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $balance = LeaveBalance::with(['employee', 'store', 'creator'])
            ->where('store_id', $storeId)
            ->find($id);
        
        if (!$balance) {
            return response()->json([
                'success' => false,
                'message' => 'Leave balance not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $balance
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $balance = LeaveBalance::where('store_id', $storeId)->find($id);
        
        if (!$balance) {
            return response()->json([
                'success' => false,
                'message' => 'Leave balance not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'yearly_quota' => 'sometimes|numeric|min:0',
            'carried_over' => 'sometimes|numeric|min:0',
            'used_days' => 'sometimes|numeric|min:0',
            'pending_days' => 'sometimes|numeric|min:0',
            'expired_days' => 'sometimes|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'status' => 'sometimes|in:active,expired,carried_over,frozen,archived',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $balance->fill($request->all());
        
        // Recalculate remaining days
        $balance->remaining_days = $balance->yearly_quota + $balance->carried_over - 
                                  $balance->used_days - $balance->pending_days;
        
        $balance->updated_by = auth()->id();
        $balance->save();

        return response()->json([
            'success' => true,
            'message' => 'Leave balance updated successfully',
            'data' => $balance->fresh()
        ]);
    }

    public function getEmployeeBalance($employeeId, $year = null)
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

        $year = $year ?? date('Y');
        
        $balances = LeaveBalance::where('employee_id', $employeeId)
            ->where('year', $year)
            ->get();

        if ($balances->isEmpty()) {
            $leaveTypes = ['vacation', 'sick', 'personal'];
            
            foreach ($leaveTypes as $type) {
                $balance = LeaveBalance::create([
                    'store_id' => $storeId,
                    'employee_id' => $employeeId,
                    'created_by' => auth()->id(),
                    'leave_type' => $type,
                    'yearly_quota' => $type === 'vacation' ? 15 : ($type === 'sick' ? 10 : 5),
                    'used_days' => 0,
                    'pending_days' => 0,
                    'remaining_days' => $type === 'vacation' ? 15 : ($type === 'sick' ? 10 : 5),
                    'carried_over' => 0,
                    'expired_days' => 0,
                    'year' => $year,
                    'status' => 'active'
                ]);
                
                $balances->push($balance);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'employee' => $employee->only(['id', 'first_name', 'last_name']),
                'year' => $year,
                'balances' => $balances
            ]
        ]);
    }

    public function carryOver(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'from_year' => 'required|integer|min:2000',
            'to_year' => 'required|integer|min:2000|gt:from_year'
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

        DB::beginTransaction();
        
        try {
            $balances = LeaveBalance::where('employee_id', $request->employee_id)
                ->where('year', $request->from_year)
                ->get();

            foreach ($balances as $balance) {
                if ($balance->remaining_days > 0) {
                    $balance->carryOverTo($request->to_year);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Leave balances carried over successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to carry over balances',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}