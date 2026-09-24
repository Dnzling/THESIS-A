<?php

namespace Database\Seeders\Hr;

use App\Http\Controllers\Api\Hr\PayrollController;
use App\Models\Core\User;
use App\Models\Hr\Attendance;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeePayAdjustment;
use App\Models\Hr\Leave;
use App\Models\Hr\OvertimeRequest;
use App\Models\Hr\PayPeriod;
use App\Models\Hr\Payroll;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StoreOneSeptember2026PayrollScenariosSeeder extends Seeder
{
    private const MARKER = '[September 2026 payroll demo]';
    private const ORIGINAL_NOTE = self::MARKER . ' Simulated present record, not a real time punch.';

    public function run(): void
    {
        DB::transaction(function (): void {
            (new StoreOneSeptember2026PayrollPerformanceSeeder())->run();

            $period = PayPeriod::where('store_id', 1)
                ->whereDate('start_date', '2026-09-01')
                ->whereDate('end_date', '2026-09-15')
                ->firstOrFail();
            if (in_array($period->status, ['locked', 'completed'], true)
                || Payroll::where('pay_period_id', $period->id)
                    ->whereNotIn('status', ['draft', 'calculated', 'processing'])->exists()) {
                throw new RuntimeException('September payroll has an approved, paid, or locked record. Demo data was not changed.');
            }

            $scenarios = [
                [17, '2026-09-03', 'late'],
                [18, '2026-09-04', 'absent'],
                [21, '2026-09-07', 'paid_leave'],
                [37, '2026-09-08', 'overtime'],
                [30, '2026-09-09', 'short_break'],
                [32, '2026-09-10', 'half_day'],
            ];
            foreach ($scenarios as [$employeeId, $date, $type]) {
                $attendance = Attendance::where('employee_id', $employeeId)
                    ->whereDate('attendance_date', $date)
                    ->where('notes', self::ORIGINAL_NOTE)
                    ->first();
                if (!$attendance || (int) $attendance->employee?->store_id !== 1) {
                    continue;
                }

                if ($type === 'late') {
                    $attendance->clock_in = $attendance->clock_in->addMinutes(20);
                    $attendance->late_minutes = 20;
                    $attendance->total_worked_minutes -= 20;
                    $attendance->status = 'late';
                } elseif ($type === 'absent' || $type === 'paid_leave') {
                    $attendance->clock_in = null;
                    $attendance->clock_out = null;
                    $attendance->break_start = null;
                    $attendance->break_end = null;
                    $attendance->break_minutes = 0;
                    $attendance->total_worked_minutes = 0;
                    $attendance->status = $type === 'absent' ? 'absent' : 'on_leave';
                    if ($type === 'paid_leave') {
                        Leave::firstOrCreate(
                            ['employee_id' => $employeeId, 'start_date' => $date, 'end_date' => $date],
                            ['leave_type' => 'personal', 'total_days' => 1, 'reason' => self::MARKER . ' Paid leave scenario.', 'is_paid' => true, 'deduct_from_balance' => false, 'status' => 'approved', 'approved_at' => Carbon::parse($date)->subDay()]
                        );
                    }
                } elseif ($type === 'overtime') {
                    $attendance->clock_out = $attendance->clock_out->addMinutes(90);
                    $attendance->overtime_minutes = 90;
                    $attendance->is_ot_approved = true;
                    $attendance->ot_approved_at = Carbon::parse($date)->endOfDay();
                } elseif ($type === 'short_break' && $attendance->break_start) {
                    $attendance->break_end = $attendance->break_start->copy()->addMinutes(45);
                    $attendance->break_minutes = 45;
                    $attendance->total_worked_minutes += 15;
                } elseif ($type === 'half_day') {
                    $attendance->clock_out = $attendance->clock_in->copy()->addHours(4);
                    $attendance->break_start = null;
                    $attendance->break_end = null;
                    $attendance->break_minutes = 0;
                    $attendance->total_worked_minutes = 240;
                    $attendance->status = 'half_day';
                }
                $attendance->notes = self::MARKER . ' Simulated ' . $type . ' scenario, not a real time punch.';
                $attendance->save();

                if ($type === 'overtime') {
                    OvertimeRequest::firstOrCreate(
                        ['attendance_id' => $attendance->id, 'reason' => self::MARKER . ' Approved overtime scenario.'],
                        ['employee_id' => $employeeId, 'ot_start' => $attendance->clock_out->copy()->subMinutes(90), 'ot_end' => $attendance->clock_out, 'ot_minutes' => 90, 'ot_type' => 'regular', 'rate_multiplier' => 1.25, 'status' => 'approved', 'approved_at' => Carbon::parse($date)->endOfDay()]
                    );
                }
            }

            $adjustments = [
                [17, '2026-09-05', 'allowance', 'Demo Transport Allowance', 350],
                [17, '2026-09-10', 'incentive', 'Demo Sales Incentive', 600],
                [18, '2026-09-08', 'allowance', 'Demo Meal Allowance', 250],
                [21, '2026-09-11', 'incentive', 'Demo Service Incentive', 450],
                [37, '2026-09-08', 'allowance', 'Demo Delivery Allowance', 500],
                [37, '2026-09-12', 'incentive', 'Demo Delivery Incentive', 800],
                [30, '2026-09-09', 'allowance', 'Demo Transport Allowance', 300],
                [32, '2026-09-10', 'incentive', 'Demo Performance Incentive', 400],
                [5, '2026-09-10', 'incentive', 'Demo Performance Incentive', 1000],
            ];
            foreach ($adjustments as [$employeeId, $date, $type, $name, $amount]) {
                if (!Employee::where('store_id', 1)->whereKey($employeeId)->exists()) {
                    continue;
                }
                EmployeePayAdjustment::firstOrCreate(
                    ['employee_id' => $employeeId, 'effective_date' => $date, 'type' => $type, 'name' => $name],
                    ['store_id' => 1, 'amount' => $amount, 'status' => 'approved', 'notes' => self::MARKER . ' Synthetic cutoff test input.']
                );
            }

            $missingIds = Employee::where('store_id', 1)
                ->whereIn('status', ['active', 'on_leave'])
                ->whereNotIn('id', Payroll::where('pay_period_id', $period->id)->select('employee_id'))
                ->pluck('id')->all();
            if ($missingIds) {
                $admin = User::where('store_id', 1)->whereKey(2)->first();
                if (!$admin) {
                    throw new RuntimeException('Store 1 admin user is required to generate demo payroll drafts.');
                }
                Auth::onceUsingId($admin->id);
                $response = app(PayrollController::class)->generate(Request::create('/api/payroll/generate', 'POST', [
                    'pay_period_id' => $period->id,
                    'employee_ids' => $missingIds,
                    'initial_status' => 'draft',
                ]));
                if (!$response->getData(true)['success']) {
                    throw new RuntimeException('Demo payroll generation failed: ' . $response->getData(true)['message']);
                }
                $result = $response->getData(true)['data'];
                $errors = $result['errors'] ?? [];
                $unexpectedErrors = array_filter($errors, fn ($error) =>
                    !str_contains((string) ($error['error'] ?? ''), 'Net pay is negative')
                );
                if ($unexpectedErrors || ($result['skipped'] ?? 0) > 0
                    || ($result['generated'] ?? 0) + count($errors) !== count($missingIds)) {
                    throw new RuntimeException('Demo payroll generation was incomplete: ' . json_encode($result['errors'] ?? []));
                }
                $this->command?->info('Created ' . $result['generated'] . ' September draft payrolls; ' . count($errors) . ' salary validation error(s).');
            }
        });
    }
}
