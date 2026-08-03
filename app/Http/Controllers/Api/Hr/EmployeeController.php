<?php

namespace App\Http\Controllers\Api\Hr;

use App\Mail\EmployeeShiftChangedMail;
use App\Http\Controllers\Controller;
use App\Models\Core\User;
use App\Models\Hr\Attendance;
use App\Models\Hr\EmployeeDeduction;
use App\Models\Hr\EmployeeGovernmentId;
use App\Models\Hr\EmployeeWeeklySchedule;
use App\Models\Hr\DeductionType;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeCreditCard;
use App\Models\Hr\Leave;
use App\Models\Hr\LeaveBalance;
use App\Models\Hr\Payroll;
use App\Models\Hr\Shift;
use App\Models\Hr\ShiftAssignment;
use App\Models\Hr\ShiftSchedule;
use App\Services\Store\DocumentAutoValidationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use App\Mail\ApplicantEmployeeCredentialsMail;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly DocumentAutoValidationService $documentAutoValidationService
    ) {
    }

    /**
     * List employees
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user || !$user->store_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not assigned to a store.'
                ], 400);
            }

            $storeId = $user->store_id;

            // Get total count for the user's store
            $totalEmployees = Employee::where('store_id', $storeId)->count();

            $activeEmployees = Employee::where('store_id', $storeId)
                ->where('status', 'active')
                ->count();

            $inactiveEmployees = Employee::where('store_id', $storeId)
                ->where('status', 'inactive')
                ->count();

            $onLeaveEmployees = Employee::where('store_id', $storeId)
                ->where('status', 'on_leave')
                ->count();

            // Get department count
            $departmentCount = Employee::where('store_id', $storeId)
                ->distinct('department')
                ->count('department');

            // Always scope employee listing to the authenticated user's store.
            $query = Employee::with('user')->where('store_id', $storeId);

            if ($request->filled('branch_id')) {
                $query->where('branch_id', (int) $request->input('branch_id'));
            }

            // Add filters
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
     * Get the authenticated user's employee profile
     */
    public function me()
    {
        $user = Auth::user();
        $employee = Employee::with(['user', 'user.branch'])
            ->where('user_id', $user->id)
            ->where('store_id', $user->store_id)
            ->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee record not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $employee
        ]);
    }

    /**
     * Preview OCR for a government ID before saving.
     */
    public function previewGovernmentId(Request $request)
    {
        $validated = $request->validate([
            'id_document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        try {
            $uploadedFile = $validated['id_document'];
            $path = $uploadedFile->store('tmp/gov-id-previews', 'public');
            $text = $this->documentAutoValidationService->extractTextFromDocument($path);
            $likelyIdNumber = $this->documentAutoValidationService->extractLikelyIdNumber($text);

            return response()->json([
                'success' => true,
                'data' => [
                    'extracted_text' => $text,
                    'likely_id_number' => $likelyIdNumber,
                    'matched' => (bool) $likelyIdNumber,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to preview government ID: ' . $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Store a newly created employee
     */
    public function store(Request $request)
    {
        return $this->storeInternal($request, false);
    }

    /**
     * Store a newly created employee without OTP (manual invite).
     */
    public function storeInvite(Request $request)
    {
        return $this->storeInternal($request, true);
    }

    private function storeInternal(Request $request, bool $markEmailVerified)
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
                'is_active' => 'nullable|boolean',
                'fname' => 'required|string|max:100',
                'lname' => 'required|string|max:100',
                'email' => 'required|email|unique:users,email',
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

            $temporaryPassword = Str::random(12);

            // Create User
            $newUser = User::create([
                'fname' => $validated['fname'],
                'lname' => $validated['lname'],
                'email' => $validated['email'],
                'password' => Hash::make($temporaryPassword),
                'role_id' => $validated['role_id'],
                'store_id' => $storeId, // Using the authenticated user's store_id
                'branch_id' => $request->branch_id ?? $user->branch_id,
                'is_active' => $request->boolean('is_active', true),
                'registered_by' => $current_user_id, // Using the authenticated user's ID
                'email_verified_at' => $markEmailVerified ? now() : null,
            ]);

            // Generate Employee ID
            $employeeNumber = (new Employee())->generateEmployeeNumber($validated['role_id']);

            // Create Employee
            $employee = Employee::create([
                'user_id' => $newUser->id,
                'store_id' => $storeId, // Using the authenticated user's store_id
                'branch_id' => $validated['branch_id'] ?? $user->branch_id,
                'role_id' => $validated['role_id'],
                'employee_number' => $employeeNumber,
                'fname' => $validated['fname'],
                'lname' => $validated['lname'],
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'hire_date' => $validated['hire_date'],
                'department' => $validated['department'],
                'employment_type' => $validated['employment_type'],
                'salary' => $validated['salary'],
                'status' => $validated['status']
            ]);

            DB::commit();

            try {
                Mail::to($validated['email'])->send(new ApplicantEmployeeCredentialsMail($employee->load('user'), $temporaryPassword));
            } catch (\Exception $e) {
                \Log::warning('Failed to send employee credentials email', [
                    'employee_id' => $employee->id,
                    'email' => $validated['email'],
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Employee created successfully. Credentials were emailed.',
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
            $authUser = Auth::user();
            $employee = Employee::findOrFail($id);
            if ($authUser?->store_id && (int) $employee->store_id !== (int) $authUser->store_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found for your store'
                ], 404);
            }

            $validated = $request->validate([
                // User fields
                'fname' => 'sometimes|string|max:100',
                'lname' => 'sometimes|string|max:100',
                'email' => 'sometimes|email|unique:users,email,' . $employee->user_id,
                'is_active' => 'sometimes|string|max:255',
                'role_id' => 'sometimes|exists:roles,id',
                'branch_id' => 'sometimes|nullable|exists:branches,id',

                // Employee fields
                'employee_number' => 'sometimes|string|unique:employees,employee_number,' . $id,
                'phone' => 'sometimes|nullable|string|max:50',
                'address' => 'sometimes|nullable|string|max:255',
                'province' => 'sometimes|nullable|string|max:255',
                'city' => 'sometimes|nullable|string|max:255',
                'barangay' => 'sometimes|nullable|string|max:255',
                'date_of_birth' => 'sometimes|date',
                'gender' => 'sometimes|in:male,female,other',
                'hire_date' => 'sometimes|date',
                'department' => 'sometimes|string|max:255',
                'employment_type' => 'sometimes|in:full_time,part_time,contract,intern',
                'pay_type' => 'sometimes|in:monthly,hourly,hybrid',
                'salary' => 'sometimes|numeric|min:0',
                'deduction_type_id' => 'sometimes|nullable|exists:deduction_types,id',
                'government_id_number' => 'sometimes|nullable|string|max:100',
                'id_document' => 'sometimes|file|mimes:jpg,jpeg,png,pdf|max:5120',
                'status' => 'sometimes|in:active,on_leave,suspended,terminated',
                'termination_date' => 'nullable|date',
                'termination_reason' => 'nullable|string',
                'shift_id' => 'sometimes|nullable|exists:shifts,id',
                'shift_effective_date' => 'sometimes|date',
                'shift_change_reason' => 'required_with:shift_id|string|max:1000',
            ]);

            DB::beginTransaction();

            // Update User
            $user = $employee->user;
            if ($request->has('fname')) $user->fname = $validated['fname'];
            if ($request->has('lname')) $user->lname = $validated['lname'];
            if ($request->has('email')) $user->email = $validated['email'];
            if ($request->has('is_active')) $user->is_active = $validated['is_active'];
            if ($request->has('role_id')) $user->role_id = $validated['role_id'];
            if ($request->has('branch_id')) $user->branch_id = $validated['branch_id'];
            $user->save();

            // Update Employee
            $employeeData = collect($validated)->except([
                'shift_id',
                'shift_effective_date',
                'shift_change_reason',
                'id_document',
                'deduction_type_id',
                'government_id_type',
                'government_id_status',
            ])->toArray();
            if (!empty($employeeData)) {
                $employee->update($employeeData);
            }

            if ($request->hasFile('id_document')) {
                $storedPath = $request->file('id_document')->store("hr/employee-ids/{$employee->id}", 'public');
                $extractedText = $this->documentAutoValidationService->extractTextFromDocument($storedPath);
                $likelyIdNumber = $this->documentAutoValidationService->extractLikelyIdNumber($extractedText);
                $idNumber = $validated['government_id_number'] ?? null;
                if (!$idNumber && $likelyIdNumber) {
                    $idNumber = $likelyIdNumber;
                }

                $deductionType = DeductionType::find($validated['deduction_type_id'] ?? null);
                $isHrVerifiedUpload = $authUser?->hasRole('hr_manager') || $authUser?->hasRole('store_admin') || $authUser?->hasRole('super_admin');

                $governmentId = EmployeeGovernmentId::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'deduction_type_id' => $validated['deduction_type_id'] ?? null,
                    ],
                    [
                        'id_type' => $deductionType?->name ?? 'Government ID',
                        'id_number' => $idNumber,
                        'id_file_path' => $storedPath,
                        'status' => $isHrVerifiedUpload ? 'verified' : 'pending',
                        'verified_at' => $isHrVerifiedUpload ? now() : null,
                    ]
                );

                $employee->save();
            }

            $shiftChanged = false;
            if (array_key_exists('shift_id', $validated) && !empty($validated['shift_id'])) {
                $targetShift = Shift::where('id', $validated['shift_id'])
                    ->where('store_id', $employee->store_id)
                    ->first();

                if (!$targetShift) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Shift does not belong to employee store'
                    ], 422);
                }

                $effectiveDate = isset($validated['shift_effective_date'])
                    ? Carbon::parse($validated['shift_effective_date'])->startOfDay()
                    : now()->startOfDay();

                $currentAssignment = ShiftAssignment::query()
                    ->where('employee_id', $employee->id)
                    ->where('start_date', '<=', $effectiveDate->toDateString())
                    ->where(function ($query) use ($effectiveDate) {
                        $query->whereNull('end_date')
                            ->orWhere('end_date', '>=', $effectiveDate->toDateString());
                    })
                    ->orderByDesc('start_date')
                    ->first();

                $currentShiftId = (int) ($currentAssignment?->shift_id ?? 0);
                if ($currentShiftId !== (int) $targetShift->id) {
                    $shiftChanged = true;
                    if ($currentAssignment) {
                        $newEnd = $effectiveDate->copy()->subDay()->toDateString();
                        if (!$currentAssignment->end_date || $currentAssignment->end_date->toDateString() >= $effectiveDate->toDateString()) {
                            $currentAssignment->update([
                                'end_date' => $newEnd,
                                'updated_by' => $authUser?->id,
                                'notes' => trim(($currentAssignment->notes ? $currentAssignment->notes . "\n" : '') . 'Closed due to shift change'),
                            ]);
                        }
                    }

                    $newAssignment = ShiftAssignment::create([
                        'employee_id' => $employee->id,
                        'shift_id' => $targetShift->id,
                        'template_id' => null,
                        'start_date' => $effectiveDate->toDateString(),
                        'end_date' => null,
                        'assignment_type' => 'permanent',
                        'notes' => 'Shift changed: ' . $validated['shift_change_reason'],
                        'created_by' => $authUser?->id,
                        'updated_by' => $authUser?->id,
                    ]);

                    ShiftSchedule::where('employee_id', $employee->id)
                        ->where('schedule_date', '>=', $effectiveDate->toDateString())
                        ->update([
                            'shift_id' => $targetShift->id,
                            'assignment_id' => $newAssignment->id,
                        ]);

                    try {
                        if ($user?->email) {
                            Mail::to($user->email)->send(new EmployeeShiftChangedMail(
                                $employee->fresh(['user']),
                                $targetShift,
                                $validated['shift_change_reason'],
                                $effectiveDate->toDateString()
                            ));
                        }
                    } catch (\Throwable $e) {
                        \Log::warning('Failed to send shift change email', [
                            'employee_id' => $employee->id,
                            'email' => $user?->email,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            DB::commit();

            // Clear cached employee details for today (with and without year filter)
            $today = now()->format('Y-m-d');
            $year = now()->year;
            Cache::forget("employee_details_{$id}_{$year}_{$today}");
            Cache::forget("employee_details_{$id}__{$today}");

            return response()->json([
                'success' => true,
                'message' => $shiftChanged ? 'Employee and shift updated successfully' : 'Employee updated successfully',
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

    public function verifyGovernmentId(Request $request, int $employeeId, int $governmentIdId)
    {
        try {
            $authUser = Auth::user();
            if (!$authUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            if (!$authUser->hasAnyRole(['hr_manager', 'store_admin', 'super_admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to verify government IDs.',
                ], 403);
            }

            $employee = Employee::where('id', $employeeId)
                ->where('store_id', $authUser->store_id)
                ->firstOrFail();

            $governmentId = EmployeeGovernmentId::where('id', $governmentIdId)
                ->where('employee_id', $employee->id)
                ->firstOrFail();

            $governmentId->update([
                'status' => 'verified',
                'verified_at' => now(),
            ]);

            $today = now()->format('Y-m-d');
            $year = now()->year;
            Cache::forget("employee_details_{$employeeId}_{$year}_{$today}");
            Cache::forget("employee_details_{$employeeId}__{$today}");

            return response()->json([
                'success' => true,
                'message' => 'Government ID verified successfully.',
                'data' => [
                    'id' => $governmentId->id,
                    'status' => $governmentId->status,
                    'verified_at' => optional($governmentId->verified_at)->toDateTimeString(),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Government ID record not found.',
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify government ID.',
                'error' => $e->getMessage(),
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

            $year = $request->year ?? Carbon::now()->year;
            // Cache key based on employee and resolved year
            $cacheKey = "employee_details_{$id}_{$year}_" . now()->format('Y-m-d');

            // Try to get from cache first (5 minutes TTL)
            $data = Cache::remember($cacheKey, 300, function () use ($id, $request, $user) {
                return $this->buildEmployeeData($id, $request, $user);
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'cached' => true
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Employee not found for this store', 404);
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
            'contract_path',
        ])
            ->with([
                'branch:id,name',
                'role:id,name'
            ])
            ->where('store_id', $user->store_id)
            ->where('id', $id)
            ->first();

        if (!$employee) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
        }

        // 2. Get all required data in parallel using separate queries
        $queries = [
            'leaveBalances' => $this->getLeaveBalancesOptimized($employee->id, $currentYear),
            'recentLeaves' => $this->getRecentLeavesOptimized($employee->id),
            'upcomingLeaves' => $this->getUpcomingLeavesOptimized($employee->id),
            'attendanceStats' => $this->getAttendanceStatsOptimized($employee->id, $currentMonth, $currentYear),
            'recentAttendance' => $this->getRecentAttendanceOptimized($employee->id),
            'payrollSummary' => $this->getPayrollSummaryOptimized($employee->id, $currentYear),
            'recentPayslips' => $this->getRecentPayslipsOptimized($employee->id),
            'deductions' => $this->getDeductionsOptimized($employee->id),
            'governmentIds' => $this->getGovernmentIdsOptimized($employee->id),
            'creditCard' => $this->getLatestCreditCardOptimized($employee->id),
            'currentShift' => $this->getCurrentShiftAssignmentOptimized($employee->id),
            'weeklySchedule' => $this->getWeeklyScheduleOptimized($employee->id),
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
            'credit_card' => $queries['creditCard'],
            'current_shift' => $queries['currentShift'],
            'weekly_schedule' => $queries['weeklySchedule'],
            'deductions' => $queries['deductions'],
            'governmentIds' => $queries['governmentIds'],
            'quick_stats' => $this->calculateQuickStats($employee, $queries)
        ];
    }

    private function getCurrentShiftAssignmentOptimized($employeeId)
    {
        $today = now()->toDateString();
        $assignment = ShiftAssignment::query()
            ->with(['shift:id,name,start_time,end_time,week_days', 'template:id,name'])
            ->where('employee_id', $employeeId)
            ->where('start_date', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $today);
            })
            ->orderByDesc('start_date')
            ->first();

        if (!$assignment || !$assignment->shift) {
            return null;
        }

        $coversDays = $this->normalizeShiftWeekDays($assignment->shift->week_days);
        $startTime = $this->formatShiftTimeValue($assignment->shift->start_time);
        $endTime = $this->formatShiftTimeValue($assignment->shift->end_time);

        return [
            'assignment_id' => $assignment->id,
            'shift_id' => $assignment->shift_id,
            'shift_name' => $assignment->shift->name,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'time_range' => $startTime && $endTime ? "{$startTime} - {$endTime}" : null,
            'covers_days' => $coversDays,
            'covers_days_label' => $coversDays ? implode(', ', $coversDays) : null,
            'assignment_type' => $assignment->assignment_type,
            'start_date' => optional($assignment->start_date)->toDateString(),
            'end_date' => optional($assignment->end_date)->toDateString(),
            'template_name' => $assignment->template?->name,
        ];
    }

    private function normalizeShiftWeekDays($weekDays): array
    {
        if (is_string($weekDays)) {
            $decoded = json_decode($weekDays, true);
            $weekDays = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($weekDays)) {
            return [];
        }

        $labels = [
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday',
        ];

        return collect($weekDays)
            ->map(fn($day) => strtolower((string) $day))
            ->filter()
            ->map(fn($day) => $labels[$day] ?? ucfirst($day))
            ->values()
            ->all();
    }

    private function formatShiftTimeValue($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->format('h:i A');
        }

        try {
            return Carbon::parse((string) $value)->format('h:i A');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    private function getLatestCreditCardOptimized($employeeId)
    {
        $card = EmployeeCreditCard::select('card_number', 'card_type', 'expiration_month', 'expiration_year', 'security_code', 'status', 'assigned_at')
            ->where('employee_id', $employeeId)
            ->latest('assigned_at')
            ->latest('id')
            ->first();

        if (!$card) {
            return null;
        }

        return [
            'card_number' => $card->card_number,
            'masked_card_number' => $this->maskCardNumber($card->card_number),
            'card_type' => $card->card_type,
            'expiration_month' => $card->expiration_month,
            'expiration_year' => $card->expiration_year,
            'expiry_label' => $card->expiration_month && $card->expiration_year
                ? $card->expiration_month . '/' . $card->expiration_year
                : null,
            'security_code' => $card->security_code,
            'status' => $card->status,
            'assigned_at' => optional($card->assigned_at)->toISOString(),
        ];
    }

    private function getWeeklyScheduleOptimized($employeeId)
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $labels = [
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday',
        ];

        $records = EmployeeWeeklySchedule::query()
            ->with(['shift:id,name,start_time,end_time'])
            ->where('employee_id', $employeeId)
            ->orderBy('effective_from', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('day_of_week');

        return collect($days)->map(function ($day) use ($records, $labels) {
            $record = $records->get($day)?->first();
            return [
                'day_of_week' => $day,
                'day_label' => $labels[$day],
                'id' => $record?->id,
                'shift_id' => $record?->shift_id,
                'shift_name' => $record?->shift?->name,
                'start_time' => $record?->start_time ?: $record?->shift?->start_time,
                'end_time' => $record?->end_time ?: $record?->shift?->end_time,
                'is_off' => (bool) ($record?->is_off ?? true),
                'effective_from' => optional($record?->effective_from)->toDateString(),
                'effective_to' => optional($record?->effective_to)->toDateString(),
                'notes' => $record?->notes,
            ];
        })->values();
    }

    public function saveWeeklySchedule(Request $request, int $employeeId)
    {
        $user = Auth::user();
        if (!$user?->store_id) {
            return response()->json(['success' => false, 'message' => 'User is not associated with any store'], 403);
        }

        $employee = Employee::where('id', $employeeId)->where('store_id', $user->store_id)->firstOrFail();

        $validated = $request->validate([
            'schedules' => 'required|array|size:7',
            'schedules.*.day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'schedules.*.shift_id' => 'nullable|exists:shifts,id',
            'schedules.*.start_time' => 'nullable|string|max:20',
            'schedules.*.end_time' => 'nullable|string|max:20',
            'schedules.*.is_off' => 'boolean',
            'schedules.*.effective_from' => 'nullable|date',
            'schedules.*.effective_to' => 'nullable|date',
            'schedules.*.notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($employee, $validated, $user) {
            foreach ($validated['schedules'] as $schedule) {
                $startTime = $schedule['is_off'] ? null : $this->normalizeTimeValue($schedule['start_time'] ?? null);
                $endTime = $schedule['is_off'] ? null : $this->normalizeTimeValue($schedule['end_time'] ?? null);

                EmployeeWeeklySchedule::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'day_of_week' => $schedule['day_of_week'],
                    ],
                    [
                        'shift_id' => $schedule['is_off'] ? null : ($schedule['shift_id'] ?? null),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'is_off' => (bool) ($schedule['is_off'] ?? false),
                        'effective_from' => $schedule['effective_from'] ?? null,
                        'effective_to' => $schedule['effective_to'] ?? null,
                        'notes' => $schedule['notes'] ?? null,
                    ]
                );
            }
        });

        $today = now()->format('Y-m-d');
        $year = now()->year;
        Cache::forget("employee_details_{$employee->id}_{$year}_{$today}");
        Cache::forget("employee_details_{$employee->id}__{$today}");

        return response()->json([
            'success' => true,
            'message' => 'Weekly schedule saved successfully.',
            'data' => $this->getWeeklyScheduleOptimized($employee->id),
        ]);
    }

    private function normalizeTimeValue(?string $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $normalized = strtoupper($raw);
        $formats = ['g:i A', 'g:iA', 'h:i A', 'h:iA', 'H:i:s', 'H:i'];

        foreach ($formats as $format) {
            $parsed = \DateTime::createFromFormat($format, $normalized);
            if ($parsed instanceof \DateTime) {
                return $parsed->format('H:i:s');
            }
        }

        return null;
    }

    private function maskCardNumber(?string $value): string
    {
        $raw = preg_replace('/\s+/', '', (string) $value);
        if (!$raw) {
            return '-';
        }

        $tail = substr($raw, -4);
        return str_repeat('*', max(strlen($raw) - 4, 0)) . $tail;
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
        return Payroll::select('id', 'net_salary', 'deductions_total', 'tax_amount', 'status', 'payment_date')
            ->with(['payPeriod:id,name,start_date,end_date', 'items:id,payroll_id,type,name,amount'])
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
                'net_pay_formatted' => '₱' . number_format($payroll->net_salary, 2),
                'deductions_total' => round((float) ($payroll->deductions_total ?? 0), 2),
                'tax_amount' => round((float) ($payroll->tax_amount ?? 0), 2),
                'contribution_total' => round((float) ($payroll->deductions_total ?? 0) + (float) ($payroll->tax_amount ?? 0), 2),
                'items' => $payroll->items->whereIn('type', ['deduction', 'tax'])->values()->map(fn($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'type' => $item->type,
                    'amount' => round((float) $item->amount, 2),
                ]),
            ]);
    }

    /**
     * Optimized deductions query
     */
    private function getDeductionsOptimized($employeeId)
    {
        $payrolls = Payroll::query()
            ->select('id', 'employee_id', 'payment_date', 'status', 'pay_period_id')
            ->with([
                'payPeriod:id,name,start_date,end_date',
                'items:id,payroll_id,type,name,amount',
            ])
            ->where('employee_id', $employeeId)
            ->whereIn('status', ['approved', 'paid'])
            ->orderBy('payment_date', 'desc')
            ->get();

        $deductions = $payrolls->flatMap(function (Payroll $payroll) {
            $periodLabel = $payroll->payPeriod->name
                ?? ($payroll->payment_date ? Carbon::parse($payroll->payment_date)->format('M d, Y') : 'N/A');

            return $payroll->items
                ->whereIn('type', ['deduction', 'tax'])
                ->values()
                ->map(function ($item) use ($payroll, $periodLabel) {
                    return [
                        'id' => $item->id,
                        'payroll_id' => $payroll->id,
                        'period' => $periodLabel,
                        'deduction_type_id' => null,
                        'name' => $item->name,
                        'code' => $item->type,
                        'reference_number' => null,
                        'amount' => round((float) $item->amount, 2),
                        'formatted' => 'PHP ' . number_format((float) $item->amount, 2),
                    ];
                });
        });

        $total = $deductions->sum('amount');

        return [
            'total_monthly' => round($total, 2),
            'total_yearly' => round($total * 12, 2),
            'items' => $deductions->values(),
            'payroll_count' => $payrolls->count(),
        ];
    }

    private function getGovernmentIdsOptimized($employeeId)
    {
        return EmployeeGovernmentId::query()
            ->with('deductionType:id,name,code')
            ->where('employee_id', $employeeId)
            ->orderByRaw("CASE status WHEN 'verified' THEN 1 WHEN 'pending' THEN 2 WHEN 'rejected' THEN 3 ELSE 4 END")
            ->orderBy('created_at')
            ->get()
            ->map(function (EmployeeGovernmentId $record) {
                return [
                    'id' => $record->id,
                    'deduction_type_id' => $record->deduction_type_id,
                    'label' => $record->deductionType?->name ?? $record->id_type,
                    'code' => $record->deductionType?->code ?? null,
                    'id_type' => $record->id_type,
                    'id_number' => $record->id_number,
                    'status' => $record->status,
                    'id_file_path' => $record->id_file_path,
                    'verified_at' => $record->verified_at,
                ];
            })
            ->values();
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
            'role_id' => $employee->role_id,
            'branch' => $employee->branch->name ?? 'N/A',
            'role' => $employee->role->name ?? 'N/A',
            'department' => $employee->department,
            'type' => $employee->employment_type,
            'status' => $employee->status,
            'hire_date' => $employee->hire_date ? Carbon::parse($employee->hire_date)->format('M d, Y') : null,
            'tenure' => $yearsEmployed . ' year(s)',
            'monthly_salary' => round($employee->salary, 2),
            'pay_type' => $employee->pay_type ?? 'monthly',
            'hourly_rate' => round((float) ($employee->hourly_rate ?? 0), 4),
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
