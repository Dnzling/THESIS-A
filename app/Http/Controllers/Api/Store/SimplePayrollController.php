<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Models\Hr\PayPeriod;
use App\Models\Hr\Payroll;
use App\Models\Store\Branch;
use App\Models\Store\Store;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\Finance\CashflowService;

class SimplePayrollController extends Controller
{
    public function periods(Request $request): JsonResponse
    {
        $storeId = (int) $request->user()->store_id;
        $periods = PayPeriod::query()->where('store_id', $storeId)
            ->whereHas('payrolls', fn ($query) => $query
                ->whereJsonContains('payslip_snapshot->source', 'simple_attendance')
                ->whereHas('employee', fn ($employee) => $employee->where('store_id', $storeId)))
            ->with(['payrolls' => fn ($query) => $query
                ->whereJsonContains('payslip_snapshot->source', 'simple_attendance')
                ->whereHas('employee', fn ($employee) => $employee->where('store_id', $storeId))])
            ->orderByDesc('start_date')->get()
            ->map(fn (PayPeriod $period) => [
                'id' => $period->id,
                'name' => $period->name,
                'start_date' => $period->start_date?->toDateString(),
                'end_date' => $period->end_date?->toDateString(),
                'employee_count' => $period->payrolls->count(),
                'total_net' => round((float) $period->payrolls->sum('net_salary'), 2),
                'statuses' => $period->payrolls->pluck('status')->countBy(),
                'paid_total' => round((float) $period->payrolls->where('status', 'paid')->sum('net_salary'), 2),
                'pending_total' => round((float) $period->payrolls->whereIn('status', ['draft', 'calculated', 'approved', 'released'])->sum('net_salary'), 2),
            ])->values();

        return response()->json(['success' => true, 'data' => $periods]);
    }

    public function periodDetail(Request $request, int $periodId): JsonResponse
    {
        $storeId = (int) $request->user()->store_id;
        $period = PayPeriod::query()->where('store_id', $storeId)->findOrFail($periodId);
        $attendanceByEmployee = DB::table('simple_employee_attendances')
            ->where('store_id', $storeId)
            ->whereBetween('attendance_date', [$period->start_date->toDateString(), $period->end_date->toDateString()])
            ->orderBy('attendance_date')->get()->groupBy('employee_id');
        $payrolls = Payroll::query()->where('pay_period_id', $period->id)
            ->whereJsonContains('payslip_snapshot->source', 'simple_attendance')
            ->whereHas('employee', fn ($query) => $query->where('store_id', $storeId))
            ->with(['employee.user:id,fname,lname', 'items'])
            ->orderBy('employee_id')->get()
            ->map(function (Payroll $payroll) use ($attendanceByEmployee, $period) {
                $savedDays = $payroll->payslip_snapshot['attendance']['days'] ?? null;
                $employeeAttendance = $attendanceByEmployee->get($payroll->employee_id, collect())->keyBy(
                    fn ($record) => Carbon::parse($record->attendance_date)->toDateString()
                );
                $days = is_array($savedDays) ? $savedDays : collect(CarbonPeriod::create($period->start_date, $period->end_date))
                    ->map(function (Carbon $date) use ($employeeAttendance) {
                        $dateString = $date->toDateString();
                        $record = $employeeAttendance->get($dateString);
                        return [
                            'date' => $dateString,
                            'status' => $record?->status ?? 'no_record',
                            'worked_hours' => round((float) ($record?->worked_minutes ?? 0) / 60, 2),
                            'note' => $record?->note,
                            'missing_clock_out' => $record?->status === 'present' && $record?->clock_in_at && !$record?->clock_out_at,
                        ];
                    })->all();

                return [
                'id' => $payroll->id,
                'employee_id' => $payroll->employee_id,
                'employee_name' => trim(($payroll->employee?->user?->fname ?? '') . ' ' . ($payroll->employee?->user?->lname ?? '')),
                'employee_number' => $payroll->employee?->employee_number,
                'attendance' => $payroll->payslip_snapshot['attendance'] ?? [],
                'attendance_by_day' => $days,
                'base_salary' => (float) $payroll->base_salary,
                'overtime_amount' => (float) $payroll->overtime_amount,
                'allowances_total' => (float) $payroll->allowances_total,
                'bonuses_total' => (float) $payroll->bonuses_total,
                'deductions_total' => (float) $payroll->deductions_total,
                'tax_amount' => (float) $payroll->tax_amount,
                'net_salary' => (float) $payroll->net_salary,
                'status' => $payroll->status,
                'items' => $payroll->items,
                ];
            });

        return response()->json(['success' => true, 'data' => [
            'period' => ['id' => $period->id, 'name' => $period->name, 'start_date' => $period->start_date?->toDateString(), 'end_date' => $period->end_date?->toDateString()],
            'payrolls' => $payrolls,
        ]]);
    }

    public function updateDraft(Request $request, int $payrollId): JsonResponse
    {
        $validated = $request->validate([
            'base_salary' => ['required', 'numeric', 'min:0'],
            'overtime_amount' => ['required', 'numeric', 'min:0'],
            'allowances_total' => ['required', 'numeric', 'min:0'],
            'bonuses_total' => ['required', 'numeric', 'min:0'],
            'deductions_total' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
        ]);
        $payroll = $this->simplePayrollForStore($request, $payrollId);
        abort_unless(in_array($payroll->status, ['draft', 'calculated'], true), 409, 'Only draft payrolls can be edited.');
        $tax = (float) ($validated['tax_amount'] ?? 0);
        $net = round((float) $validated['base_salary'] + (float) $validated['overtime_amount']
            + (float) $validated['allowances_total'] + (float) $validated['bonuses_total']
            - (float) $validated['deductions_total'] - $tax, 2);
        abort_if($net < 0, 422, 'Deductions cannot exceed gross earnings.');
        $payroll->forceFill([...$validated, 'tax_amount' => $tax, 'net_salary' => $net])->save();

        return response()->json(['success' => true, 'data' => $payroll->fresh()]);
    }

    public function bulkComplete(Request $request): JsonResponse
    {
        $validated = $request->validate(['payroll_ids' => ['required', 'array', 'min:1'], 'payroll_ids.*' => ['required', 'integer', 'distinct']]);
        $storeId = (int) $request->user()->store_id;
        $updated = DB::transaction(function () use ($validated, $storeId, $request) {
            $payrolls = Payroll::query()->whereIn('id', $validated['payroll_ids'])
                ->whereIn('status', ['draft', 'calculated'])
                ->whereJsonContains('payslip_snapshot->source', 'simple_attendance')
                ->whereHas('employee', fn ($query) => $query->where('store_id', $storeId))
                ->lockForUpdate()->get();
            if ($payrolls->count() !== count($validated['payroll_ids'])) {
                throw ValidationException::withMessages(['payroll_ids' => 'Every selected payroll must be an editable Basic Payroll draft in your store.']);
            }
            foreach ($payrolls as $payroll) {
                $payroll->forceFill(['status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now()])->save();
            }
            return $payrolls->count();
        });

        return response()->json(['success' => true, 'completed_count' => $updated]);
    }

    public function bulkPay(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payroll_ids' => ['required', 'array', 'min:1'], 'payroll_ids.*' => ['required', 'integer', 'distinct'],
            'payment_date' => ['required', 'date'], 'payment_method' => ['required', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ]);
        $storeId = (int) $request->user()->store_id;
        DB::transaction(function () use ($validated, $storeId, $request) {
            $payrolls = Payroll::query()->whereIn('id', $validated['payroll_ids'])->whereIn('status', ['approved', 'released'])
                ->whereJsonContains('payslip_snapshot->source', 'simple_attendance')
                ->whereHas('employee', fn ($query) => $query->where('store_id', $storeId))
                ->lockForUpdate()->get();
            if ($payrolls->count() !== count($validated['payroll_ids'])) {
                throw ValidationException::withMessages(['payroll_ids' => 'Every selected payroll must be completed and unpaid in your store.']);
            }
            $cashflow = new CashflowService();
            foreach ($payrolls as $payroll) {
                $cashflow->debit($storeId, (float) $payroll->net_salary, 'payroll', (int) $payroll->id,
                    $request->user()->id, 'Payroll settlement #' . $payroll->id, $validated['payment_method'],
                    ['pay_period_id' => (int) $payroll->pay_period_id, 'employee_id' => (int) $payroll->employee_id], allowOverdraft: true);
                $payroll->forceFill([
                    'status' => 'paid', 'payment_date' => $validated['payment_date'],
                    'payment_method' => $validated['payment_method'], 'reference_number' => $validated['reference_number'] ?? null,
                    'paid_by' => $request->user()->id, 'paid_at' => now(),
                ])->save();
            }
        });

        return response()->json(['success' => true, 'paid_count' => count($validated['payroll_ids'])]);
    }

    public function employeePayslips(Request $request, int $employeeId): JsonResponse
    {
        $employee = Employee::query()->where('store_id', $request->user()->store_id)->findOrFail($employeeId);
        $rows = Payroll::query()->where('employee_id', $employee->id)->where('status', 'paid')
            ->whereJsonContains('payslip_snapshot->source', 'simple_attendance')->with('payPeriod')
            ->orderByDesc('payment_date')->get();
        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function printPaidPayslip(Request $request, int $payrollId)
    {
        abort_unless(class_exists(\Dompdf\Dompdf::class), 503, 'PDF generation dependency is not installed.');
        $payroll = $this->simplePayrollForStore($request, $payrollId);
        abort_unless($payroll->status === 'paid', 404);
        $payroll->load(['employee.user', 'employee.store', 'employee.branch', 'payPeriod', 'items']);
        $printedAt = now();
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $pdf = new \Dompdf\Dompdf($options);
        $pdf->loadHtml(view('hr.simple-payslip-pdf', [
            'payroll' => $payroll, 'employee' => $payroll->employee,
            'user' => $payroll->employee?->user, 'payPeriod' => $payroll->payPeriod, 'printedAt' => $printedAt,
        ])->render());
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();
        return response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="payslip_' . $payroll->id . '.pdf"']);
    }

    private function simplePayrollForStore(Request $request, int $payrollId): Payroll
    {
        return Payroll::query()->whereKey($payrollId)
            ->whereJsonContains('payslip_snapshot->source', 'simple_attendance')
            ->whereHas('employee', fn ($query) => $query->where('store_id', $request->user()->store_id))
            ->firstOrFail();
    }

    public function complete(Request $request, int $payrollId): JsonResponse
    {
        $storeId = (int) $request->user()->store_id;
        $payroll = DB::transaction(function () use ($payrollId, $storeId, $request) {
            $payroll = Payroll::query()
                ->whereKey($payrollId)
                ->whereHas('employee', fn ($query) => $query->where('store_id', $storeId))
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($payroll->status, ['draft', 'calculated'], true)) {
                throw ValidationException::withMessages(['status' => 'Only draft payrolls can be completed.']);
            }
            if (($payroll->payslip_snapshot['source'] ?? null) !== 'simple_attendance') {
                throw ValidationException::withMessages(['payroll' => 'This payroll belongs to another workflow.']);
            }

            $payroll->forceFill([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ])->save();

            return $payroll->fresh();
        });

        return response()->json([
            'success' => true,
            'message' => 'Payroll completed and ready for payout.',
            'data' => $payroll,
        ]);
    }

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
                if ($absenceDays > (float) $row['period_days'] || $paidLeaveDays > (float) $row['period_days']) {
                    throw ValidationException::withMessages([
                        'rows' => "Leave or absence days cannot exceed the {$row['period_days']}-day payroll period.",
                    ]);
                }
                $absenceDeduction = $row['pay_type'] === 'monthly' ? round($absenceDays * $dayRate, 2) : 0.0;
                // Monthly base pay already covers paid leave. Only attendance-paid
                // daily staff need approved paid leave added to their earnings.
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
                        'days' => $row['attendance_by_day'],
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
                // Only closed, present shifts are payable. Open or non-present rows
                // must not inflate either worked hours or overtime.
                $workedMinutes = (int) $present->sum(fn ($record) => (int) ($record->worked_minutes ?? 0));
                $lateMinutes = (int) $present->sum(fn ($record) => (int) ($record->late_minutes ?? 0));
                $overtimeMinutes = (int) $present->sum(fn ($record) => min(
                    (int) ($record->overtime_minutes ?? 0),
                    (int) ($record->worked_minutes ?? 0)
                ));
                $payType = 'daily';
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
                $attendanceByDay = collect(CarbonPeriod::create($start, $end))
                    ->map(function (Carbon $date) use ($records) {
                        $dateString = $date->toDateString();
                        $record = $records->first(fn ($item) => Carbon::parse($item->attendance_date)->toDateString() === $dateString);
                        return [
                            'date' => $dateString,
                            'status' => $record?->status ?? 'no_record',
                            'worked_hours' => round((float) ($record?->worked_minutes ?? 0) / 60, 2),
                            'note' => $record?->note,
                            'missing_clock_out' => $record?->status === 'present' && $record?->clock_in_at && !$record?->clock_out_at,
                        ];
                    })->values()->all();
                $regularWorkedHours = round(max(0, $workedMinutes - $overtimeMinutes) / 60, 2);
                $overtimeHours = round($overtimeMinutes / 60, 2);
                $baseSalary = $regularWorkedHours * $hourlyRate;
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
                    'period_days' => $periodDays,
                    'standard_paid_hours' => $standardHours,
                    'completed_workdays' => $present->count(),
                    'worked_hours' => $workedHours,
                    'attendance_by_day' => $attendanceByDay,
                    'regular_worked_hours' => $regularWorkedHours,
                    'late_minutes' => $lateMinutes,
                    'late_occurrences' => $present->filter(fn ($record) => (int) $record->late_minutes > 0)->count(),
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
