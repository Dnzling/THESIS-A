<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeDeduction;
use App\Models\Hr\DeductionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class EmployeeDeductionController extends Controller
{
    /**
     * List all deductions for an employee or all employees in store
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $storeId = $user->store_id;

            $query = EmployeeDeduction::with(['employee', 'deductionType', 'creator'])
                ->whereHas('employee', function($q) use ($storeId) {
                    $q->where('store_id', $storeId);
                });

            // Filter by employee
            if ($request->has('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            // Filter by deduction type
            if ($request->has('deduction_type_id')) {
                $query->where('deduction_type_id', $request->deduction_type_id);
            }

            // Filter active/inactive
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // Filter by frequency
            if ($request->has('frequency')) {
                $query->where('frequency', $request->frequency);
            }

            // Filter by date range
            if ($request->has('from_date')) {
                $query->where('effective_date', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->where('effective_date', '<=', $request->to_date);
            }

            $deductions = $query->orderBy('created_at', 'desc')->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $deductions,
                'summary' => [
                    'total_monthly' => $query->where('frequency', 'monthly')->sum('amount'),
                    'total_one_time' => $query->where('frequency', 'one-time')->sum('amount'),
                    'active_count' => $query->where('is_active', true)->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch deductions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add new deduction for an employee
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $storeId = $user->store_id;

            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|exists:employees,id',
                'deduction_type_id' => 'required|exists:deduction_types,id',
                'amount' => 'required|numeric|min:0',
                'effective_date' => 'required|date',
                'end_date' => 'nullable|date|after_or_equal:effective_date',
                'frequency' => 'required|in:monthly,one-time,bi-monthly,quarterly,annual',
                'reference_number' => 'nullable|string|max:100',
                'notes' => 'nullable|string',
                'is_mandatory' => 'boolean',
                'is_taxable' => 'boolean'
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
                ], 403);
            }

            // Check for overlapping active deductions of same type
            if ($request->frequency !== 'one-time') {
                $existing = EmployeeDeduction::where('employee_id', $request->employee_id)
                    ->where('deduction_type_id', $request->deduction_type_id)
                    ->where('is_active', true)
                    ->where('frequency', $request->frequency)
                    ->where(function($q) use ($request) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', $request->effective_date);
                    })
                    ->first();

                if ($existing) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Employee already has an active deduction of this type'
                    ], 422);
                }
            }

            DB::beginTransaction();

            $deduction = EmployeeDeduction::create([
                'employee_id' => $request->employee_id,
                'deduction_type_id' => $request->deduction_type_id,
                'amount' => $request->amount,
                'effective_date' => $request->effective_date,
                'end_date' => $request->end_date,
                'frequency' => $request->frequency,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes,
                'is_mandatory' => $request->is_mandatory ?? false,
                'is_taxable' => $request->is_taxable ?? true,
                'is_active' => true,
                'created_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Employee deduction added successfully',
                'data' => $deduction->load(['employee', 'deductionType', 'creator'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add deduction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single deduction details
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            $storeId = $user->store_id;

            $deduction = EmployeeDeduction::with([
                'employee', 
                'deductionType', 
                'creator',
                'updater'
            ])
            ->whereHas('employee', function($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->findOrFail($id);

            // Get deduction history/payments if any
            $history = $this->getDeductionHistory($deduction);

            return response()->json([
                'success' => true,
                'data' => $deduction,
                'history' => $history
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Deduction not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch deduction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update deduction details
     */
    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $storeId = $user->store_id;

            $deduction = EmployeeDeduction::whereHas('employee', function($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'amount' => 'sometimes|numeric|min:0',
                'end_date' => 'nullable|date|after_or_equal:effective_date',
                'frequency' => 'sometimes|in:monthly,one-time,bi-monthly,quarterly,annual',
                'reference_number' => 'nullable|string|max:100',
                'notes' => 'nullable|string',
                'is_mandatory' => 'boolean',
                'is_taxable' => 'boolean',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $deduction->update([
                'amount' => $request->amount ?? $deduction->amount,
                'end_date' => $request->end_date ?? $deduction->end_date,
                'frequency' => $request->frequency ?? $deduction->frequency,
                'reference_number' => $request->reference_number ?? $deduction->reference_number,
                'notes' => $request->notes ?? $deduction->notes,
                'is_mandatory' => $request->is_mandatory ?? $deduction->is_mandatory,
                'is_taxable' => $request->is_taxable ?? $deduction->is_taxable,
                'is_active' => $request->is_active ?? $deduction->is_active,
                'updated_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Deduction updated successfully',
                'data' => $deduction->fresh(['employee', 'deductionType', 'updater'])
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Deduction not found'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update deduction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deactivate a deduction (soft delete)
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $storeId = $user->store_id;

            $deduction = EmployeeDeduction::whereHas('employee', function($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })->findOrFail($id);

            DB::beginTransaction();

            $deduction->update([
                'is_active' => false,
                'deleted_by' => auth()->id()
            ]);

            $deduction->delete(); // Soft delete

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Deduction deactivated successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Deduction not found'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate deduction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk add deductions to multiple employees
     */
    public function bulkStore(Request $request)
    {
        try {
            $user = Auth::user();
            $storeId = $user->store_id;

            $validator = Validator::make($request->all(), [
                'employee_ids' => 'required|array',
                'employee_ids.*' => 'exists:employees,id',
                'deduction_type_id' => 'required|exists:deduction_types,id',
                'amount' => 'required|numeric|min:0',
                'effective_date' => 'required|date',
                'end_date' => 'nullable|date|after_or_equal:effective_date',
                'frequency' => 'required|in:monthly,one-time,bi-monthly,quarterly,annual',
                'notes' => 'nullable|string'
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
                ], 403);
            }

            DB::beginTransaction();

            $created = [];
            foreach ($request->employee_ids as $employeeId) {
                $deduction = EmployeeDeduction::create([
                    'employee_id' => $employeeId,
                    'deduction_type_id' => $request->deduction_type_id,
                    'amount' => $request->amount,
                    'effective_date' => $request->effective_date,
                    'end_date' => $request->end_date,
                    'frequency' => $request->frequency,
                    'notes' => $request->notes,
                    'is_active' => true,
                    'created_by' => auth()->id()
                ]);
                $created[] = $deduction;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($created) . ' deductions added successfully',
                'data' => $created
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add deductions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employee's deduction summary
     */
    public function employeeSummary($employeeId)
    {
        try {
            $user = Auth::user();
            $storeId = $user->store_id;

            $employee = Employee::where('id', $employeeId)
                ->where('store_id', $storeId)
                ->firstOrFail();

            $deductions = EmployeeDeduction::with('deductionType')
                ->where('employee_id', $employeeId)
                ->where('is_active', true)
                ->get();

            $summary = [
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->fname . ' ' . $employee->lname,
                    'employee_number' => $employee->employee_number
                ],
                'total_monthly_deductions' => $deductions->where('frequency', 'monthly')->sum('amount'),
                'total_one_time' => $deductions->where('frequency', 'one-time')->sum('amount'),
                'by_frequency' => $deductions->groupBy('frequency')->map(function($group) {
                    return [
                        'count' => $group->count(),
                        'total' => $group->sum('amount')
                    ];
                }),
                'deductions' => $deductions
            ];

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch employee deductions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get deduction history from payroll items
     */
    private function getDeductionHistory(EmployeeDeduction $deduction)
    {
        // This assumes you have payroll_items linked to this deduction
        // You can track actual deductions applied in past payrolls
        return \App\Models\Hr\PayrollItem::where('employee_id', $deduction->employee_id)
            ->where('type', 'deduction')
            ->where('name', 'LIKE', '%' . $deduction->deductionType->name . '%')
            ->with('payroll.payPeriod')
            ->orderBy('created_at', 'desc')
            ->take(12)
            ->get()
            ->map(function($item) {
                return [
                    'payroll_id' => $item->payroll_id,
                    'period' => $item->payroll->payPeriod->name ?? 'N/A',
                    'amount' => $item->amount,
                    'date' => $item->created_at->format('Y-m-d')
                ];
            });
    }

    /**
     * Get all deduction types (helper method)
     */
    public function getDeductionTypes()
    {
        try {
            $types = DeductionType::where('is_active', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $types
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch deduction types: ' . $e->getMessage()
            ], 500);
        }
    }
}