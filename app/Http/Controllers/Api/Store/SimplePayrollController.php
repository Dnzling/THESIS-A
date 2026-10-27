<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Models\Hr\PayPeriod;
use App\Models\Hr\Payroll;
use App\Models\Store\Branch;
use App\Models\Store\Store;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SimplePayrollController extends Controller
{
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $storeId = (int) $request->user()->store_id;
        $this->validatePeriod($validated['start_date'], $validated['end_date']);
        $branchId = $this->validateBranch($storeId, $validated['branch_id'] ?? null);

        return response()->json([
            'success' => true,
            'data' => $this->previewRows($storeId, $validated['start_date'], $validated['end_date'], $branchId),
        ]);
    }

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'branch_id' => ['nullable', 'integer'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.employee_id' => ['required', 'integer', 'distinct'],
            'rows.*.unpaid_absence_days' => ['nullable', 'numeric', 'min:0'],
            'rows.*.paid_leave_days' => ['nullable', 'numeric', 'min:0'],
            'rows.*.allowances' => ['nullable', 'numeric', 'min:0'],
            'rows.*.incentives' => ['nullable', 'numeric', 'min:0'],
            'rows.*.other_deductions' => ['nullable', 'numeric', 'min:0'],
        ]);

        $storeId = (int) $request->user()->store_id;
        $this->validatePeriod($validated['start_date'], $validated['end_date']);
        $branchId = $this->validateBranch($storeId, $validated['branch_id'] ?? null);
        $start = Carbon::parse($validated['start_date'])->toDateString();
        $end = Carbon::parse($validated['end_date'])->toDateString();
        $preview = collect($this->previewRows($storeId, $start, $end, $branchId))->keyBy('employee_id');

        $submittedRows = collect($validated['rows'])->keyBy(fn ($row) => (int) $row['employee_id']);
        if ($submittedRows->keys()->diff($preview->keys())->isNotEmpty()) {
            throw ValidationException::withMessages(['rows' => 'One or more employees are outside your store or selected branch. Refresh the preview and try again.']);
        }

        $period = DB::transaction(function () use ($storeId, $start, $end, $request, $preview, $submittedRows) {
            $overlap = PayPeriod::query()
                ->where('store_id', $storeId)
                ->whereDate('start_date', '<=', $end)
                ->whereDate('end_date', '>=', $start)
                ->lockForUpdate()
                ->get();

            $period = $overlap->first(fn ($candidate) => $candidate->start_date->toDateString() === $start
                && $candidate->end_date->toDateString() === $end);

            if ($overlap->isNotEmpty() && !$period) {
                throw ValidationException::withMessages(['start_date' => 'This date range overlaps an existing payroll period. Choose the existing period or a different range.']);
            }

            if (!$period) {
                $period = PayPeriod::create([
                    'store_id' => $storeId,
                    'name' => 'Payroll ' . Carbon::parse($start)->format('M j') . ' to ' . Carbon::parse($end)->format('M j, Y'),
                    'start_date' => $start,
                    'end_date' => $end,
                    'cutoff_date' => $end,
                    'created_by' => $request->user()->id,
                    'status' => 'draft',
                ]);
            } elseif (in_array($period->status, ['locked', 'completed'], true)) {
                throw ValidationException::withMessages(['start_date' => 'This payroll period is locked or completed.']);
            }

            foreach ($submittedRows as $employeeId => $adjustment) {
                $row = $preview->get($employeeId);
                $absenceDays = (float) ($adjustment['unpaid_absence_days'] ?? 0);
                $paidLeaveDays = (float) ($adjustment['paid_leave_days'] ?? 0);
                $allowances = round((float) ($adjustment['allowances'] ?? 0), 2);
                $incentives = round((float) ($adjustment['incentives'] ?? 0), 2);
                $otherDeductions = round((float) ($adjustment['other_deductions'] ?? 0), 2);
                $dayRate = (float) $row['day_rate'];
                $absenceDeduction = $row['pay_type'] === 'monthly' ? round($absenceDays * $dayRate, 2) : 0.0;
                $paidLeavePay = $row['pay_type'] === 'daily' ? round($paidLeaveDays * $dayRate, 2) : 0.0;
                $baseSalary = round((float) $row['base_salary'] + $paidLeavePay, 2);
                $deductions = round((float) $row['late_deduction'] + $absenceDeduction + $otherDeductions, 2);
                $gross = round($baseSalary + (float) $row['overtime_amount'] + $allowances + $incentives, 2);
                $net = round($gross - $deductions, 2);

                if ($net < 0) {
                    throw ValidationException::withMessages(["rows.{$employeeId}.other_deductions" => 'Deductions cannot exceed gross pay.']);
                }

                $payroll = Payroll::query()->where('employee_id', $employeeId)->where('pay_period_id', $period->id)->lockForUpdate()->first();
                if ($payroll && !in_array($payroll->status, ['draft', 'calculated'], true)) {
                    throw ValidationException::withMessages(['rows' => 'A payroll in this period has already been submitted, approved, or paid and cannot be regenerated.']);
                }
                if ($payroll && ($payroll->payslip_snapshot['source'] ?? null) !== 'simple_attendance') {
                    throw ValidationException::withMessages(['rows' => 'A payroll already exists for an employee in this period from another payroll workflow. It was left unchanged.']);
                }

                $snapshot = [
                    'source' => 'simple_attendance',
                    'pay_type' => $row['pay_type'],
                    'paid_hours_per_day' => $row['standard_paid_hours'],
                    'attendance' => [
                        'completed_workdays' => $row['completed_workdays'],
                        'worked_hours' => $row['worked_hours'],
                        'late_minutes' => $row['late_minutes'],
                        'overtime_hours' => $row['overtime_hours'],
                        'missing_clock_outs' => $row['missing_clock_outs'],
                    ],
                    'manual_review' => [
                        'unpaid_absence_days' => $absenceDays,
                        'paid_leave_days' => $paidLeaveDays,
                        'allowances' => $allowances,
                        'incentives' => $incentives,
                        'other_deductions' => $otherDeductions,
                        'absence_deduction' => $absenceDeduction,
                    ],
                ];

                $payroll ??= new Payroll(['employee_id' => $employeeId, 'pay_period_id' => $period->id]);
                $payroll->forceFill([
                    'base_salary' => $baseSalary,
                    'overtime_hours' => $row['overtime_hours'],
                    'overtime_amount' => $row['overtime_amount'],
                    'late_minutes' => $row['late_minutes'],
                    'late_occurrences' => $row['late_occurrences'],
                    'late_deduction' => $row['late_deduction'],
                    'allowances_total' => $allowances,
                    'bonuses_total' => $incentives,
                    'deductions_total' => $deductions,
                    'tax_amount' => 0,
                    'net_salary' => $net,
                    'status' => 'draft',
                    'notes' => 'Draft from Simple attendance. Review attendance, absences, leave, and deductions before submission.',
                    'hourly_rate_at_generation' => $row['hourly_rate'],
                    'payslip_snapshot' => $snapshot,
                ])->save();
            }

            return $period;
        });

        return response()->json([
            'success' => true,
            'message' => 'Payroll drafts generated. Review attendance and manual adjustments before submitting for approval.',
            'data' => ['pay_period_id' => $period->id, 'pay_period' => $period],
        ], 201);
    }

    private function previewRows(int $storeId, string $start, string $end, ?int $branchId): array
    {
        $store = Store::query()->findOrFail($storeId);
        $settings = is_array($store->settings) ? $store->settings : [];
        $payrollSettings = $settings['hr_payroll_settings'] ?? [];
        $payrollConfiguration = $settings['hr_payroll_configuration'] ?? [];
        $standardHours = max(1, min(24, (float) ($payrollSettings['paidHours'] ?? $settings['hr_workforce_rules']['dailyHours'] ?? 8)));
        $overtimeMultiplier = max(1, min(5, (float) ($payrollSettings['overtimeMultiplier'] ?? 1.25)));
        $lateEnabled = (bool) ($payrollConfiguration['lateDeductionEnabled'] ?? false);
        $lateRate = max(0, min(100, (float) ($payrollConfiguration['lateDeductionRate'] ?? 100)));
        $periodDays = Carbon::parse($start)->diffInDays(Carbon::parse($end)) + 1;

        return Employee::query()
            ->with(['user:id,fname,lname,email,avatar_path', 'branch:id,name'])
            ->where('store_id', $storeId)
            ->where('status', 'active')
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->orderBy('employee_number')
            ->get()
            ->map(function (Employee $employee) use ($storeId, $start, $end, $periodDays, $standardHours, $overtimeMultiplier, $lateEnabled, $lateRate) {
                $records = DB::table('simple_employee_attendances')
                    ->where('store_id', $storeId)
                    ->where('employee_id', $employee->id)
                    ->whereBetween('attendance_date', [$start, $end])
                    ->orderBy('attendance_date')
                    ->get();
                $present = $records->filter(fn ($record) => $record->status === 'present' && $record->clock_in_at && $record->clock_out_at);
                $missingClockOuts = $records->filter(fn ($record) => $record->status === 'present' && $record->clock_in_at && !$record->clock_out_at)->count();
                $workedMinutes = (int) $records->sum('worked_minutes');
                $lateMinutes = (int) $records->sum('late_minutes');
                $overtimeMinutes = (int) $records->sum('overtime_minutes');
                $payType = in_array($employee->pay_type, ['daily', 'hourly'], true) ? $employee->pay_type : 'monthly';
                $salary = (float) ($employee->salary ?? 0);
                $hourlyRate = match ($payType) {
                    'daily' => $salary / $standardHours,
                    'hourly' => (float) ($employee->hourly_rate ?: $salary),
                    default => $salary / 30 / $standardHours,
                };
                $dayRate = match ($payType) {
                    'daily' => $salary,
                    'hourly' => $hourlyRate * $standardHours,
                    default => $salary / 30,
                };
                $workedHours = round($workedMinutes / 60, 2);
                $overtimeHours = round($overtimeMinutes / 60, 2);
                $baseSalary = match ($payType) {
                    'daily' => $present->count() * $dayRate,
                    'hourly' => $workedHours * $hourlyRate,
                    default => $salary / 30 * $periodDays,
                };
                $overtimeAmount = round($overtimeHours * $hourlyRate * $overtimeMultiplier, 2);
                $lateDeduction = $lateEnabled ? round(($lateMinutes / 60) * $hourlyRate * ($lateRate / 100), 2) : 0;

                return [
                    'employee_id' => $employee->id,
                    'employee_name' => trim(($employee->user?->fname ?? $employee->fname) . ' ' . ($employee->user?->lname ?? $employee->lname)),
                    'employee_number' => $employee->employee_number,
                    'avatar_url' => $employee->user?->avatar_url,
                    'branch_name' => $employee->branch?->name,
                    'pay_type' => $payType,
                    'salary_rate' => $salary,
                    'standard_paid_hours' => $standardHours,
                    'completed_workdays' => $present->count(),
                    'worked_hours' => $workedHours,
                    'late_minutes' => $lateMinutes,
                    'late_occurrences' => $records->filter(fn ($record) => (int) $record->late_minutes > 0)->count(),
                    'overtime_hours' => $overtimeHours,
                    'missing_clock_outs' => $missingClockOuts,
                    'day_rate' => round($dayRate, 4),
                    'hourly_rate' => round($hourlyRate, 4),
                    'base_salary' => round($baseSalary, 2),
                    'overtime_amount' => $overtimeAmount,
                    'late_deduction' => $lateDeduction,
                ];
            })
            ->values()
            ->all();
    }

    private function validatePeriod(string $start, string $end): void
    {
        if (Carbon::parse($end)->isFuture()) {
            throw ValidationException::withMessages(['end_date' => 'Payroll can only be generated for completed dates.']);
        }
    }

    private function validateBranch(int $storeId, ?int $branchId): ?int
    {
        if ($branchId === null) {
            return null;
        }
        if (!Branch::query()->where('store_id', $storeId)->whereKey($branchId)->exists()) {
            throw ValidationException::withMessages(['branch_id' => 'Select a branch from your store.']);
        }
        return $branchId;
    }
}
