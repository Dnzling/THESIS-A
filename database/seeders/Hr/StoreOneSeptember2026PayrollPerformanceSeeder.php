<?php

namespace Database\Seeders\Hr;

use App\Models\Hr\Attendance;
use App\Models\Hr\Employee;
use App\Models\Hr\Leave;
use App\Models\Hr\OvertimeRequest;
use App\Models\Hr\PayPeriod;
use App\Models\Hr\Payroll;
use App\Models\Hr\Shift;
use App\Models\Hr\ShiftAssignment;
use App\Models\Hr\ShiftSchedule;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StoreOneSeptember2026PayrollPerformanceSeeder extends Seeder
{
    private const STORE_ID = 1;
    private const START = '2026-09-01';
    private const END = '2026-09-15';
    private const MARKER = '[September 2026 payroll demo]';

    public function run(): void
    {
        DB::transaction(function (): void {
            $employees = Employee::where('store_id', self::STORE_ID)
                ->whereIn('status', ['active', 'on_leave'])
                ->get();
            $defaultShift = Shift::where('store_id', self::STORE_ID)
                ->where('is_active', true)
                ->whereKey(1)
                ->first();

            if ($employees->isEmpty() || !$defaultShift) {
                throw new RuntimeException('Store 1 needs active employees and its default morning shift before seeding.');
            }

            $period = PayPeriod::withTrashed()
                ->where('store_id', self::STORE_ID)
                ->whereDate('start_date', self::START)
                ->whereDate('end_date', self::END)
                ->first();
            if ($period && ($period->trashed() || (int) $period->store_id !== self::STORE_ID)) {
                throw new RuntimeException('The September 1-15 pay period already belongs to another store or was deleted.');
            }
            if ($period && (in_array($period->status, ['locked', 'completed'], true)
                || Payroll::where('pay_period_id', $period->id)
                    ->whereNotIn('status', ['draft', 'calculated', 'processing'])->exists())) {
                throw new RuntimeException('The September 1-15 pay period has locked, approved, or paid payroll. No demo data was changed.');
            }
            if (!$period) {
                PayPeriod::create([
                    'store_id' => self::STORE_ID,
                    'name' => 'September 1-15, 2026 (payroll demo)',
                    'start_date' => self::START,
                    'end_date' => self::END,
                    'cutoff_date' => self::END,
                    'status' => 'draft',
                    'notes' => self::MARKER . ' Draft only; no payroll payments are created.',
                ]);
            }

            $assignments = ShiftAssignment::with('shift')
                ->whereIn('employee_id', $employees->pluck('id'))
                ->whereDate('start_date', '<=', self::END)
                ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', self::START))
                ->get()
                ->groupBy('employee_id');

            foreach ($employees as $employee) {
                foreach (CarbonPeriod::create(self::START, self::END) as $day) {
                    $date = $day->toDateString();
                    $assignment = ($assignments->get($employee->id) ?? collect())->first(fn ($row) =>
                        $row->start_date->toDateString() <= $date
                        && (!$row->end_date || $row->end_date->toDateString() >= $date)
                    );
                    $shift = $assignment?->shift ?? $defaultShift;
                    if ((int) $shift->store_id !== self::STORE_ID) {
                        continue;
                    }

                    $pattern = $assignment?->recurring_pattern ?? [];
                    $workingDays = $pattern['week_days'] ?? $pattern['days'] ?? $shift->week_days ?? [];
                    if ($workingDays && !in_array(strtolower($day->format('l')), array_map('strtolower', $workingDays), true)) {
                        continue;
                    }

                    $schedule = ShiftSchedule::withTrashed()
                        ->where('employee_id', $employee->id)
                        ->whereDate('schedule_date', $date)
                        ->first();
                    if ($schedule?->trashed() || $schedule?->status === 'cancelled') {
                        continue;
                    }
                    if (!$schedule) {
                        $schedule = ShiftSchedule::create([
                            'employee_id' => $employee->id,
                            'shift_id' => $shift->id,
                            'assignment_id' => $assignment?->id,
                            'schedule_date' => $date,
                            'generation_method' => 'manual',
                            'status' => 'scheduled',
                            'notes' => self::MARKER,
                        ]);
                    }

                    $existing = Attendance::withTrashed()
                        ->where('employee_id', $employee->id)
                        ->whereDate('attendance_date', $date)
                        ->first();
                    if ($existing) {
                        continue;
                    }

                    $leaveExists = Leave::withTrashed()
                        ->where('employee_id', $employee->id)
                        ->whereDate('start_date', '<=', $date)
                        ->whereDate('end_date', '>=', $date)
                        ->exists();
                    if ($leaveExists) {
                        continue;
                    }

                    $scenario = match (true) {
                        $employee->id === 3 && $date === '2026-09-11' => 'on_leave',
                        $employee->id === 7 && $date === '2026-09-12' => 'absent',
                        $employee->id === 4 && $date === '2026-09-08' => 'late',
                        $employee->id === 10 && $date === '2026-09-09' => 'half_day',
                        $employee->id === 5 && $date === '2026-09-10' => 'overtime',
                        default => 'present',
                    };

                    if ($scenario === 'on_leave') {
                        Leave::firstOrCreate(
                            ['employee_id' => $employee->id, 'start_date' => $date, 'end_date' => $date],
                            ['leave_type' => 'personal', 'total_days' => 1, 'reason' => self::MARKER . ' Approved paid leave example.', 'is_paid' => true, 'deduct_from_balance' => false, 'status' => 'approved', 'approved_at' => Carbon::parse($date)->subDay()]
                        );
                    }

                    $attendance = Attendance::create($this->attendanceData(
                        $employee->id, $schedule, $schedule->shift ?? $shift, $date, $scenario
                    ));
                    if ($scenario === 'overtime') {
                        OvertimeRequest::create([
                            'employee_id' => $employee->id,
                            'attendance_id' => $attendance->id,
                            'ot_start' => $attendance->clock_out->copy()->subHour(),
                            'ot_end' => $attendance->clock_out,
                            'ot_minutes' => 60,
                            'ot_type' => 'regular',
                            'rate_multiplier' => 1.25,
                            'reason' => self::MARKER . ' Approved overtime example.',
                            'status' => 'approved',
                            'approved_at' => Carbon::parse($date)->endOfDay(),
                        ]);
                    }
                }
            }
        });
    }

    private function attendanceData(int $employeeId, ShiftSchedule $schedule, Shift $shift, string $date, string $scenario): array
    {
        $data = [
            'employee_id' => $employeeId,
            'shift_id' => $shift->id,
            'schedule_id' => $schedule->id,
            'attendance_date' => $date,
            'status' => in_array($scenario, ['absent', 'on_leave', 'late', 'half_day'], true) ? $scenario : 'present',
            'notes' => self::MARKER . ' Simulated ' . $scenario . ' record, not a real time punch.',
        ];
        if (in_array($scenario, ['absent', 'on_leave'], true)) {
            return $data;
        }

        $clockIn = Carbon::parse($date . ' ' . $shift->start_time->format('H:i:s'));
        $clockOut = Carbon::parse($date . ' ' . $shift->end_time->format('H:i:s'));
        if ($clockOut->lessThanOrEqualTo($clockIn)) {
            $clockOut->addDay();
        }
        $breakStart = $shift->break_start ? Carbon::parse($date . ' ' . $shift->break_start->format('H:i:s')) : null;
        $breakEnd = $shift->break_end ? Carbon::parse($date . ' ' . $shift->break_end->format('H:i:s')) : null;
        if ($breakStart && $breakStart->lessThan($clockIn)) {
            $breakStart->addDay();
        }
        if ($breakEnd && $breakStart && $breakEnd->lessThanOrEqualTo($breakStart)) {
            $breakEnd->addDay();
        }

        $lateMinutes = $scenario === 'late' ? 30 : 0;
        $overtimeMinutes = $scenario === 'overtime' ? 60 : 0;
        if ($lateMinutes) {
            $clockIn->addMinutes($lateMinutes);
        }
        if ($scenario === 'half_day') {
            $clockOut = $clockIn->copy()->addHours(4);
        } elseif ($overtimeMinutes) {
            $clockOut->addMinutes($overtimeMinutes);
        }
        $breakMinutes = $breakStart && $breakEnd && $clockOut->greaterThan($breakEnd)
            ? (int) $breakStart->diffInMinutes($breakEnd) : 0;

        return $data + [
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'clock_in_method' => 'manual',
            'clock_out_method' => 'manual',
            'break_start' => $breakMinutes ? $breakStart : null,
            'break_end' => $breakMinutes ? $breakEnd : null,
            'break_minutes' => $breakMinutes,
            'late_minutes' => $lateMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'is_ot_approved' => $overtimeMinutes > 0,
            'ot_approved_at' => $overtimeMinutes > 0 ? Carbon::parse($date)->endOfDay() : null,
            'total_worked_minutes' => max(0, (int) $clockIn->diffInMinutes($clockOut) - $breakMinutes - $overtimeMinutes),
        ];
    }
}
