<?php

namespace App\Http\Controllers\Api\Hr;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Mail\ApplicantEmployeeCredentialsMail;
use App\Mail\ApplicantInterviewScheduledMail;
use App\Models\ApplicationTimeline;
use App\Models\Core\User;
use App\Models\Core\Role;
use App\Models\Hr\DeductionType;
use App\Models\Hr\Department;
use App\Models\Hr\Employee;
use App\Models\Hr\LeaveBalance;
use App\Models\Hr\EmployeeDeduction;
use App\Models\JobApplication;
use App\Models\Interview;
use App\Models\Store\Store;
use App\Models\Store\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Models\JobOnboardingPrerequisite;
use Illuminate\Support\Str;

class RecruitmentController extends Controller
{
    private function authorizeOnboarding(Request $request, JobApplication $application): void
    {
        abort_unless($request->user()?->store_id && (int) $application->jobPosting?->store_id === (int) $request->user()->store_id, 404);
    }

    public function onboardingRequirements(Request $request, JobApplication $application): JsonResponse
    {
        $this->authorizeOnboarding($request, $application);
        return response()->json(['data' => $application->load(['jobPosting', 'onboardingPrerequisite'])->onboardingPrerequisite]);
    }

    public function saveOnboardingRequirements(Request $request, JobApplication $application): JsonResponse
    {
        $this->authorizeOnboarding($request, $application);
        abort_if($application->employee_id, 422, 'This applicant is already hired.');
        $validated = $request->validate([
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120',
            'contract' => 'nullable|file|mimes:pdf|max:10240',
            'contract_signed' => 'nullable|boolean',
        ]);
        $record = JobOnboardingPrerequisite::firstOrCreate(['application_id' => $application->id]);
        $documents = $record->documents ?? [];
        $required = $application->jobPosting->hiring_documents ?? [];
        foreach ($request->file('documents', []) as $index => $file) {
            if (!array_key_exists($index, $required)) continue;
            $label = $required[$index];
            if (isset($documents[$label]['path'])) Storage::disk('private')->delete($documents[$label]['path']);
            $documents[$label] = ['name' => $file->getClientOriginalName(), 'path' => $file->store("hr/onboarding/{$application->id}", 'private')];
        }
        $record->documents = $documents;
        if ($request->hasFile('contract')) {
            if ($record->contract_path) Storage::disk('private')->delete($record->contract_path);
            $record->contract_path = $request->file('contract')->store("hr/onboarding/{$application->id}", 'private');
            $record->contract_signed_at = null;
            $record->contract_confirmed_by = null;
        }
        if ($request->boolean('contract_signed') && $record->contract_path) {
            $record->contract_signed_at = now();
            $record->contract_confirmed_by = $request->user()->id;
        }
        $record->save();
        if ($application->status !== 'Onboarding Pending') {
            $application->update(['status' => 'Onboarding Pending']);
            ApplicationTimeline::create(['application_id' => $application->id, 'status' => 'Onboarding Pending', 'changed_by' => $request->user()->id, 'changed_at' => now(), 'notes' => 'Hiring prerequisites are being collected']);
        }
        return response()->json(['data' => $record]);
    }

    public function downloadOnboardingRequirement(Request $request, JobApplication $application, string $kind)
    {
        $this->authorizeOnboarding($request, $application);
        $record = $application->onboardingPrerequisite;
        $label = $application->jobPosting->hiring_documents[(int) $kind] ?? null;
        $item = $kind === 'contract' ? ['path' => $record?->contract_path, 'name' => 'employment-contract.pdf'] : ($label ? ($record?->documents[$label] ?? null) : null);
        abort_unless($item && !empty($item['path']) && Storage::disk('private')->exists($item['path']), 404);
        return Storage::disk('private')->download($item['path'], $item['name']);
    }

    public function employmentContractPdf(Request $request, JobApplication $application)
    {
        $this->authorizeOnboarding($request, $application);

        $validated = $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'department_id' => 'required|integer|exists:departments,id',
            'role_id' => 'required|integer|exists:roles,id',
            'hire_date' => 'required|date',
            'contract_end_date' => 'nullable|required_if:employment_type,contract|date|after_or_equal:hire_date',
            'employment_type' => 'required|in:full_time,part_time,contract,intern',
            'pay_type' => 'required|in:monthly,hourly,hybrid',
            'salary' => 'required|numeric|min:0',
            'position' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'weekly_schedule' => 'required|array|size:7',
            'weekly_schedule.*.day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'weekly_schedule.*.is_off' => 'required|boolean',
            'weekly_schedule.*.start_time' => 'nullable|date_format:H:i',
            'weekly_schedule.*.end_time' => 'nullable|date_format:H:i',
        ]);

        foreach ($validated['weekly_schedule'] as $day) {
            if ($day['is_off']) {
                continue;
            }
            if (empty($day['start_time']) || empty($day['end_time']) || $day['start_time'] >= $day['end_time']) {
                throw ValidationException::withMessages([
                    'weekly_schedule' => ['Every working day in the contract schedule needs a valid start and end time.'],
                ]);
            }
        }

        $storeId = (int) $request->user()->store_id;
        $branch = Branch::query()->where('store_id', $storeId)->findOrFail($validated['branch_id']);
        $department = Department::query()->where('store_id', $storeId)->findOrFail($validated['department_id']);
        $role = Role::query()->where('id', $validated['role_id'])
            ->where(fn ($query) => $query->whereNull('store_id')->orWhere('store_id', $storeId))
            ->firstOrFail();
        $store = Store::query()->findOrFail($storeId);

        $pdf = \PDF::loadView('hr.employment-contract-pdf', [
            'application' => $application,
            'store' => $store,
            'branch' => $branch,
            'department' => $department,
            'role' => $role,
            'terms' => $validated,
            'printedAt' => now(),
        ])->setPaper('a4', 'portrait');

        $safeName = preg_replace('/[^A-Za-z0-9-]+/', '-', trim($application->first_name . '-' . $application->last_name, '-'));
        return $pdf->download('employment-contract-' . ($safeName ?: $application->id) . '.pdf');
    }

    public function scheduleInterview(Request $request, JobApplication $application): JsonResponse
    {
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

        $latestAttempt = $application->interviews()->orderByDesc('id')->first();
        if ($latestAttempt && !Carbon::parse($latestAttempt->interview_date)->isSameDay(now())) {
            return response()->json([
                'success' => false,
                'message' => 'Another interview can only be scheduled on the recorded interview date.',
            ], 422);
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

        $allowedStages = $application->jobPosting?->screeningStages()->pluck('stage_name')->filter()->values()->all() ?? [];
        if (!in_array($validated['interview_type'], $allowedStages, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid hiring stage selected for this job posting.',
                'errors' => [
                    'interview_type' => ['Please select a stage configured for this job posting.'],
                ],
            ], 422);
        }

        $interview = Interview::create([
            'application_id' => $application->id,
            'interviewer_id' => $validated['interviewer_id'] ?? $request->user()->id,
            'interview_date' => $validated['interview_date'],
            'interview_type' => $validated['interview_type'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $application->update(['status' => 'Interview']);

        ApplicationTimeline::create([
            'application_id' => $application->id,
            'status' => 'Interview',
            'changed_by' => $request->user()->id,
            'changed_at' => now(),
            'notes' => 'Interview scheduled',
        ]);

        Mail::to($application->email)->send(new ApplicantInterviewScheduledMail(
            $interview->load(['application.jobPosting.store', 'interviewer'])
        ));

        return response()->json([
            'success' => true,
            'message' => 'Interview scheduled successfully.',
            'data' => $interview->load('interviewer'),
        ], 201);
    }

    public function updateInterviewSchedule(Request $request, JobApplication $application, Interview $interview): JsonResponse
    {
        $validated = $request->validate([
            'interview_date' => 'required|date|after:now',
            'interview_type' => 'required|string|max:100',
            'notes' => 'nullable|string',
        ]);

        abort_unless((int) $interview->application_id === (int) $application->id, 404);
        $storeId = (int) ($request->user()?->store_id ?? 0);
        abort_unless($storeId > 0 && (int) $application->jobPosting?->store_id === $storeId, 404);
        abort_unless(!in_array(strtolower((string) $application->status), ['hired', 'rejected'], true), 422, 'This application can no longer be rescheduled.');

        $allowedStages = $application->jobPosting?->screeningStages()->pluck('stage_name')->filter()->values()->all() ?? [];
        if (!in_array($validated['interview_type'], $allowedStages, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid hiring stage selected for this job posting.',
                'errors' => ['interview_type' => ['Please select a stage configured for this job posting.']],
            ], 422);
        }

        $settings = is_array($request->user()?->store?->settings) ? $request->user()->store->settings : [];
        $dailyLimit = (int) ($settings['hr_interview_daily_limit'] ?? 10);
        $interviewDate = Carbon::parse($validated['interview_date'])->toDateString();
        $dailyCount = Interview::query()
            ->where('id', '!=', $interview->id)
            ->whereDate('interview_date', $interviewDate)
            ->whereHas('application.jobPosting', fn ($query) => $query->where('store_id', $storeId))
            ->count();

        if ($dailyCount >= $dailyLimit) {
            return response()->json([
                'success' => false,
                'message' => "Interview schedule is full for {$interviewDate}. Max {$dailyLimit} applicants per day.",
            ], 422);
        }

        DB::transaction(function () use ($interview, $application, $request, $validated): void {
            $interview->update([
                'interviewer_id' => $request->user()->id,
                'interview_date' => $validated['interview_date'],
                'interview_type' => $validated['interview_type'],
                'notes' => $validated['notes'] ?? null,
            ]);

            ApplicationTimeline::create([
                'application_id' => $application->id,
                'status' => 'Interview',
                'changed_by' => $request->user()->id,
                'changed_at' => now(),
                'notes' => 'Interview schedule updated to ' . Carbon::parse($validated['interview_date'])->format('M j, Y g:i A'),
            ]);
        });

        $interview->refresh()->load(['application.jobPosting.store', 'interviewer']);
        Mail::to($application->email)->send(new ApplicantInterviewScheduledMail($interview, updated: true));

        return response()->json([
            'success' => true,
            'message' => 'Interview schedule updated and applicant notified by email.',
            'data' => $interview,
        ]);
    }

    public function hireApplicant(Request $request, JobApplication $application): JsonResponse
    {
        $this->authorizeOnboarding($request, $application);
        $prerequisites = $application->onboardingPrerequisite;
        foreach ($application->jobPosting->hiring_documents ?? [] as $label) {
            $path = $prerequisites?->documents[$label]['path'] ?? null;
            if (!$path || !Storage::disk('private')->exists($path)) {
                throw ValidationException::withMessages(['documents' => "Upload the required {$label} before hiring."]);
            }
        }
        if ($application->jobPosting->requires_signed_contract && (!$prerequisites?->contract_signed_at || !$prerequisites->contract_path || !Storage::disk('private')->exists($prerequisites->contract_path))) {
            throw ValidationException::withMessages(['contract' => 'Upload the signed employment contract and confirm both signatures before hiring.']);
        }
        if ($this->decisionNotYetAvailable($application)) {
            return response()->json([
                'success' => false,
                'message' => 'Hiring is available on or after the latest interview date.',
            ], 422);
        }

        $hrUser = $request->user();
        $storeId = $hrUser->store_id;

            $validated = $request->validate([
                'branch_id' => 'required|exists:branches,id',
                'department_id' => 'required|exists:departments,id',
                'role_id' => 'required|exists:roles,id',
                'hire_date' => 'required|date',
                'contract_end_date' => 'nullable|required_if:employment_type,contract|date|after_or_equal:hire_date',
                'pay_type' => 'nullable|in:monthly,hourly,hybrid',
                'employment_type' => 'required|in:full_time,part_time,contract,intern',
                'salary' => 'required|numeric|min:0',
                'position' => 'required|string|max:255',
                'phone' => 'nullable|string|max:50',
                'address' => 'nullable|string|max:255',
                'province' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'barangay' => 'nullable|string|max:255',
                'government_id_type' => 'nullable|string|max:50',
                'government_id_number' => 'nullable|string|max:100',
                'id_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            ]);

        abort_unless(Branch::query()
            ->where('id', $validated['branch_id'])
            ->where('store_id', $storeId)
            ->exists(), 422, 'Selected branch is not available for this store.');

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
                'province' => $validated['province'] ?? $application->province,
                'city' => $validated['city'] ?? $application->city,
                'barangay' => $validated['barangay'] ?? $application->barangay,
                'hire_date' => $validated['hire_date'],
                'contract_end_date' => $validated['contract_end_date'] ?? null,
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
        if ($this->decisionNotYetAvailable($application)) {
            return response()->json([
                'success' => false,
                'message' => 'Rejecting is available on or after the latest interview date.',
            ], 422);
        }

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

    private function decisionNotYetAvailable(JobApplication $application): bool
    {
        $latest = $application->interviews()->orderByDesc('created_at')->orderByDesc('id')->first(['interview_date']);
        return !$latest || Carbon::parse($latest->interview_date)->startOfDay()->isAfter(now()->startOfDay());
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
