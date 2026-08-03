<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Mail\ApplicantEmployeeCredentialsMail;
use App\Mail\ApplicantInterviewScheduledMail;
use App\Models\ApplicationTimeline;
use App\Models\Core\User;
use App\Models\Hr\DeductionType;
use App\Models\Hr\Department;
use App\Models\Hr\Employee;
use App\Models\Hr\LeaveBalance;
use App\Models\Hr\EmployeeDeduction;
use App\Models\JobApplication;
use App\Models\Interview;
use App\Models\Store\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RecruitmentController extends Controller
{
    public function scheduleInterview(Request $request, JobApplication $application): JsonResponse
    {
        $interviewTypeMap = [
            'Screening' => 'Screening',
            'Technical/Skills Test' => 'Technical/Skills Test',
            'Final Interview' => 'Final Interview',
        ];

        $validated = $request->validate([
            'interview_date' => 'required|date|after:now',
            'interview_type' => 'required|string|max:100',
            'notes' => 'nullable|string',
            'interviewer_id' => 'nullable|exists:users,id',
        ]);

        $storeId = $request->user()?->store_id;
        if ($storeId && $application->jobPosting?->store_id && (int) $application->jobPosting->store_id !== (int) $storeId) {
            return response()->json([
                'success' => false,
                'message' => 'Interview scheduling is restricted to your store.',
            ], 403);
        }

        $settings = is_array($request->user()?->store?->settings) ? $request->user()->store->settings : [];
        $dailyLimit = (int) ($settings['hr_interview_daily_limit'] ?? 10);
        $interviewDate = \Carbon\Carbon::parse($validated['interview_date'])->toDateString();

        $dailyCount = Interview::query()
            ->whereDate('interview_date', $interviewDate)
            ->whereHas('application.jobPosting', fn($q) => $q->where('store_id', $storeId))
            ->count();

        if ($dailyCount >= $dailyLimit) {
            return response()->json([
                'success' => false,
                'message' => "Interview schedule is full for {$interviewDate}. Max {$dailyLimit} applicants per day.",
            ], 422);
        }

        $normalizedInterviewType = $interviewTypeMap[$validated['interview_type']] ?? null;

        if (!$normalizedInterviewType) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid interview type selected.',
                'errors' => [
                    'interview_type' => ['Please select a valid interview type.'],
                ],
            ], 422);
        }

        $interview = Interview::create([
            'application_id' => $application->id,
            'interviewer_id' => $validated['interviewer_id'] ?? $request->user()->id,
            'interview_date' => $validated['interview_date'],
            'interview_type' => $normalizedInterviewType,
            'notes' => trim(implode("\n", array_filter([
                isset($validated['interview_type']) && $validated['interview_type'] !== $normalizedInterviewType
                    ? 'Workflow label: ' . $validated['interview_type']
                    : null,
                $validated['notes'] ?? null,
            ]))),
        ]);

        $application->update(['status' => 'Interview']);

        ApplicationTimeline::create([
            'application_id' => $application->id,
            'status' => 'Interview',
            'changed_by' => $request->user()->id,
            'changed_at' => now(),
            'notes' => 'Interview scheduled',
        ]);

        Mail::to($application->email)->send(new ApplicantInterviewScheduledMail($interview->load('application.jobPosting')));

        return response()->json([
            'success' => true,
            'message' => 'Interview scheduled successfully.',
            'data' => $interview->load('interviewer'),
        ], 201);
    }

    public function hireApplicant(Request $request, JobApplication $application): JsonResponse
    {
        $hrUser = $request->user();
        $storeId = $hrUser->store_id;

            $validated = $request->validate([
                'branch_id' => 'required|exists:branches,id',
                'department_id' => 'required|exists:departments,id',
                'role_id' => 'required|exists:roles,id',
                'hire_date' => 'required|date',
                'pay_type' => 'nullable|in:monthly,hourly,hybrid',
                'employment_type' => 'required|in:full_time,part_time,contract,intern',
                'salary' => 'required|numeric|min:0',
                'position' => 'required|string|max:255',
                'phone' => 'nullable|string|max:50',
                'address' => 'nullable|string|max:255',
                'government_id_type' => 'nullable|string|max:50',
                'government_id_number' => 'nullable|string|max:100',
                'id_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            ]);

        if ($application->employee_id) {
            return response()->json([
                'success' => false,
                'message' => 'This applicant has already been converted into an employee.',
            ], 422);
        }

        $department = Department::where('id', $validated['department_id'])
            ->where('store_id', $storeId)
            ->firstOrFail();

        DB::beginTransaction();

        try {
            $temporaryPassword = Str::password(10);
            $portalUser = $application->user_id ? User::find($application->user_id) : null;

            if ($portalUser) {
                $portalUser->update([
                    'role_id' => $validated['role_id'],
                    'store_id' => $storeId,
                    'branch_id' => $validated['branch_id'],
                    'password' => Hash::make($temporaryPassword),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);
                $user = $portalUser;
            } else {
                $user = User::create([
                    'fname' => $application->first_name,
                    'lname' => $application->last_name,
                    'email' => $application->email,
                    'password' => Hash::make($temporaryPassword),
                    'role_id' => $validated['role_id'],
                    'store_id' => $storeId,
                    'branch_id' => $validated['branch_id'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);
            }

            $employee = Employee::create([
                'user_id' => $user->id,
                'store_id' => $storeId,
                'branch_id' => $validated['branch_id'],
                'employee_number' => Employee::generateEmployeeNumber($validated['role_id']),
                'role_id' => $validated['role_id'],
                'fname' => $application->first_name,
                'lname' => $application->last_name,
                'phone' => $validated['phone'] ?? $application->phone,
                'address' => $validated['address'] ?? null,
                'hire_date' => $validated['hire_date'],
                'department' => $department->name,
                'employment_type' => $validated['employment_type'],
                'salary' => $validated['salary'],
                'pay_type' => $validated['pay_type'] ?? 'monthly',
                'hourly_rate' => isset($validated['pay_type']) && $validated['pay_type'] === 'hourly' ? $validated['salary'] / 160 : null,
                'government_id_type' => $validated['government_id_type'] ?? null,
                'government_id_number' => $validated['government_id_number'] ?? null,
                'government_id_path' => null,
                'government_id_status' => 'pending',
                'status' => 'active',
            ]);

            if ($request->hasFile('id_document')) {
                $employee->government_id_path = $request->file('id_document')->store("hr/employee-ids/{$employee->id}", 'public');
                $employee->id_document_path = $employee->government_id_path;
                $employee->save();
            }

            $this->applyStoreDeductions($employee, $storeId, $hrUser->id);
            $this->applyDefaultLeaveBalances($employee, $storeId, $hrUser->id, (int) date('Y', strtotime($validated['hire_date'])));

            $application->update([
                'status' => 'Hired',
                'employee_id' => $employee->id,
                'user_id' => $user->id,
            ]);

            ApplicationTimeline::create([
                'application_id' => $application->id,
                'status' => 'Hired',
                'changed_by' => $hrUser->id,
                'changed_at' => now(),
                'notes' => 'Applicant hired and employee profile created',
            ]);

            DB::commit();

            Mail::to($user->email)->send(new ApplicantEmployeeCredentialsMail($employee->load('user'), $temporaryPassword));

            return response()->json([
                'success' => true,
                'message' => 'Applicant hired and employee account created successfully.',
                'data' => [
                    'employee' => $employee->load('user'),
                    'next_route' => '/hr/shifts/create?employee_id=' . $employee->id,
                ],
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function rejectApplicant(Request $request, JobApplication $application): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $application->update([
            'status' => 'Rejected',
        ]);

        ApplicationTimeline::create([
            'application_id' => $application->id,
            'status' => 'Rejected',
            'changed_by' => $request->user()->id,
            'changed_at' => now(),
            'notes' => trim(implode("\n", array_filter([
                'Reason: ' . $validated['reason'],
                $validated['notes'] ?? null,
            ]))),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Applicant rejected successfully.',
            'data' => $application->fresh(),
        ]);
    }

    private function applyStoreDeductions(Employee $employee, int $storeId, int $createdBy): void
    {
        $deductionTypes = DeductionType::query()
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('is_mandatory', true)
                    ->orWhere('category', 'government');
            })
            ->get();

        foreach ($deductionTypes as $deductionType) {
            EmployeeDeduction::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'deduction_type_id' => $deductionType->id,
                ],
                [
                    'effective_date' => now()->toDateString(),
                    'is_active' => true,
                    'created_by' => $createdBy,
                ]
            );
        }
    }

    private function applyDefaultLeaveBalances(Employee $employee, int $storeId, int $createdBy, int $year): void
    {
        $store = Store::find($storeId);
        $settings = is_array($store?->settings) ? $store->settings : [];
        $defaultLeaveSettings = [
            'vacation' => 15,
            'sick' => 10,
            'personal' => 5,
            'maternity' => 0,
            'paternity' => 0,
            'bereavement' => 0,
            'others' => 0,
        ];
        $leaveDefaults = array_merge($defaultLeaveSettings, $settings['hr_leave_defaults'] ?? []);

        foreach ($leaveDefaults as $leaveType => $quotaValue) {
            $quota = (float) ($quotaValue ?? 0);
            LeaveBalance::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'leave_type' => $leaveType,
                    'year' => $year,
                ],
                [
                    'store_id' => $storeId,
                    'created_by' => $createdBy,
                    'yearly_quota' => $quota,
                    'used_days' => 0,
                    'pending_days' => 0,
                    'remaining_days' => $quota,
                    'carried_over' => 0,
                    'expired_days' => 0,
                    'status' => 'active',
                ]
            );
        }
    }
}
