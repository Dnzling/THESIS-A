<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Hr\Payroll;
use App\Models\Hr\PayPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancePayrollController extends Controller
{
    public function periodPayroll(Request $request, int $id, \App\Http\Controllers\Api\Hr\PayPeriodController $payPeriods)
    {
        abort_unless(PayPeriod::query()->where('store_id', (int) $request->user()->store_id)->whereKey($id)->exists(), 404);
        return $payPeriods->getPayrollPerPeriod($request, $id);
    }

    public function submit(Request $request, int $id, \App\Http\Controllers\Api\Hr\PayrollController $payrollController)
    {
        $this->scopePayroll($request, $id);
        return $payrollController->submit($request, $id);
    }

    public function approve(Request $request, int $id, \App\Http\Controllers\Api\Hr\PayrollController $payrollController)
    {
        $this->scopePayroll($request, $id);
        return $payrollController->approve($request, $id);
    }

    public function release(Request $request, int $id, \App\Http\Controllers\Api\Hr\PayrollController $payrollController)
    {
        $this->scopePayroll($request, $id);
        return $payrollController->release($request, $id);
    }

    public function markPaid(Request $request, int $id, \App\Http\Controllers\Api\Hr\PayrollController $payrollController)
    {
        $this->scopePayroll($request, $id);
        return $payrollController->markPaid($request, $id);
    }

    public function bulkMarkPaid(Request $request, \App\Http\Controllers\Api\Hr\PayrollController $payrollController)
    {
        $ids = $request->validate(['payroll_ids' => ['required', 'array', 'min:1'], 'payroll_ids.*' => ['required', 'integer', 'distinct']])['payroll_ids'];
        $this->scopePayrolls($request, $ids);
        return $payrollController->bulkMarkPaid($request);
    }

    public function bulkSubmit(Request $request, \App\Http\Controllers\Api\Hr\PayrollController $payrollController)
    {
        $ids = $request->validate(['payroll_ids' => ['required', 'array', 'min:1'], 'payroll_ids.*' => ['required', 'integer', 'distinct']])['payroll_ids'];
        $this->scopePayrolls($request, $ids);
        return $payrollController->bulkSubmitForApproval($request);
    }

    public function bulkApprove(Request $request, \App\Http\Controllers\Api\Hr\PayrollController $payrollController)
    {
        $ids = $request->validate(['payroll_ids' => ['required', 'array', 'min:1'], 'payroll_ids.*' => ['required', 'integer', 'distinct']])['payroll_ids'];
        $this->scopePayrolls($request, $ids);
        return $payrollController->bulkApprove($request);
    }

    private function scopePayroll(Request $request, int $id): Payroll
    {
        return Payroll::query()
            ->whereKey($id)
            ->whereHas('employee', fn ($query) => $query->where('store_id', (int) $request->user()->store_id))
            ->firstOrFail();
    }

    private function scopePayrolls(Request $request, array $ids): void
    {
        abort_if(!$ids, 422, 'Select at least one payroll entry.');
        $storeId = (int) $request->user()->store_id;
        $count = Payroll::query()->whereIn('id', $ids)
            ->whereHas('employee', fn ($query) => $query->where('store_id', $storeId))
            ->count();
        abort_unless($count === count(array_unique($ids)), 404);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Payroll::with([
            'employee.user:id,fname,lname',
            'payPeriod',
        ])
            ->byUserStore();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('pay_period_id')) {
            $query->where('pay_period_id', $request->pay_period_id);
        }

        $payrolls = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $payrolls,
        ]);
    }
}
