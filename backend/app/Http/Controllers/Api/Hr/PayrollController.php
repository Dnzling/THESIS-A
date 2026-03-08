<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Http\Resources\Hr\PayrollIndexResource;
use App\Models\Hr\Payroll;
use App\Models\Hr\PayPeriod;
use App\Models\Hr\Employee;
use App\Models\Hr\Attendance;
use App\Models\Hr\EmployeeDeduction;
use App\Models\Hr\Leave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            // Check if user has store access
            if (!$user->store_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not associated with any store'
                ], 403);
            }

            // Apply the scope directly to the query
            $query = Payroll::byUserStore(); // This automatically filters by user's store

            // Eager load with proper field selection
            $query->with([
                'employee' => function ($q) {
                    $q->select('id', 'fname', 'lname',  'employee_number', 'department', 'store_id', 'branch_id')
                        ->with('branch:id,name');
                },
                'payPeriod' => function ($q) {
                    $q->select('id', 'name');
                }
            ]);

            // Filter by pay period
            if ($request->has('pay_period_id')) {
                $query->where('pay_period_id', $request->pay_period_id);
            }

            // Filter by employee
            if ($request->has('employee_id')) {
                $query->where('employee_id', $request->employee_id);
                // No need to add store filter here because scope already handles it
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Order by
            $orderBy = $request->order_by ?? 'created_at';
            $orderDirection = $request->order_direction ?? 'desc';

            // Ensure the order by column exists
            $allowedOrderColumns = ['id', 'created_at', 'net_salary', 'status', 'payment_date'];
            if (!in_array($orderBy, $allowedOrderColumns)) {
                $orderBy = 'created_at';
            }

            $query->orderBy($orderBy, $orderDirection);

            $payrolls = $query->get();

            // Transform the paginated data
            $payrollsResource = PayrollIndexResource::collection($payrolls);

            // Summary - USE THE SAME SCOPE for consistency
            $summary = [
                'store_id' => $user->store_id,
                // 'total' => $payrolls->total(),
                'total_amount' => $payrolls->sum('net_salary') ?? 0,
                'draft' => Payroll::byUserStore()->where('status', 'draft')->count(),
                'approved' => Payroll::byUserStore()->where('status', 'approved')->count(),
                'paid' => Payroll::byUserStore()->where('status', 'paid')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $payrollsResource,
                'summary' => $summary,
                'store' => [
                    'id' => $user->store_id,
                    'name' => $user->store->name ?? 'Unknown Store'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payrolls: ' . $e->getMessage()
            ], 500);
        }
    }
    public function generate(Request $request)
    {
        try {
            $validated = $request->validate([
                'pay_period_id'  => 'required|exists:pay_periods,id',
                'employee_ids'   => 'nullable|array',
                'employee_ids.*' => 'exists:employees,id',
                'recalculate'    => 'boolean',
                'initial_status' => 'nullable|in:draft,processing',
            ]);

            $payPeriod = PayPeriod::find($validated['pay_period_id']);

            if ($payPeriod->end_date > now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot generate payroll for future periods'
                ], 400);
            }

            if ($payPeriod->status === 'locked' || $payPeriod->status === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot generate payroll for locked or completed period'
                ], 400);
            }

            // Get employees to process (filter by current user's store + active status)
            $user = Auth::user();
            $employees = Employee::where('store_id', $user->store_id)
                ->where('status', 'active');

            if (!empty($validated['employee_ids'])) {
                $employees->whereIn('id', $validated['employee_ids']);
            }

            $employees = $employees->get();

            if ($employees->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active employees found'
                ], 404);
            }

            DB::beginTransaction();

            $generated = [];
            $errors = [];

            foreach ($employees as $employee) {
                try {
                    // Check if payroll already exists
                    $existing = Payroll::where('employee_id', $employee->id)
                        ->where('pay_period_id', $payPeriod->id)
                        ->first();

                    if ($existing && !($validated['recalculate'] ?? false)) {
                        $generated[] = $existing;
                        continue;
                    }

                    // Calculate payroll
                    $payrollData = $this->calculateEmployeePayroll($employee, $payPeriod);

                    if ($existing) {
                        // Update existing payroll
                        $existing->update($payrollData);
                        $payroll = $existing;
                    } else {
                        // Create new payroll
                        $payroll = Payroll::create([
                            ...$payrollData,
                            'employee_id' => $employee->id,
                            'pay_period_id' => $payPeriod->id,
                            'status' => $validated['initial_status'] ?? 'draft',
                        ]);
                    }

                    // Generate payroll items
                    $this->generatePayrollItems($payroll, $payrollData);

                    $generated[] = $payroll->load(['employee.user', 'items']);
                } catch (\Exception $e) {
                    $errors[] = [
                        'employee' => $employee->fname . ' ' . $employee->lname,
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Update period status if not already processing
            if ($payPeriod->status === 'draft') {
                $payPeriod->update(['status' => 'processing']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payroll generated successfully',
                'data' => [
                    'generated' => count($generated),
                    'errors' => $errors,
                    'payrolls' => $generated
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate payroll: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $payroll = Payroll::with([
                'employee.user',
                'payPeriod',
                'items',
                'approvedBy',
                'paidBy'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $payroll
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payroll not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payroll: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Individual submit for approval: draft/calculated → processing
     */
    public function submit(Request $request, $id)
    {
        try {
            $payroll = Payroll::findOrFail($id);

            if (!in_array($payroll->status, ['draft', 'calculated'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft or calculated payrolls can be submitted for approval',
                ], 400);
            }

            $payroll->update(['status' => 'processing']);

            // Sync the pay period status
            $this->syncPeriodStatus($payroll->pay_period_id);

            return response()->json([
                'success' => true,
                'message' => 'Payroll submitted for approval',
                'data' => $payroll,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Payroll not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to submit payroll: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Individual approve: processing → approved
     */
    public function approve(Request $request, $id)
    {
        try {
            $payroll = Payroll::findOrFail($id);

            if (!in_array($payroll->status, ['draft', 'calculated', 'processing'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft, calculated, or processing payrolls can be approved'
                ], 400);
            }

            $validated = $request->validate([
                'notes' => 'nullable|string',
            ]);

            $payroll->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'notes' => $validated['notes'] ?? $payroll->notes,
            ]);

            // Sync the pay period status
            $this->syncPeriodStatus($payroll->pay_period_id);

            return response()->json([
                'success' => true,
                'message' => 'Payroll approved successfully',
                'data' => $payroll->load(['approvedBy'])
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payroll not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve payroll: ' . $e->getMessage()
            ], 500);
        }
    }

    public function markPaid(Request $request, $id)
    {
        try {
            $payroll = Payroll::findOrFail($id);

            if ($payroll->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only approved payrolls can be marked as paid'
                ], 400);
            }

            $validated = $request->validate([
                'payment_date' => 'required|date',
                'payment_method' => 'required|string|max:50',
                'reference_number' => 'nullable|string|max:100',
                'notes' => 'nullable|string',
            ]);

            $payroll->update([
                ...$validated,
                'status' => 'paid',
                'paid_by' => auth()->id(),
                'paid_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payroll marked as paid successfully',
                'data' => $payroll->load(['paidBy'])
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payroll not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark payroll as paid: ' . $e->getMessage()
            ], 500);
        }
    }

    public function report(Request $request)
    {
        try {
            $request->validate([
                'pay_period_id' => 'required|exists:pay_periods,id',
                'store_id' => 'nullable|exists:stores,id',
                'department' => 'nullable|string',
            ]);

            $query = Payroll::with(['employee.user', 'items'])
                ->where('pay_period_id', $request->pay_period_id)
                ->where('status', '!=', 'cancelled');

            if ($request->has('store_id')) {
                $query->whereHas('employee', function ($q) use ($request) {
                    $q->where('store_id', $request->store_id);
                });
            }

            if ($request->has('department')) {
                $query->whereHas('employee', function ($q) use ($request) {
                    $q->where('department', $request->department);
                });
            }

            $payrolls = $query->get();

            // Summary report
            $report = [
                'period' => PayPeriod::find($request->pay_period_id),
                'summary' => [
                    'total_employees' => $payrolls->count(),
                    'total_base_salary' => $payrolls->sum('base_salary'),
                    'total_overtime' => $payrolls->sum('overtime_amount'),
                    'total_deductions' => $payrolls->sum('deductions_total'),
                    'total_bonuses' => $payrolls->sum('bonuses_total'),
                    'total_tax' => $payrolls->sum('tax_amount'),
                    'total_net_salary' => $payrolls->sum('net_salary'),
                ],
                'by_department' => $payrolls->groupBy('employee.department')->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'total_salary' => $group->sum('net_salary'),
                    ];
                }),
                'payrolls' => $payrolls
            ];

            return response()->json([
                'success' => true,
                'data' => $report
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage()
            ], 500);
        }
    }

    private function calculateEmployeePayroll(Employee $employee, PayPeriod $period)
    {
        // 1. Get attendance summary
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$period->start_date, $period->cutoff_date])
            ->select(
                DB::raw('SUM(total_worked_minutes) as total_minutes'),
                DB::raw('SUM(overtime_minutes) as total_overtime'),
                DB::raw('SUM(late_minutes) as total_late'),
                DB::raw('SUM(night_differential_minutes) as total_night_diff'),
                DB::raw('COUNT(CASE WHEN status = "absent" THEN 1 END) as absent_days'),
                DB::raw('COUNT(CASE WHEN status = "half_day" THEN 1 END) as half_days'),
                DB::raw('COUNT(CASE WHEN status = "on_leave" THEN 1 END) as leave_days'),
                DB::raw('COUNT(CASE WHEN is_restday_work = 1 THEN 1 END) as restday_work_days')
            )
            ->first();

        // 2. Calculate hourly rate
        $hourlyRate = $employee->salary / 160; // 160 hours per month (20 days × 8 hours)
        $dailyRate = $employee->salary / 20; // 20 working days per month

        // 3. Calculate regular hours worked
        $regularHours = ($attendance->total_minutes ?? 0) / 60;

        // 4. Calculate late deductions (if company policy deducts for lateness)
        $lateHours = ($attendance->total_late ?? 0) / 60;
        $lateDeduction = $lateHours * $hourlyRate * 0.5; // 50% deduction for late hours

        // 5. Calculate night differential (usually +10% to +20%)
        $nightDiffHours = ($attendance->total_night_diff ?? 0) / 60;
        $nightDiffRate = config('payroll.night_diff_rate', 1.10); // 10% extra
        $nightDiffPay = $nightDiffHours * $hourlyRate * ($nightDiffRate - 1);

        // 6. Rest day work (usually +30% to +50%)
        $restDayPay = ($attendance->restday_work_days ?? 0) * $dailyRate * 0.3;

        // 7. Absent deductions
        $absentDeduction = ($attendance->absent_days ?? 0) * $dailyRate;

        // 8. Half-day deductions
        $halfDayDeduction = ($attendance->half_days ?? 0) * ($dailyRate / 2);

        // 9. Leave pay (if paid leave)
        $leaveDays = $attendance->leave_days ?? 0;
        $leavePay = 0;

        // Check if leave is paid (you'd need to join with leaves table)
        if ($leaveDays > 0) {
            $paidLeaves = Leave::where('employee_id', $employee->id)
                ->whereBetween('start_date', [$period->start_date, $period->cutoff_date])
                ->orWhereBetween('end_date', [$period->start_date, $period->cutoff_date])
                ->where('is_paid', true)
                ->where('status', 'approved')
                ->sum('total_days');

            $leavePay = $paidLeaves * $dailyRate;
        }

        // 10. Calculate totals
        $baseSalary = $employee->salary / 2; // Monthly salary
        $overtimeHours = ($attendance->total_overtime ?? 0) / 60;
        $overtimeRate = config('payroll.overtime_rate', $hourlyRate * 1.25);
        $overtimeAmount = $overtimeHours * $overtimeRate;

        // Gross pay = base salary + overtime + night diff + rest day + leave pay
        $grossPay = $baseSalary + $overtimeAmount + $nightDiffPay + $restDayPay + $leavePay;

        // Total deductions = late deduction + absent deduction + half day deduction + other deductions
        $otherDeductions = EmployeeDeduction::where('employee_id', $employee->id)
            ->active()
            ->sum('amount');

        $totalDeductions = $lateDeduction + $absentDeduction + $halfDayDeduction + $otherDeductions;

        // Tax calculation (simplified)
        $taxableIncome = $grossPay - $totalDeductions;
        $taxAmount = $this->calculateTax($taxableIncome);

        // Net salary
        $netSalary = $grossPay - $totalDeductions - $taxAmount;

        // Map only to columns that exist in the payrolls table
        return [
            'base_salary'       => $baseSalary,
            'overtime_hours'    => $overtimeHours,
            'overtime_amount'   => $overtimeAmount,
            'deductions_total'  => $totalDeductions,
            'bonuses_total'     => $restDayPay + $nightDiffPay + $leavePay, // rest day, night diff, leave pay as bonuses
            'allowances_total'  => 0,
            'tax_amount'        => $taxAmount,
            'net_salary'        => $netSalary,
            'late_minutes'      => (int) ($attendance->total_late ?? 0),
            'late_deduction'    => $lateDeduction,
            'late_occurrences'  => (int) ($attendance->absent_days ?? 0), // approximate; update if you track occurrences separately
        ];
    }

    private function calculateTax($taxableIncome)
    {
        // Simplified Philippine tax table (for illustration)
        if ($taxableIncome <= 20833) {
            return 0; // Minimum wage earner
        } elseif ($taxableIncome <= 33332) {
            return ($taxableIncome - 20833) * 0.15;
        } elseif ($taxableIncome <= 83332) {
            return 1875 + ($taxableIncome - 33333) * 0.20;
        } elseif ($taxableIncome <= 333332) {
            return 11875 + ($taxableIncome - 83333) * 0.25;
        } else {
            return 70875 + ($taxableIncome - 333333) * 0.30;
        }
    }


    public function calcEmployeePayroll(Request $request)
    {
        try {
            $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'pay_period_id' => 'required|exists:pay_periods,id'
            ]);

            $employee = Employee::findOrFail($request->employee_id);
            $period = PayPeriod::findOrFail($request->pay_period_id);

            $calculation = $this->calculateEmployeePayroll($employee, $period);

            return response()->json([
                'success' => true,
                'data' => [
                    'employee' => [
                        'id' => $employee->id,
                        'name' => $employee->fname . ' ' . $employee->lname,
                        'employee_number' => $employee->employee_number
                    ],
                    'pay_period' => [
                        'id' => $period->id,
                        'name' => $period->name,
                        'start_date' => $period->start_date,
                        'cutoff_date' => $period->cutoff_date
                    ],
                    'calculation' => $calculation,
                    'breakdown' => [
                        'hourly_rate' => $employee->salary / 160,
                        'overtime_rate' => config('payroll.overtime_rate', ($employee->salary / 160) * 1.25),
                        // 'total_hours_worked' => $this->getTotalHoursWorked($employee->id, $period)
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate payroll',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    private function generatePayrollItems(Payroll $payroll, array $payrollData)
    {
        // Ensure employee relationship is loaded
        if (!$payroll->relationLoaded('employee')) {
            $payroll->load('employee');
        }

        // Clear existing items if recalculating
        $payroll->items()->delete();

        // Add base salary item
        $payroll->items()->create([
            'type' => 'earning',
            'name' => 'Basic Salary',
            'amount' => $payrollData['base_salary'],
            'calculation_type' => 'fixed',
        ]);

        // Add overtime if any
        if (($payrollData['overtime_amount'] ?? 0) > 0) {
            $hourlyRate = $payroll->employee ? ($payroll->employee->salary / 160) : 0;
            $payroll->items()->create([
                'type' => 'earning',
                'name' => 'Overtime',
                'amount' => $payrollData['overtime_amount'],
                'calculation_type' => 'hourly',
                'rate' => config('payroll.overtime_rate', $hourlyRate),
                'quantity' => $payrollData['overtime_hours'] ?? 0,
            ]);
        }

        // Add tax deduction
        if ($payrollData['tax_amount'] > 0) {
            $payroll->items()->create([
                'type' => 'tax',
                'name' => 'Income Tax',
                'amount' => $payrollData['tax_amount'],
                'calculation_type' => 'percentage',
                'rate' => 10, // 10%
            ]);
        }

        // Add other deductions
        $deductions = EmployeeDeduction::where('employee_id', $payroll->employee_id)
            ->active()
            ->with('deductionType')
            ->get();

        foreach ($deductions as $deduction) {
            $payroll->items()->create([
                'type' => 'deduction',
                'name' => $deduction->deductionType->name,
                'amount' => $deduction->amount,
                'calculation_type' => 'fixed',
                'notes' => $deduction->notes,
            ]);
        }
    }

    public function getEmployeeBasicSalary(Request $request)
    {
        try {
            $user = Auth::user();

            // Check if user has store access
            if (!$user->store_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not associated with any store'
                ], 403);
            }

            // Get pay period if specified, otherwise use current
            $payPeriodId = $request->pay_period_id;
            $month = $request->month ?? now()->month;
            $year = $request->year ?? now()->year;

            // Build query for employees with their basic salary
            $query = Employee::query()
                ->where('store_id', $user->store_id)
                ->whereIn('status', ['active', 'on_leave']) // Include active and on-leave employees
                ->select([
                    'id',
                    'employee_number',
                    'fname',
                    'lname',
                    'store_id',
                    'branch_id',
                    'department',
                    'employment_type',
                    'salary as monthly_salary',
                    'status',
                    'hire_date',
                    'role_id'
                ])
                ->with(['branch:id,name', 'role:id,display_name']) // Eager load relationships if needed
                ->orderBy('fname')
                ->orderBy('lname');

            // Get pay period details if specified
            $payPeriod = null;
            if ($payPeriodId) {
                $payPeriod = PayPeriod::find($payPeriodId);
            }

            // Transform the data to include bi-calendar calculations
            $employees = $query->get()->map(function ($employee) use ($payPeriod, $month, $year) {

                // Calculate daily rate based on employment type
                $dailyRate = $this->calculateDailyRate($employee->monthly_salary, $employee->employment_type);

                // Calculate semi-monthly rate (bi-calendar)
                $semiMonthlyRate = $this->calculateSemiMonthlyRate(
                    $employee->monthly_salary,
                    $employee->employment_type,
                    $month,
                    $year
                );

                // If pay period is specified, calculate based on actual days in that period
                $periodSalary = null;
                $periodDays = null;
                $periodStart = null;
                $periodEnd = null;

                if ($payPeriod) {
                    $periodSalary = $this->calculatePayPeriodSalary(
                        $employee->monthly_salary,
                        $payPeriod->start_date,
                        $payPeriod->end_date,
                        $employee->employment_type
                    );
                    $periodDays = $this->getWorkingDaysInPeriod($payPeriod->start_date, $payPeriod->end_date);
                    $periodStart = $payPeriod->start_date;
                    $periodEnd = $payPeriod->end_date;
                }

                return [
                    'id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'full_name' => $employee->fname . ' ' . $employee->lname,
                    'first_name' => $employee->fname,
                    'last_name' => $employee->lname,
                    'store_id' => $employee->store_id,
                    'branch' => $employee->branch->name ?? 'N/A',
                    'department' => $employee->department,
                    'role' => $employee->role->display_name ?? 'N/A',
                    'employment_type' => $employee->employment_type,
                    'status' => $employee->status,
                    'employee' => $employee,

                    // Salary Information
                    'monthly_salary' => (float) $employee->monthly_salary,
                    'daily_rate' => round($dailyRate, 2),
                    'hourly_rate' => round($dailyRate / 8, 2), // Assuming 8-hour work day

                    // Bi-Calendar (Semi-Monthly) Calculations
                    'semi_monthly_rate' => round($semiMonthlyRate, 2),
                    'first_half_salary' => round($this->calculateHalfMonthSalary($employee->monthly_salary, 'first', $month, $year), 2),
                    'second_half_salary' => round($this->calculateHalfMonthSalary($employee->monthly_salary, 'second', $month, $year), 2),

                    // Pay Period Specific (if selected)
                    'pay_period_salary' => $periodSalary ? round($periodSalary, 2) : null,
                    'pay_period_days' => $periodDays,
                    'pay_period_start' => $periodStart,
                    'pay_period_end' => $periodEnd,

                    // Hire Date Info (for pro-rated calculations)
                    'hire_date' => $employee->hire_date,
                    'months_employed' => $this->getMonthsEmployed($employee->hire_date),

                    // Formatted for display
                    'formatted' => [
                        'monthly_salary' => '₱' . number_format($employee->monthly_salary, 2),
                        'daily_rate' => '₱' . number_format($dailyRate, 2),
                        'semi_monthly' => '₱' . number_format($semiMonthlyRate, 2),
                    ]
                ];
            });

            // Get summary statistics
            $summary = [
                'total_employees' => $employees->count(),
                'total_active' => $employees->where('status', 'active')->count(),
                'total_monthly_payroll' => $employees->sum('monthly_salary'),
                'total_semi_monthly_payroll' => $employees->sum('semi_monthly_rate'),
                'average_monthly_salary' => $employees->avg('monthly_salary'),
                'by_employment_type' => $employees->groupBy('employment_type')
                    ->map(function ($group) {
                        return [
                            'count' => $group->count(),
                            'total_monthly' => $group->sum('monthly_salary')
                        ];
                    }),
                'by_department' => $employees->groupBy('department')
                    ->map(function ($group) {
                        return [
                            'count' => $group->count(),
                            'total_monthly' => $group->sum('monthly_salary')
                        ];
                    })->take(5) // Top 5 departments
            ];

            // If pay period is specified, add period summary
            if ($payPeriod) {
                $summary['pay_period'] = [
                    'id' => $payPeriod->id,
                    'name' => $payPeriod->name,
                    'start_date' => $payPeriod->start_date,
                    'end_date' => $payPeriod->end_date,
                    'total_payroll' => $employees->sum('pay_period_salary')
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $employees,
                'summary' => $summary,
                'meta' => [
                    'store_id' => $user->store_id,
                    'month' => $month,
                    'year' => $year,
                    'pay_period_id' => $payPeriodId,
                    'total_records' => $employees->count(),
                    'calculation_basis' => $payPeriod ? 'pay_period' : 'semi_monthly'
                ]
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve employee salary information',
                'error' => config('app.debug') ? $th->getMessage() : null
            ], 500);
        }
    }

    /**
     * Calculate daily rate based on employment type
     */
    private function calculateDailyRate($monthlySalary, $employmentType)
    {
        // Standard: 261 working days per year (excluding holidays)
        // Monthly equivalent: 261 / 12 = 21.75 working days per month
        $workingDaysPerMonth = 21.75; // Standard for monthly-rated employees

        switch ($employmentType) {
            case 'part_time':
                // Part-time works 4 hours per day
                return ($monthlySalary / $workingDaysPerMonth) * 0.5;

            case 'contract':
            case 'intern':
                // Contractual might have different basis
                return $monthlySalary / $workingDaysPerMonth;

            default: // full_time
                return $monthlySalary / $workingDaysPerMonth;
        }
    }

    /**
     * Calculate semi-monthly rate (bi-calendar)
     */
    private function calculateSemiMonthlyRate($monthlySalary, $employmentType, $month, $year)
    {
        // For semi-monthly: Monthly salary / 2
        // But for months with 15 days in first half and remaining in second half
        $baseSemiMonthly = $monthlySalary / 2;

        // Adjust for employment type
        switch ($employmentType) {
            case 'part_time':
                return $baseSemiMonthly * 0.5;

            case 'contract':
                return $baseSemiMonthly * 0.75;

            default:
                return $baseSemiMonthly;
        }
    }

    /**
     * Calculate half-month salary (1st or 2nd half)
     */
    private function calculateHalfMonthSalary($monthlySalary, $half, $month, $year)
    {
        $daysInMonth = Carbon::create($year, $month)->daysInMonth;

        if ($half === 'first') {
            // First half: Days 1-15
            $daysInFirstHalf = 15;
            return ($monthlySalary / $daysInMonth) * $daysInFirstHalf;
        } else {
            // Second half: Days 16 to end of month
            $daysInSecondHalf = $daysInMonth - 15;
            return ($monthlySalary / $daysInMonth) * $daysInSecondHalf;
        }
    }

    /**
     * Calculate salary for a specific pay period
     */
    private function calculatePayPeriodSalary($monthlySalary, $startDate, $endDate, $employmentType)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // Get the month of the pay period
        $month = $start->month;
        $year = $start->year;
        $daysInMonth = Carbon::create($year, $month)->daysInMonth;

        // Calculate number of calendar days in this pay period
        $periodDays = $start->diffInDays($end) + 1; // +1 to include both start and end

        // Prorate based on days in period
        $proratedSalary = ($monthlySalary / $daysInMonth) * $periodDays;

        // Adjust for employment type
        switch ($employmentType) {
            case 'part_time':
                return $proratedSalary * 0.5;
            case 'contract':
                return $proratedSalary * 0.75;
            default:
                return $proratedSalary;
        }
    }

    /**
     * Get number of working days in a period (excluding weekends)
     */
    private function getWorkingDaysInPeriod($startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $workingDays = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            if (!$current->isWeekend()) {
                $workingDays++;
            }
            $current->addDay();
        }

        return $workingDays;
    }

    /**
     * Get months employed
     */
    private function getMonthsEmployed($hireDate)
    {
        if (!$hireDate) return 0;

        return Carbon::parse($hireDate)->diffInMonths(now());
    }

    public function generatePayslip($id)
    {
        $payroll = Payroll::with(['employee', 'payPeriod', 'items'])
            ->findOrFail($id);

        return response()->json([
            'payslip' => [
                'header' => [
                    'payroll_id' => $payroll->id,
                    'employee_name' => $payroll->employee->fname . ' ' . $payroll->employee->lname,
                    'employee_number' => $payroll->employee->employee_number,
                    'department' => $payroll->employee->department,
                    'period' => $payroll->payPeriod->name,
                    'pay_date' => $payroll->payment_date,
                    'status' => $payroll->status
                ],
                'earnings' => $payroll->items->where('type', 'earning'),
                'deductions' => $payroll->items->whereIn('type', ['deduction', 'tax']),
                'allowances' => $payroll->items->where('type', 'allowance'),
                'bonuses' => $payroll->items->where('type', 'bonus'),
                'summary' => [
                    'gross_pay' => $payroll->base_salary + $payroll->overtime_amount + $payroll->bonuses_total + $payroll->allowances_total,
                    'total_deductions' => $payroll->deductions_total + $payroll->tax_amount,
                    'net_pay' => $payroll->net_salary
                ]
            ]
        ]);
    }

    public function generateBulk(Request $request)
    {
        try {
            $validated = $request->validate([
                'pay_period_id' => 'required|exists:pay_periods,id',
                'department' => 'nullable|string',
                'employment_type' => 'nullable|string',
            ]);

            $payPeriod = PayPeriod::find($validated['pay_period_id']);

            // Get employees to process
            $employees = Employee::where('store_id', Auth::user()->store_id)
                ->where('status', 'active');

            if ($request->has('department')) {
                $employees->where('department', $request->department);
            }

            if ($request->has('employment_type')) {
                $employees->where('employment_type', $request->employment_type);
            }

            $employees = $employees->get();
            $total = $employees->count();

            if ($total === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No employees found'
                ], 404);
            }

            DB::beginTransaction();

            $processed = 0;
            $errors = [];

            foreach ($employees as $employee) {
                try {
                    // Check if payroll exists
                    $existing = Payroll::where('employee_id', $employee->id)
                        ->where('pay_period_id', $payPeriod->id)
                        ->first();

                    if ($existing && $existing->status !== 'draft') {
                        continue; // Skip if already processed
                    }

                    // Calculate and save
                    $payrollData = $this->calculateEmployeePayroll($employee, $payPeriod);

                    if ($existing) {
                        $existing->update($payrollData);
                    } else {
                        Payroll::create([
                            ...$payrollData,
                            'employee_id' => $employee->id,
                            'pay_period_id' => $payPeriod->id,
                            'status' => 'draft',
                        ]);
                    }

                    $processed++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'employee' => $employee->fname . ' ' . $employee->lname,
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Processed $processed out of $total employees",
                'data' => [
                    'total' => $total,
                    'processed' => $processed,
                    'errors' => $errors
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate bulk payroll: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk submit for approval: draft/calculated → processing
     */
    public function bulkSubmitForApproval(Request $request)
    {
        try {
            $validated = $request->validate([
                'payroll_ids' => 'required|array|min:1',
                'payroll_ids.*' => 'exists:payrolls,id',
            ]);

            $payrolls = Payroll::whereIn('id', $validated['payroll_ids'])
                ->whereIn('status', ['draft', 'calculated'])
                ->get();

            if ($payrolls->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No eligible payrolls found (must be in draft or calculated status)',
                ], 400);
            }

            DB::beginTransaction();
            $payPeriodIds = [];
            foreach ($payrolls as $payroll) {
                $payroll->update(['status' => 'processing']);
                $payPeriodIds[] = $payroll->pay_period_id;
            }
            DB::commit();

            // Sync period statuses
            foreach (array_unique($payPeriodIds) as $periodId) {
                $this->syncPeriodStatus($periodId);
            }

            return response()->json([
                'success' => true,
                'message' => $payrolls->count() . ' payroll(s) submitted for approval',
                'submitted_count' => $payrolls->count(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk submit payrolls: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk approve: processing → approved
     */
    public function bulkApprove(Request $request)
    {
        try {
            $validated = $request->validate([
                'payroll_ids' => 'required|array|min:1',
                'payroll_ids.*' => 'exists:payrolls,id',
            ]);

            $payrolls = Payroll::whereIn('id', $validated['payroll_ids'])
                ->whereIn('status', ['draft', 'calculated', 'processing'])
                ->get();

            if ($payrolls->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No eligible payrolls found for approval',
                ], 400);
            }

            DB::beginTransaction();
            $payPeriodIds = [];
            foreach ($payrolls as $payroll) {
                $payroll->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
                $payPeriodIds[] = $payroll->pay_period_id;
            }
            DB::commit();

            // Sync period statuses
            foreach (array_unique($payPeriodIds) as $periodId) {
                $this->syncPeriodStatus($periodId);
            }

            return response()->json([
                'success' => true,
                'message' => $payrolls->count() . ' payroll(s) approved successfully',
                'approved_count' => $payrolls->count(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk approve payrolls: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync pay period status based on its payroll statuses:
     * - ANY draft/calculated  → period = draft
     * - ALL processing        → period = processing
     * - ALL approved          → period = locked
     * - ALL paid              → period = completed
     */
    private function syncPeriodStatus(int $payPeriodId): void
    {
        $period = PayPeriod::find($payPeriodId);
        if (!$period) return;

        $statuses = Payroll::where('pay_period_id', $payPeriodId)
            ->pluck('status')
            ->unique()
            ->values()
            ->toArray();

        if (empty($statuses)) return;

        if (in_array('draft', $statuses) || in_array('calculated', $statuses)) {
            $newStatus = 'draft';
        } elseif (in_array('processing', $statuses)) {
            $newStatus = 'processing';
        } elseif (count($statuses) === 1 && $statuses[0] === 'approved') {
            $newStatus = 'locked';
        } elseif (count($statuses) === 1 && $statuses[0] === 'paid') {
            $newStatus = 'completed';
        } else {
            $newStatus = 'processing';
        }

        if ($period->status !== $newStatus) {
            $period->update(['status' => $newStatus]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $payroll = Payroll::findOrFail($id);

            if (!in_array($payroll->status, ['draft', 'calculated'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft or calculated payrolls can be updated',
                ], 400);
            }

            $validated = $request->validate([
                'allowances_total' => 'nullable|numeric|min:0',
                'bonuses_total'    => 'nullable|numeric|min:0',
                'late_deduction'   => 'nullable|numeric|min:0',
                'deductions_total' => 'nullable|numeric|min:0',
                'notes'            => 'nullable|string',
            ]);

            $payroll->fill($validated);
            $payroll->net_salary = $payroll->calculateNetSalary();
            $payroll->save();

            return response()->json([
                'success' => true,
                'messa9ge' => 'Payroll updated successfully',
                'data'    => $payroll,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payroll not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payroll: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function testCalculatePayroll(Request $request)
    {
        try {
            $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'pay_period_id' => 'required|exists:pay_periods,id'
            ]);

            $employee = Employee::findOrFail($request->employee_id);
            $period = PayPeriod::findOrFail($request->pay_period_id);

            // Get attendance records for this period
            $attendances = Attendance::where('employee_id', $employee->id)
                ->whereBetween('attendance_date', [$period->start_date, $period->cutoff_date])
                ->get();

            // Calculate payroll
            $calculation = $this->calculateEmployeePayroll($employee, $period);

            return response()->json([
                'success' => true,
                'data' => [
                    'employee' => [
                        'id' => $employee->id,
                        'name' => $employee->fname . ' ' . $employee->lname,
                        'salary' => $employee->salary,
                        'hourly_rate' => round($employee->salary / 160, 2),
                        'daily_rate' => round($employee->salary / 20, 2)
                    ],
                    'pay_period' => [
                        'id' => $period->id,
                        'name' => $period->name,
                        'start_date' => $period->start_date,
                        'cutoff_date' => $period->cutoff_date,
                        'status' => $period->status
                    ],
                    'attendance_summary' => [
                        'total_records' => $attendances->count(),
                        'total_worked_minutes' => $attendances->sum('total_worked_minutes'),
                        'total_worked_hours' => round($attendances->sum('total_worked_minutes') / 60, 2),
                        'total_overtime_minutes' => $attendances->sum('overtime_minutes'),
                        'total_overtime_hours' => round($attendances->sum('overtime_minutes') / 60, 2),
                        'total_late_minutes' => $attendances->sum('late_minutes'),
                        'by_status' => $attendances->groupBy('status')->map->count()
                    ],
                    'calculation' => $calculation,
                    'breakdown' => [
                        'hourly_rate' => round($employee->salary / 160, 2),
                        'daily_rate' => round($employee->salary / 20, 2),
                        'overtime_rate' => round(($employee->salary / 160) * 1.25, 2),
                        'night_diff_rate' => config('payroll.night_diff_rate', 1.10)
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate payroll',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    public function overview(Request $request)
    {
        try {
            $user = Auth::user();

            // Check if user has store access
            if (!$user->store_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not associated with any store'
                ], 403);
            }

            // Get current year or specified year
            $year = $request->get('year', now()->year);

            // Summary Statistics
            $summary = [
                'total_payroll' => Payroll::byUserStore()->sum('net_salary'),
                'pending_approval' => Payroll::byUserStore()->where('status', 'processing')->count(),
                'paid_amount' => Payroll::byUserStore()->where('status', 'paid')->sum('net_salary'),
                'active_employees' => Employee::where('store_id', $user->store_id)->where('status', 'active')->count(),
            ];

            // Payroll Trends (Last 6 months)
            $trends = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthStart = $date->copy()->startOfMonth();
                $monthEnd = $date->copy()->endOfMonth();

                $monthlyTotal = Payroll::byUserStore()
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->sum('net_salary');

                $trends[] = [
                    'month' => $date->format('M Y'),
                    'amount' => (float) $monthlyTotal
                ];
            }

            // Department Breakdown
            $departmentData = Payroll::byUserStore()
                ->with(['employee' => function ($q) {
                    $q->select('id', 'department');
                }])
                ->get()
                ->groupBy('employee.department')
                ->map(function ($payrolls, $department) {
                    return [
                        'department' => $department ?: 'Unassigned',
                        'total_payroll' => $payrolls->sum('net_salary'),
                        'employee_count' => $payrolls->unique('employee_id')->count()
                    ];
                })
                ->values()
                ->sortByDesc('total_payroll')
                ->take(5); // Top 5 departments

            // Upcoming Pay Periods
            $upcomingPeriods = PayPeriod::where('store_id', $user->store_id)
                ->where('end_date', '>', now())
                ->where('status', '!=', 'completed')
                ->orderBy('start_date')
                ->take(2)
                ->get()
                ->map(function ($period) {
                    return [
                        'id' => $period->id,
                        'period' => $period->name,
                        'cutoff' => $period->cutoff_date->format('M d, Y'),
                        'payDate' => $period->end_date->format('M d, Y'),
                        'employees' => Payroll::where('pay_period_id', $period->id)->count()
                    ];
                });

            // Recent Activities (Last 10 payroll actions)
            $recentActivities = Payroll::byUserStore()
                ->with(['employee' => function ($q) {
                    $q->select('id', 'fname', 'lname');
                }])
                ->orderBy('updated_at', 'desc')
                ->take(10)
                ->get()
                ->map(function ($payroll) {
                    $action = '';
                    $icon = '';
                    $color = '';

                    switch ($payroll->status) {
                        case 'draft':
                            $action = 'Payroll created for ' . $payroll->employee->fname . ' ' . $payroll->employee->lname;
                            $icon = 'pi pi-plus-circle';
                            $color = 'text-blue-500';
                            break;
                        case 'processing':
                            $action = 'Payroll submitted for approval - ' . $payroll->employee->fname . ' ' . $payroll->employee->lname;
                            $icon = 'pi pi-clock';
                            $color = 'text-yellow-500';
                            break;
                        case 'approved':
                            $action = 'Payroll approved for ' . $payroll->employee->fname . ' ' . $payroll->employee->lname;
                            $icon = 'pi pi-check-circle';
                            $color = 'text-green-500';
                            break;
                        case 'paid':
                            $action = 'Payroll marked as paid - ' . $payroll->employee->fname . ' ' . $payroll->employee->lname;
                            $icon = 'pi pi-money-bill';
                            $color = 'text-purple-500';
                            break;
                        default:
                            $action = 'Payroll updated for ' . $payroll->employee->fname . ' ' . $payroll->employee->lname;
                            $icon = 'pi pi-pencil';
                            $color = 'text-gray-500';
                    }

                    return [
                        'action' => $action,
                        'time' => $payroll->updated_at->diffForHumans(),
                        'icon' => $icon . ' ' . $color
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'trends' => $trends,
                    'department_breakdown' => $departmentData,
                    'upcoming_periods' => $upcomingPeriods,
                    'recent_activities' => $recentActivities
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payroll overview: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getEmployeePayslips(Request $request, $employeeId)
    {
        try {
            $user = Auth::user();

            // Check if user has store access
            if (!$user->store_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not associated with any store'
                ], 403);
            }

            // Verify employee belongs to user's store
            $employee = Employee::where('store_id', $user->store_id)
                ->findOrFail($employeeId);

            // Build query
            $query = Payroll::with([
                'payPeriod',
                'items' => function ($q) {
                    $q->select('id', 'payroll_id', 'type', 'name', 'amount', 'calculation_type', 'rate', 'quantity');
                }
            ])
                ->where('employee_id', $employeeId)
                ->where('reference_number', '!=', null)
                ->whereHas('payPeriod', function ($q) use ($user) {
                    $q->where('store_id', $user->store_id);
                });

            // ===== FILTER BY YEAR =====
            if ($request->has('year') && !empty($request->year)) {
                $query->whereHas('payPeriod', function ($q) use ($request) {
                    $q->whereYear('start_date', $request->year);
                });
            }

            // ===== FILTER BY MONTH =====
            if ($request->has('month') && !empty($request->month)) {
                $query->whereHas('payPeriod', function ($q) use ($request) {
                    $q->whereMonth('start_date', $request->month);
                });
            }

            // ===== FILTER BY STATUS =====
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // ===== FILTER BY DATE RANGE =====
            if ($request->has('from_date') && !empty($request->from_date)) {
                $query->whereHas('payPeriod', function ($q) use ($request) {
                    $q->whereDate('start_date', '>=', $request->from_date);
                });
            }

            if ($request->has('to_date') && !empty($request->to_date)) {
                $query->whereHas('payPeriod', function ($q) use ($request) {
                    $q->whereDate('end_date', '<=', $request->to_date);
                });
            }

            // ===== SORTING =====
            $sortField = $request->get('sort_field', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');

            $allowedSortFields = ['created_at', 'pay_period_id', 'net_salary', 'status'];
            if (in_array($sortField, $allowedSortFields)) {
                $query->orderBy($sortField, $sortDirection);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // ===== PAGINATION =====
            $perPage = $request->get('per_page', 15);

            if ($request->has('paginate') && $request->paginate == 'false') {
                $payrolls = $query->get();
            } else {
                $payrolls = $query->paginate($perPage);
            }

            // Calculate summary statistics
            $summary = $this->calculatePayslipSummary($query, $employeeId, $request);

            // Transform the data
            $transformedPayrolls = $payrolls instanceof \Illuminate\Pagination\AbstractPaginator
                ? $payrolls->through(function ($payroll) {
                    return $this->transformPayslipData($payroll);
                })
                : $payrolls->map(function ($payroll) {
                    return $this->transformPayslipData($payroll);
                });

            return response()->json([
                'success' => true,
                'data' => $transformedPayrolls,
                'summary' => $summary,
                'filters' => [
                    'employee_id' => $employeeId,
                    'employee_name' => $employee->fname . ' ' . $employee->lname,
                    'year' => $request->year,
                    'month' => $request->month,
                    'status' => $request->status,
                    'from_date' => $request->from_date,
                    'to_date' => $request->to_date
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch employee payslips',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Transform payslip data for frontend
     */
    private function transformPayslipData($payroll)
    {
        // Calculate gross pay
        $grossPay = $payroll->base_salary +
            $payroll->overtime_amount +
            $payroll->bonuses_total +
            $payroll->allowances_total;

        // Group items by type
        $earnings = $payroll->items->where('type', 'earning')->values();
        $deductions = $payroll->items->whereIn('type', ['deduction', 'tax'])->values();
        $allowances = $payroll->items->where('type', 'allowance')->values();
        $bonuses = $payroll->items->where('type', 'bonus')->values();

        return [
            'id' => $payroll->id,
            'employee_id' => $payroll->employee_id,
            'pay_period_id' => $payroll->pay_period_id,
            'base_salary' => (float) $payroll->base_salary,
            'overtime_hours' => (float) $payroll->overtime_hours,
            'overtime_amount' => (float) $payroll->overtime_amount,
            'deductions_total' => (float) $payroll->deductions_total,
            'bonuses_total' => (float) $payroll->bonuses_total,
            'allowances_total' => (float) $payroll->allowances_total,
            'tax_amount' => (float) $payroll->tax_amount,
            'net_salary' => (float) $payroll->net_salary,
            'late_minutes' => (int) $payroll->late_minutes,
            'late_deduction' => (float) $payroll->late_deduction,
            'late_occurrences' => (int) $payroll->late_occurrences,
            'status' => $payroll->status,
            'payment_date' => $payroll->payment_date,
            'payment_method' => $payroll->payment_method,
            'reference_number' => $payroll->reference_number,
            'notes' => $payroll->notes,
            'approved_by' => $payroll->approved_by,
            'approved_at' => $payroll->approved_at,
            'paid_by' => $payroll->paid_by,
            'paid_at' => $payroll->paid_at,
            'created_at' => $payroll->created_at,
            'updated_at' => $payroll->updated_at,
            'gross_pay' => $grossPay,
            'pay_period' => $payroll->pay_period ? [
                'id' => $payroll->pay_period->id,
                'name' => $payroll->pay_period->name,
                'start_date' => $payroll->pay_period->start_date,
                'end_date' => $payroll->pay_period->end_date,
                'status' => $payroll->pay_period->status
            ] : null,
            'items' => [
                'earnings' => $earnings,
                'deductions' => $deductions,
                'allowances' => $allowances,
                'bonuses' => $bonuses,
                'all' => $payroll->items
            ]
        ];
    }

    /**
     * Calculate payslip summary statistics
     */
    private function calculatePayslipSummary($query, $employeeId, $request)
    {
        // Get all payrolls for this employee (without pagination for accurate summary)
        $allPayrollsQuery = clone $query;

        // Apply year filter to summary if specified
        if ($request->has('year') && !empty($request->year)) {
            $allPayrollsQuery->whereHas('payPeriod', function ($q) use ($request) {
                $q->whereYear('start_date', $request->year);
            });
        }

        $allPayrolls = $allPayrollsQuery->get();

        if ($allPayrolls->isEmpty()) {
            return [
                'total_gross' => 0,
                'total_net' => 0,
                'average_monthly' => 0,
                'payroll_count' => 0,
                'month_count' => 0,
                'by_status' => [],
                'ytd' => [
                    'gross' => 0,
                    'net' => 0,
                    'tax' => 0,
                    'deductions' => 0
                ]
            ];
        }

        $totalGross = 0;
        $totalNet = 0;
        $totalTax = 0;
        $totalDeductions = 0;
        $monthsSet = collect();
        $byStatus = [];

        foreach ($allPayrolls as $payroll) {
            $gross = $payroll->base_salary +
                $payroll->overtime_amount +
                $payroll->bonuses_total +
                $payroll->allowances_total;

            $totalGross += $gross;
            $totalNet += $payroll->net_salary;
            $totalTax += $payroll->tax_amount;
            $totalDeductions += $payroll->deductions_total;

            // Group by status
            if (!isset($byStatus[$payroll->status])) {
                $byStatus[$payroll->status] = [
                    'count' => 0,
                    'total' => 0
                ];
            }
            $byStatus[$payroll->status]['count']++;
            $byStatus[$payroll->status]['total'] += $payroll->net_salary;

            // Track unique months
            if ($payroll->pay_period && $payroll->pay_period->start_date) {
                $date = new Carbon($payroll->pay_period->start_date);
                $monthsSet->push($date->format('Y-m'));
            }
        }

        $uniqueMonths = $monthsSet->unique()->values();
        $monthCount = $uniqueMonths->count();

        // Get YTD summary (current year)
        $currentYear = date('Y');
        $ytdPayrolls = $allPayrolls->filter(function ($payroll) use ($currentYear) {
            return $payroll->pay_period &&
                date('Y', strtotime($payroll->pay_period->start_date)) == $currentYear;
        });

        $ytdGross = 0;
        $ytdNet = 0;
        $ytdTax = 0;
        $ytdDeductions = 0;

        foreach ($ytdPayrolls as $payroll) {
            $ytdGross += $payroll->base_salary +
                $payroll->overtime_amount +
                $payroll->bonuses_total +
                $payroll->allowances_total;
            $ytdNet += $payroll->net_salary;
            $ytdTax += $payroll->tax_amount;
            $ytdDeductions += $payroll->deductions_total;
        }

        return [
            'total_gross' => round($totalGross, 2),
            'total_net' => round($totalNet, 2),
            'average_monthly' => $monthCount > 0 ? round($totalNet / $monthCount, 2) : 0,
            'payroll_count' => $allPayrolls->count(),
            'month_count' => $monthCount,
            'by_status' => $byStatus,
            'ytd' => [
                'gross' => round($ytdGross, 2),
                'net' => round($ytdNet, 2),
                'tax' => round($ytdTax, 2),
                'deductions' => round($ytdDeductions, 2)
            ]
        ];
    }
}
