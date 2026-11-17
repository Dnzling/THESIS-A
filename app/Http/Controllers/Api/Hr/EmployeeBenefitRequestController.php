<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Models\Core\SystemNotification;
use App\Models\Core\User;
use App\Models\Hr\DeductionType;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeBenefitRequest;
use App\Models\Hr\EmployeeDeduction;
use App\Models\Hr\PayrollItem;
use App\Models\Finance\FinanceExpense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeBenefitRequestController extends Controller
{
    public function mine(Request $request): JsonResponse
    {
        $employee = $this->myEmployee($request);
        $assignments = EmployeeDeduction::query()
            ->with('deductionType:id,store_id,name,category,is_active,benefit_limit')
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->where('effective_date', '<=', now()->toDateString())
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString()))
            ->whereHas('deductionType', fn ($q) => $q->where('store_id', $employee->store_id)->where('category', 'benefit')->where('is_active', true))
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'benefits' => $assignments->unique('deduction_type_id')
                    ->map(fn ($assignment) => $this->balance($employee, $assignment->deductionType))->values(),
                'requests' => EmployeeBenefitRequest::query()
                    ->with('deductionType:id,name')
                    ->where('store_id', $employee->store_id)
                    ->where('employee_id', $employee->id)
                    ->latest()->get(),
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeReview($request);
        $rows = EmployeeBenefitRequest::query()
            ->with(['employee.user:id,fname,lname', 'deductionType:id,name', 'reviewer:id,fname,lname'])
            ->where('store_id', $request->user()->store_id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()->paginate(min(50, max(1, (int) $request->input('per_page', 15))));

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function show(Request $request, EmployeeBenefitRequest $benefitRequest): JsonResponse
    {
        abort_unless($request->user()?->hasAnyPermission(['hr.settings.manage', 'finance.expenses.view']), 403);
        $this->scopeRequest($request, $benefitRequest);
        return response()->json([
            'success' => true,
            'data' => $benefitRequest->load(['employee.user:id,fname,lname', 'deductionType:id,name', 'reviewer:id,fname,lname']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $employee = $this->myEmployee($request);
        $data = $request->validate([
            'deduction_type_id' => 'required|integer',
            'provider' => 'required|string|max:150',
            'service_type' => 'required|string|max:120',
            'service_date' => 'required|date',
            'requested_amount' => 'required|numeric|gt:0',
            'notes' => 'nullable|string|max:2000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
        ]);

        $type = $this->eligibleType($employee, (int) $data['deduction_type_id']);
        $balance = $this->balance($employee, $type);
        if ((float) $data['requested_amount'] > $balance['available']) {
            throw ValidationException::withMessages(['requested_amount' => 'Requested amount exceeds the available benefit balance.']);
        }

        $path = $request->file('attachment')?->store('hr/benefit-requests', 'public');
        $benefitRequest = EmployeeBenefitRequest::create([
            'store_id' => $employee->store_id,
            'employee_id' => $employee->id,
            'deduction_type_id' => $type->id,
            'provider' => $data['provider'],
            'service_type' => $data['service_type'],
            'service_date' => $data['service_date'],
            'requested_amount' => $data['requested_amount'],
            'notes' => $data['notes'] ?? null,
            'request_attachment_path' => $path,
        ]);

        $this->notifyHr($employee, $benefitRequest);
        return response()->json(['success' => true, 'data' => $benefitRequest, 'message' => 'Benefit request submitted to HR.'], 201);
    }

    public function review(Request $request, EmployeeBenefitRequest $benefitRequest): JsonResponse
    {
        $this->authorizeReview($request);
        $this->scopeRequest($request, $benefitRequest);
        $data = $request->validate([
            'decision' => 'required|in:approved,rejected',
            'approved_amount' => 'required_if:decision,approved|nullable|numeric|gt:0',
            'review_notes' => 'required_if:decision,rejected|nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($request, $benefitRequest, $data) {
            DB::table('deduction_types')->where('id', $benefitRequest->deduction_type_id)->lockForUpdate()->first();
            $locked = EmployeeBenefitRequest::query()->lockForUpdate()->findOrFail($benefitRequest->id);
            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'Only pending requests can be reviewed.']);
            }
            if ($data['decision'] === 'approved') {
                $amount = (float) $data['approved_amount'];
                if ($amount > (float) $locked->requested_amount) {
                    throw ValidationException::withMessages(['approved_amount' => 'Approved amount cannot exceed the requested amount.']);
                }
                $balance = $this->balance($locked->employee, $locked->deductionType);
                if ($amount > $balance['available']) {
                    throw ValidationException::withMessages(['approved_amount' => 'Insufficient available benefit balance.']);
                }
                $locked->approved_amount = $amount;
            }
            $locked->status = $data['decision'];
            $locked->review_notes = $data['review_notes'] ?? null;
            $locked->reviewed_by = $request->user()->id;
            $locked->reviewed_at = now();
            $locked->save();
            $this->notifyEmployee($locked, 'Benefit request ' . $locked->status, 'HR ' . $locked->status . ' your request for ' . $locked->deductionType->name . '.');
        });

        return response()->json(['success' => true, 'message' => 'Benefit request reviewed.']);
    }

    public function complete(Request $request, EmployeeBenefitRequest $benefitRequest): JsonResponse
    {
        $employee = $this->myEmployee($request);
        $this->scopeRequest($request, $benefitRequest);
        abort_unless((int) $benefitRequest->employee_id === (int) $employee->id, 403);
        $data = $request->validate(['receipt' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:5120']);
        if ($benefitRequest->status !== 'approved') {
            throw ValidationException::withMessages(['status' => 'Only approved requests can submit a receipt.']);
        }
        $path = $data['receipt']->store('hr/benefit-receipts', 'public');
        $benefitRequest->update(['receipt_path' => $path, 'status' => 'completed']);
        $this->notifyHr($employee, $benefitRequest);
        return response()->json(['success' => true, 'message' => 'Receipt submitted to HR.']);
    }

    public function settle(Request $request, EmployeeBenefitRequest $benefitRequest): JsonResponse
    {
        $this->authorizeReview($request);
        $this->scopeRequest($request, $benefitRequest);
        $data = $request->validate([
            'used_amount' => 'required|numeric|gte:0',
            'company_payment_amount' => 'nullable|numeric|gte:0',
        ]);
        DB::transaction(function () use ($benefitRequest, $data, $request) {
            $locked = EmployeeBenefitRequest::query()->lockForUpdate()->findOrFail($benefitRequest->id);
            if ($locked->status !== 'completed') {
                throw ValidationException::withMessages(['status' => 'A receipt is required before settlement.']);
            }
            if ((float) $data['used_amount'] > (float) $locked->approved_amount) {
                throw ValidationException::withMessages(['used_amount' => 'Used amount cannot exceed the approved amount.']);
            }
            if ((float) ($data['company_payment_amount'] ?? 0) > (float) $data['used_amount']) {
                throw ValidationException::withMessages(['company_payment_amount' => 'Company payment cannot exceed benefit usage.']);
            }
            $locked->update([
                'used_amount' => $data['used_amount'],
                'company_payment_amount' => $data['company_payment_amount'] ?? 0,
                'status' => 'settled', 'settled_at' => now(),
            ]);
            if ((float) ($data['company_payment_amount'] ?? 0) > 0) {
                FinanceExpense::query()->firstOrCreate(
                    ['store_id' => $locked->store_id, 'reference_type' => 'employee_benefit_request', 'reference_id' => $locked->id],
                    [
                        'department' => 'HR', 'category' => 'Benefits', 'amount' => $data['company_payment_amount'],
                        'expense_date' => now()->toDateString(), 'status' => 'pending_approval',
                        'description' => $locked->deductionType->name . ' benefit for ' . $locked->employee->user?->full_name,
                        'requested_by' => $request->user()->id,
                    ]
                );
            }
            $this->notifyEmployee($locked, 'Benefit usage recorded', 'Your ' . $locked->deductionType->name . ' usage has been recorded.');
        });
        return response()->json(['success' => true, 'message' => 'Benefit usage settled.']);
    }

    private function balance(Employee $employee, DeductionType $type): array
    {
        $contributed = (float) PayrollItem::query()
            ->whereHas('payroll', fn ($q) => $q->where('employee_id', $employee->id)->where('status', 'paid'))
            ->where('type', 'deduction')
            ->where(function ($q) use ($type) {
                $q->where('deduction_type_id', $type->id)
                    ->orWhere(fn ($legacy) => $legacy->whereNull('deduction_type_id')->where('name', $type->name));
            })->sum('amount');
        $limit = $type->benefit_limit !== null ? (float) $type->benefit_limit : $contributed;
        $used = (float) EmployeeBenefitRequest::query()
            ->where('employee_id', $employee->id)->where('deduction_type_id', $type->id)
            ->where('status', 'settled')->sum('used_amount');
        $reserved = (float) EmployeeBenefitRequest::query()
            ->where('employee_id', $employee->id)->where('deduction_type_id', $type->id)
            ->whereIn('status', ['approved', 'completed'])->sum('approved_amount');

        return [
            'deduction_type_id' => $type->id, 'name' => $type->name,
            'contributed' => round($contributed, 2), 'limit' => round($limit, 2),
            'limit_source' => $type->benefit_limit !== null ? 'coverage' : 'contributions',
            'used' => round($used, 2), 'reserved' => round($reserved, 2),
            'available' => round(max(0, $limit - $used - $reserved), 2),
            'usage_percent' => $limit > 0 ? round(min(100, ($used / $limit) * 100), 1) : 0,
            'contribution_percent' => $limit > 0 ? round(min(100, ($contributed / $limit) * 100), 1) : 0,
        ];
    }

    private function eligibleType(Employee $employee, int $typeId): DeductionType
    {
        $assignment = EmployeeDeduction::query()->with('deductionType')
            ->where('employee_id', $employee->id)->where('deduction_type_id', $typeId)
            ->active()->current()->first();
        $type = $assignment?->deductionType;
        if (!$type || (int) $type->store_id !== (int) $employee->store_id || $type->category !== 'benefit') {
            throw ValidationException::withMessages(['deduction_type_id' => 'This benefit is not active for your employee profile.']);
        }
        return $type;
    }

    private function myEmployee(Request $request): Employee
    {
        $employee = Employee::query()->where('store_id', $request->user()->store_id)
            ->where('user_id', $request->user()->id)->where('status', 'active')->first();
        abort_unless($employee, 403, 'An active employee profile is required.');
        return $employee;
    }

    private function authorizeReview(Request $request): void
    {
        abort_unless($request->user()?->hasPermissionTo('hr.employees.view') && $request->user()?->hasPermissionTo('hr.settings.manage'), 403);
    }

    private function scopeRequest(Request $request, EmployeeBenefitRequest $benefitRequest): void
    {
        abort_unless((int) $benefitRequest->store_id === (int) $request->user()->store_id, 404);
    }

    private function notifyHr(Employee $employee, EmployeeBenefitRequest $benefitRequest): void
    {
        User::query()->where('store_id', $employee->store_id)->get()->each(function (User $user) use ($employee, $benefitRequest) {
            if (!$user->hasPermissionTo('hr.settings.manage')) return;
            SystemNotification::create([
                'store_id' => $employee->store_id, 'branch_id' => $employee->branch_id, 'user_id' => $user->id,
                'module' => 'hr', 'entity_type' => 'employee_benefit_request', 'entity_id' => $benefitRequest->id,
                'action' => $benefitRequest->status, 'title' => 'Benefit request awaiting HR',
                'message' => 'An employee benefit request needs review or settlement.',
                'link' => '/hr/benefit-requests',
            ]);
        });
    }

    private function notifyEmployee(EmployeeBenefitRequest $benefitRequest, string $title, string $message): void
    {
        if (!$benefitRequest->employee?->user_id) return;
        SystemNotification::create([
            'store_id' => $benefitRequest->store_id, 'branch_id' => $benefitRequest->employee->branch_id,
            'user_id' => $benefitRequest->employee->user_id, 'module' => 'hr',
            'entity_type' => 'employee_benefit_request', 'entity_id' => $benefitRequest->id,
            'action' => $benefitRequest->status, 'title' => $title, 'message' => $message,
            'link' => '/employee-benefits',
        ]);
    }
}
