<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Models\Core\User;
use App\Models\Hr\Attendance;
use App\Models\Hr\Employee;
use App\Models\Hr\Leave;
use App\Models\Hr\LeaveBalance;
use App\Models\Hr\Payroll;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller
{
    /**
     * List employees
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            // Get total count for the user's store
            $totalEmployees = Employee::where('store_id', $user->store_id)->count();

            $activeEmployees = Employee::where('store_id', $user->store_id)
                ->where('status', 'active')
                ->count();

            $inactiveEmployees = Employee::where('store_id', $user->store_id)
                ->where('status', 'inactive')
                ->count();

            $onLeaveEmployees = Employee::where('store_id', $user->store_id)
                ->where('status', 'on_leave')
                ->count();

            // Get department count
            $departmentCount = Employee::where('store_id', $user->store_id)
                ->distinct('department')
                ->count('department');

            $query = Employee::with('user');

            // Add filters
            if ($request->has('store_id')) {
                $query->where('store_id', $request->store_id);
            }

            if ($request->has('department')) {
                $query->where('department', $request->department);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('employee_number', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($q2) use ($search) {
                            $q2->where('fname', 'like', "%{$search}%")
                                ->orWhere('lname', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            }

            // Get filtered count
            $filteredCount = $query->count();

            // Get all results with only essential fields for table preview
            $employees = $query->get()->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'fname' => $employee->fname,
                    'lname' => $employee->lname,
                    'employee_number' => $employee->employee_number,
                    'role_name' => $employee->user->role_name ?? null,
                    'department' => $employee->department,
                    'status' => ucfirst($employee->status),
                    'hireDate' => $employee->hire_date,
                    'email' => $employee->user->email ?? null,
                    'branch' => $employee->user->branch->name ?? null,
                    'phone' => $employee->phone
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $employees,
                'counts' => [
                    'total' => $totalEmployees,
                    'active' => $activeEmployees,
                    'onleave' => $onLeaveEmployees,
                    'inactive' => $inactiveEmployees,
                    'departments' => $departmentCount,
                    'filtered' => $filteredCount
                ]
            ], 200); // Changed to 200 OK instead of 201

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch employees: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Store a newly created employee
     */
    public function store(Request $request)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Get the current user's ID
            $current_user_id = $user->id;

            // Get the user's store_id
            $storeId = $user->store_id;

            if (!$storeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User does not have a store assigned'
                ], 400);
            }

            // Simple validation
            $validated = $request->validate([
                'branch_id' => 'nullable|exists:branches,id',
                'fname' => 'required|string|max:100',
                'lname' => 'required|string|max:100',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
                'role_id' => 'required|exists:roles,id',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|in:male,female,other',
                'hire_date' => 'required|date',
                'department' => 'required|string|max:255',
                'employment_type' => 'required|in:full_time,part_time,contract,intern',
                'salary' => 'required|numeric|min:0',
                'status' => 'required|in:active,on_leave,suspended,terminated'
            ]);

            // Start transaction
            DB::beginTransaction();

            // Create User
            $newUser = User::create([
                'fname' => $validated['fname'],
                'lname' => $validated['lname'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role_id' => $validated['role_id'],
                'store_id' => $storeId, // Using the authenticated user's store_id
                'branch_id' => $request->branch_id ?? $user->branch_id,
                'is_active' => $request->is_active ?? 'active',
                'registered_by' => $current_user_id // Using the authenticated user's ID
            ]);

            // Generate Employee ID
            $employeeNumber = (new Employee())->generateEmployeeNumber($validated['role_id']);

            // Create Employee
            $employee = Employee::create([
                'user_id' => $newUser->id,
                'store_id' => $storeId, // Using the authenticated user's store_id
                'branch_id' => $validated['branch_id'],
                'employee_number' => $employeeNumber,
                'date_of_birth' => $validated['date_of_birth'],
                'gender' => $validated['gender'],
                'hire_date' => $validated['hire_date'],
                'department' => $validated['department'],
                'employment_type' => $validated['employment_type'],
                'salary' => $validated['salary'],
                'status' => $validated['status']
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Employee created successfully',
                'data' => [
                    'employee' => $employee->load('user')
                ]
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create employee: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Update employee
     */
    public function update(Request $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);

            $validated = $request->validate([
                // User fields
                'fname' => 'sometimes|string|max:100',
                'lname' => 'sometimes|string|max:100',
                'email' => 'sometimes|email|unique:users,email,' . $employee->user_id,
                'is_active' => 'sometimes|string|max:255',

                // Employee fields
                'employee_number' => 'sometimes|string|unique:employees,employee_number,' . $id,
                'date_of_birth' => 'sometimes|date',
                'gender' => 'sometimes|in:male,female,other',
                'hire_date' => 'sometimes|date',
                'department' => 'sometimes|string|max:255',
                'employment_type' => 'sometimes|in:full_time,part_time,contract,intern',
                'salary' => 'sometimes|numeric|min:0',
                'status' => 'sometimes|in:active,on_leave,suspended,terminated',
                'termination_date' => 'nullable|date',
                'termination_reason' => 'nullable|string',
            ]);

            DB::beginTransaction();

            // Update User
            $user = $employee->user;
            if ($request->has('fname')) $user->fname = $validated['fname'];
            if ($request->has('lname')) $user->lname = $validated['lname'];
            if ($request->has('email')) $user->email = $validated['email'];
            if ($request->has('is_active')) $user->is_active = $validated['is_active'];
            $user->save();

            // Update Employee
            $employee->update($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Employee updated successfully',
                'data' => $employee->load('user')
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Show single employee
     */
    public function show($id)
    {
        try {
            $employee = Employee::with('user')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $employee
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete employee (soft delete)
     */
    public function destroy($id)
    {
        try {
            $employee = Employee::findOrFail($id);
            $user = $employee->user;

            DB::beginTransaction();

            // Soft delete user
            $user->deleted_at = now();
            $user->deleted_by = auth()->id();
            $user->save();

            // Soft delete employee
            $employee->deleted_at = now();
            $employee->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Employee deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get concise employee details (optimized version)
     */
    public function getEmployeeDetails(Request $request, $id)
    {
        try {
            $user = Auth::user();

            // Quick store access check
            if (!$user->store_id) {
                return $this->errorResponse('User is not associated with any store', 403);
            }

            // Cache key based on employee and year
            $cacheKey = "employee_details_{$id}_{$request->year}_" . now()->format('Y-m-d');

            // Try to get from cache first (5 minutes TTL)
            $data = Cache::remember($cacheKey, 300, function () use ($id, $request, $user) {
                return $this->buildEmployeeData($id, $request, $user);
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'cached' => true
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch employee details', 500, $e);
        }
    }

    /**
     * Build employee data with optimized queries
     */
    private function buildEmployeeData($id, $request, $user)
    {
        $currentYear = $request->year ?? Carbon::now()->year;
        $currentMonth = $request->month ?? Carbon::now()->month;

        // 1. Get employee with ONLY necessary relationships (eager load wisely)
        $employee = Employee::select([
            'id',
            'user_id',
            'store_id',
            'branch_id',
            'role_id',
            'employee_number',
            'fname',
            'lname',
            'date_of_birth',
            'gender',
            'hire_date',
            'department',
            'employment_type',
            'status',
            'salary',
            'bank_account',
            'tax_id',
            'phone',
            'address',
            'city',
            'province',
            'emergency_contact_name',
            'emergency_contact_phone',
            'emergency_contact_relationship',
            'id_document_path',
            'contract_path',
        ])
            ->with([
                'branch:id,name',
                'role:id,name'
            ])
            ->where('store_id', $user->store_id)
            ->findOrFail($id);

        // 2. Get all required data in parallel using separate queries
        $queries = [
            'leaveBalances' => $this->getLeaveBalancesOptimized($employee->id, $currentYear),
            'recentLeaves' => $this->getRecentLeavesOptimized($employee->id),
            'upcomingLeaves' => $this->getUpcomingLeavesOptimized($employee->id),
            'attendanceStats' => $this->getAttendanceStatsOptimized($employee->id, $currentMonth, $currentYear),
            'recentAttendance' => $this->getRecentAttendanceOptimized($employee->id),
            'payrollSummary' => $this->getPayrollSummaryOptimized($employee->id, $currentYear),
            'recentPayslips' => $this->getRecentPayslipsOptimized($employee->id),
            'deductions' => $this->getDeductionsOptimized($employee->id)
        ];

        // 3. Combine results
        return [
            'basic_info' => $this->formatBasicInfo($employee),
            'employment_details' => $this->formatEmploymentDetails($employee),
            'contact_info' => $this->formatContactInfo($employee),
            'leave_info' => [
                'balances' => $queries['leaveBalances'],
                'recent_requests' => $queries['recentLeaves'],
                'upcoming_leaves' => $queries['upcomingLeaves'],
                'summary' => $this->calculateLeaveSummary($queries['leaveBalances'])
            ],
            'attendance' => [
                'monthly_stats' => $queries['attendanceStats'],
                'recent_records' => $queries['recentAttendance']
            ],
            'payroll' => [
                'yearly_summary' => $queries['payrollSummary'],
                'recent_payslips' => $queries['recentPayslips']
            ],
            'deductions' => $queries['deductions'],
            'quick_stats' => $this->calculateQuickStats($employee, $queries)
        ];
    }

    /**
     * Optimized leave balances query
     */
    private function getLeaveBalancesOptimized($employeeId, $year)
    {
        return LeaveBalance::select('leave_type', 'yearly_quota', 'used_days', 'pending_days', 'remaining_days', 'carried_over')
            ->where('employee_id', $employeeId)
            ->where('year', $year)
            ->get()
            ->keyBy('leave_type')
            ->map(function ($item) {
                return [
                    'quota' => (float) $item->yearly_quota,
                    'used' => (float) $item->used_days,
                    'pending' => (float) $item->pending_days,
                    'remaining' => (float) $item->remaining_days,
                    'carried_over' => (float) $item->carried_over
                ];
            });
    }

    /**
     * Optimized recent leaves query
     */
    private function getRecentLeavesOptimized($employeeId)
    {
        return Leave::select('id', 'leave_type', 'start_date', 'end_date', 'total_days', 'status')
            ->where('employee_id', $employeeId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($leave) => [
                'id' => $leave->id,
                'type' => $leave->leave_type,
                'period' => Carbon::parse($leave->start_date)->format('M d') . ' - ' .
                    Carbon::parse($leave->end_date)->format('M d, Y'),
                'days' => $leave->total_days,
                'status' => $leave->status,
                'badge' => $this->getStatusBadge($leave->status)
            ]);
    }

    /**
     * Optimized upcoming leaves query
     */
    private function getUpcomingLeavesOptimized($employeeId)
    {
        return Leave::select('id', 'leave_type', 'start_date', 'end_date', 'total_days')
            ->where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where('start_date', '>=', now())
            ->orderBy('start_date')
            ->limit(3)
            ->get()
            ->map(fn($leave) => [
                'type' => $leave->leave_type,
                'start' => Carbon::parse($leave->start_date)->format('M d'),
                'end' => Carbon::parse($leave->end_date)->format('M d, Y'),
                'days' => $leave->total_days
            ]);
    }

    /**
     * Optimized attendance stats using raw DB for speed
     */
    private function getAttendanceStatsOptimized($employeeId, $month, $year)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $stats = Attendance::select(
            DB::raw("COUNT(CASE WHEN status = 'present' THEN 1 END) as present_count"),
            DB::raw("COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_count"),
            DB::raw("COUNT(CASE WHEN status = 'late' THEN 1 END) as late_count"),
            DB::raw("COUNT(CASE WHEN status = 'on_leave' THEN 1 END) as leave_count"),
            DB::raw("SUM(overtime_minutes) as total_overtime"),
            DB::raw("SUM(total_worked_minutes) as total_worked"),
            DB::raw("SUM(late_minutes) as total_late")
        )
            ->where('employee_id', $employeeId)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->first();

        $workingDays = $this->getWorkingDaysCount($startDate, $endDate);
        $presentDays = $stats->present_count ?? 0;

        return [
            'present' => $presentDays,
            'absent' => $stats->absent_count ?? 0,
            'late' => $stats->late_count ?? 0,
            'on_leave' => $stats->leave_count ?? 0,
            'overtime_hours' => round(($stats->total_overtime ?? 0) / 60, 1),
            'total_hours' => round(($stats->total_worked ?? 0) / 60, 1),
            'late_minutes' => $stats->total_late ?? 0,
            'attendance_rate' => $workingDays > 0 ? round(($presentDays / $workingDays) * 100, 1) : 0
        ];
    }

    /**
     * Optimized recent attendance
     */
    private function getRecentAttendanceOptimized($employeeId)
    {
        return Attendance::select('attendance_date', 'clock_in', 'clock_out', 'status', 'total_worked_minutes')
            ->where('employee_id', $employeeId)
            ->orderBy('attendance_date', 'desc')
            ->limit(7)
            ->get()
            ->map(fn($att) => [
                'date' => Carbon::parse($att->attendance_date)->format('D, M d'),
                'status' => $att->status,
                'hours' => $att->total_worked_minutes ? round($att->total_worked_minutes / 60, 1) . 'h' : '-',
                'badge' => $this->getStatusBadge($att->status)
            ]);
    }

    /**
     * Optimized payroll summary using aggregation
     */
    private function getPayrollSummaryOptimized($employeeId, $year)
    {
        $summary = Payroll::select(
            DB::raw("SUM(net_salary) as total_net"),
            DB::raw("SUM(base_salary + overtime_amount + bonuses_total + allowances_total) as total_gross"),
            DB::raw("SUM(overtime_hours) as total_overtime_hours"),
            DB::raw("COUNT(*) as payroll_count"),
            DB::raw("AVG(net_salary) as average_net")
        )
            ->where('employee_id', $employeeId)
            ->whereYear('created_at', $year)
            ->whereIn('status', ['approved', 'paid'])
            ->first();

        return [
            'year' => $year,
            'total_net' => round($summary->total_net ?? 0, 2),
            'total_gross' => round($summary->total_gross ?? 0, 2),
            'average_monthly' => round(($summary->average_net ?? 0), 2),
            'payroll_count' => $summary->payroll_count ?? 0,
            'overtime_hours' => round($summary->total_overtime_hours ?? 0, 1)
        ];
    }

    /**
     * Optimized recent payslips
     */
    private function getRecentPayslipsOptimized($employeeId)
    {
        return Payroll::select('id', 'net_salary', 'status', 'payment_date')
            ->with('payPeriod:id,name,start_date,end_date')
            ->where('employee_id', $employeeId)
            ->whereIn('status', ['approved', 'paid'])
            ->orderBy('payment_date', 'desc')
            ->limit(3)
            ->get()
            ->map(fn($payroll) => [
                'id' => $payroll->id,
                'period' => $payroll->payPeriod->name ?? 'N/A',
                'date' => $payroll->payment_date ? Carbon::parse($payroll->payment_date)->format('M d, Y') : null,
                'net_pay' => round($payroll->net_salary, 2),
                'net_pay_formatted' => '₱' . number_format($payroll->net_salary, 2)
            ]);
    }

    /**
     * Optimized deductions query
     */
    private function getDeductionsOptimized($employeeId)
    {
        $deductions = DB::table('employee_deductions as ed')
            ->join('deduction_types as dt', 'ed.deduction_type_id', '=', 'dt.id')
            ->select('dt.name', 'dt.code', 'ed.amount')
            ->where('ed.employee_id', $employeeId)
            ->where('ed.is_active', true)
            ->where(function ($q) {
                $q->whereNull('ed.end_date')
                    ->orWhere('ed.end_date', '>=', now());
            })
            ->get();

        $total = $deductions->sum('amount');

        return [
            'total_monthly' => round($total, 2),
            'total_yearly' => round($total * 12, 2),
            'items' => $deductions->map(fn($d) => [
                'name' => $d->name,
                'code' => $d->code,
                'amount' => round($d->amount, 2),
                'formatted' => '₱' . number_format($d->amount, 2)
            ])
        ];
    }

    /**
     * Format basic info
     */
    private function formatBasicInfo($employee)
    {
        return [
            'id' => $employee->id,
            'employee_number' => $employee->employee_number,
            'name' => $employee->fname . ' ' . $employee->lname,
            'first_name' => $employee->fname,
            'last_name' => $employee->lname,
            'birthday' => $employee->date_of_birth ? Carbon::parse($employee->date_of_birth)->format('M d, Y') : null,
            'age' => $employee->date_of_birth ? Carbon::parse($employee->date_of_birth)->age : null,
            'gender' => $employee->gender,
        ];
    }

    /**
     * Format employment details
     */
    private function formatEmploymentDetails($employee)
    {
        $yearsEmployed = $employee->hire_date ? Carbon::parse($employee->hire_date)->diffInYears(now()) : 0;

        return [
            'branch' => $employee->branch->name ?? 'N/A',
            'role' => $employee->role->name ?? 'N/A',
            'department' => $employee->department,
            'type' => $employee->employment_type,
            'status' => $employee->status,
            'hire_date' => $employee->hire_date ? Carbon::parse($employee->hire_date)->format('M d, Y') : null,
            'tenure' => $yearsEmployed . ' year(s)',
            'monthly_salary' => round($employee->salary, 2),
            'monthly_salary_formatted' => '₱' . number_format($employee->salary, 2)
        ];
    }

    /**
     * Format contact info
     */
    private function formatContactInfo($employee)
    {
        return [
            'phone' => $employee->phone,
            'address' => trim($employee->address . ', ' . $employee->city . ', ' . $employee->province),
            'emergency_contact' => [
                'name' => $employee->emergency_contact_name,
                'phone' => $employee->emergency_contact_phone,
                'relationship' => $employee->emergency_contact_relationship
            ]
        ];
    }

    /**
     * Calculate leave summary
     */
    private function calculateLeaveSummary($balances)
    {
        return [
            'total_used' => $balances->sum('used'),
            'total_remaining' => $balances->sum('remaining'),
            'total_pending' => $balances->sum('pending')
        ];
    }

    /**
     * Calculate quick stats
     */
    private function calculateQuickStats($employee, $queries)
    {
        return [
            'attendance_rate' => $queries['attendanceStats']['attendance_rate'] ?? 0,
            'leave_balance' => $queries['leaveBalances']->sum('remaining'),
            'monthly_salary' => round($employee->salary, 2),
            'ytd_earnings' => $queries['payrollSummary']['total_net'] ?? 0
        ];
    }

    /**
     * Helper: Get working days count
     */
    private function getWorkingDaysCount($start, $end)
    {
        $days = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            if (!$current->isWeekend()) {
                $days++;
            }
            $current->addDay();
        }

        return $days;
    }

    /**
     * Helper: Get status badge
     */
    private function getStatusBadge($status)
    {
        $badges = [
            'present' => 'success',
            'absent' => 'danger',
            'late' => 'warning',
            'on_leave' => 'info',
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger'
        ];

        return $badges[strtolower($status)] ?? 'secondary';
    }

    /**
     * Helper: Error response
     */
    private function errorResponse($message, $code = 500, $e = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => config('app.debug') && $e ? $e->getMessage() : null
        ], $code);
    }
}
