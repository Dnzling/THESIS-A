<?php

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Store45SimplePayrollAttendanceSeeder extends Seeder
{
    private const STORE_ID = 45;
    private const START_DATE = '2026-10-01';
    private const END_DATE = '2026-11-10';
    private const TIMEZONE = 'Asia/Manila';
    private const NOTE_PREFIX = '[Store 45 Basic Payroll demo]';

    /** @var array{days: list<string>, opens_at: string, closes_at: string} */
    private array $operatingHours;

    public function run(): void
    {
        $store = $this->targetStore();
        $employees = DB::table('employees')
            ->where('store_id', self::STORE_ID)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->whereDate('hire_date', '<=', self::END_DATE)
            ->orderBy('id')
            ->get(['id', 'user_id', 'hire_date', 'termination_date', 'contract_end_date', 'salary']);

        if ($employees->isEmpty()) {
            throw new RuntimeException('Store 45 has no active employees to seed.');
        }

        $withoutSalary = $employees->filter(fn (object $employee) => (float) ($employee->salary ?? 0) <= 0)
            ->pluck('id')->all();
        if ($withoutSalary !== []) {
            throw new RuntimeException(
                'Basic Payroll requires a positive daily salary. Missing salary for employee IDs: '
                . implode(', ', $withoutSalary),
            );
        }

        $this->operatingHours = $this->storeOperatingHours($store);

        $recordedBy = $this->recordedByUserId($employees);
        $paidMinutes = $this->standardPaidMinutes($store);
        $inserted = 0;
        $preserved = 0;

        DB::transaction(function () use ($employees, $recordedBy, $paidMinutes, &$inserted, &$preserved): void {
            foreach ($employees as $employee) {
                foreach (CarbonPeriod::create(self::START_DATE, self::END_DATE) as $periodDate) {
                    $date = CarbonImmutable::instance($periodDate)->setTimezone(self::TIMEZONE)->startOfDay();
                    if (!$this->employeeWasActiveOn($employee, $date)) {
                        continue;
                    }

                    if ($this->attendanceExists((int) $employee->id, $date)) {
                        $preserved++;
                        continue;
                    }

                    $schedule = $this->weeklySchedule((int) $employee->id, $date);
                    $holiday = $this->nonWorkingHoliday($date);
                    $attendance = $this->attendanceRow(
                        (int) $employee->id,
                        $date,
                        $schedule,
                        $holiday,
                        $recordedBy,
                        $paidMinutes,
                    );

                    DB::table('simple_employee_attendances')->insert($attendance);
                    $inserted++;
                }
            }
        });

        $this->command?->info(
            "Store 45 Basic Payroll attendance seeded: {$inserted} inserted, {$preserved} existing rows preserved.",
        );
    }

    private function targetStore(): object
    {
        $store = DB::table('stores as s')
            ->leftJoin('subscription_plans as p', 'p.id', '=', 's.subscription_tier')
            ->where('s.id', self::STORE_ID)
            ->whereNull('s.deleted_at')
            ->first(['s.id', 's.name', 's.settings', 'p.plan_key']);

        if (!$store) {
            throw new RuntimeException('Store 45 was not found.');
        }

        if (strtolower((string) $store->plan_key) !== 'simple') {
            throw new RuntimeException('Store 45 is not assigned to the Simple plan.');
        }

        return $store;
    }

    private function recordedByUserId($employees): ?int
    {
        $owner = DB::table('users as u')
            ->leftJoin('roles as r', 'r.id', '=', 'u.role_id')
            ->where('u.store_id', self::STORE_ID)
            ->where('u.is_active', true)
            ->whereIn('r.name', ['owner', 'store_owner'])
            ->orderBy('u.id')
            ->value('u.id');

        return $owner ? (int) $owner : ($employees->first()?->user_id ? (int) $employees->first()->user_id : null);
    }

    /** @return array{days: list<string>, opens_at: string, closes_at: string} */
    private function storeOperatingHours(object $store): array
    {
        $settings = json_decode((string) ($store->settings ?? ''), true);
        $hours = is_array($settings) ? ($settings['simple_operating_hours'] ?? null) : null;
        $days = is_array($hours) ? array_values(array_filter(array_map(
            fn ($day): string => strtolower(trim((string) $day)),
            $hours['days'] ?? [],
        ))) : [];
        $opensAt = is_array($hours) ? trim((string) ($hours['opens_at'] ?? '')) : '';
        $closesAt = is_array($hours) ? trim((string) ($hours['closes_at'] ?? '')) : '';

        if ($days === [] || $opensAt === '' || $closesAt === '') {
            throw new RuntimeException(
                'Store 45 must configure operating days, opening time, and closing time before attendance can be seeded.',
            );
        }

        return ['days' => $days, 'opens_at' => $opensAt, 'closes_at' => $closesAt];
    }

    private function standardPaidMinutes(object $store): int
    {
        $settings = json_decode((string) ($store->settings ?? ''), true);
        $settings = is_array($settings) ? $settings : [];
        $hours = (float) ($settings['hr_payroll_settings']['paidHours']
            ?? $settings['hr_workforce_rules']['dailyHours']
            ?? 8);

        return (int) round(max(1, min(24, $hours)) * 60);
    }

    private function employeeWasActiveOn(object $employee, CarbonImmutable $date): bool
    {
        if ($employee->hire_date && $date->lt(CarbonImmutable::parse($employee->hire_date, self::TIMEZONE))) {
            return false;
        }
        if ($employee->termination_date && $date->gt(CarbonImmutable::parse($employee->termination_date, self::TIMEZONE))) {
            return false;
        }

        return !$employee->contract_end_date
            || $date->lte(CarbonImmutable::parse($employee->contract_end_date, self::TIMEZONE));
    }

    private function attendanceExists(int $employeeId, CarbonImmutable $date): bool
    {
        return DB::table('simple_employee_attendances')
            ->where('store_id', self::STORE_ID)
            ->where('employee_id', $employeeId)
            ->whereDate('attendance_date', $date->toDateString())
            ->exists();
    }

    private function weeklySchedule(int $employeeId, CarbonImmutable $date): ?object
    {
        $schedule = DB::table('employee_weekly_schedules as schedule')
            ->leftJoin('shifts as shift', 'shift.id', '=', 'schedule.shift_id')
            ->where('schedule.employee_id', $employeeId)
            ->whereRaw('LOWER(schedule.day_of_week) = ?', [strtolower($date->englishDayOfWeek)])
            ->where(function ($query) use ($date): void {
                $query->whereNull('schedule.effective_from')
                    ->orWhereDate('schedule.effective_from', '<=', $date->toDateString());
            })
            ->where(function ($query) use ($date): void {
                $query->whereNull('schedule.effective_to')
                    ->orWhereDate('schedule.effective_to', '>=', $date->toDateString());
            })
            ->orderByDesc('schedule.effective_from')
            ->orderByDesc('schedule.id')
            ->first([
                'schedule.id',
                'schedule.is_off',
                'schedule.start_time',
                'schedule.end_time',
                'shift.start_time as shift_start_time',
                'shift.end_time as shift_end_time',
            ]);

        if ($schedule) {
            return $schedule;
        }

        $isWorkday = in_array(strtolower($date->englishDayOfWeek), $this->operatingHours['days'], true);

        return (object) [
            'id' => null,
            'is_off' => !$isWorkday,
            'start_time' => $this->operatingHours['opens_at'],
            'end_time' => $this->operatingHours['closes_at'],
            'shift_start_time' => null,
            'shift_end_time' => null,
        ];
    }

    private function nonWorkingHoliday(CarbonImmutable $date): ?object
    {
        return DB::table('holiday_schedules')
            ->whereDate('holiday_date', $date->toDateString())
            ->where('is_working_holiday', false)
            ->where(function ($query): void {
                $query->whereNull('store_id')->orWhere('store_id', self::STORE_ID);
            })
            ->orderByDesc('store_id')
            ->first(['name']);
    }

    private function attendanceRow(
        int $employeeId,
        CarbonImmutable $date,
        ?object $schedule,
        ?object $holiday,
        ?int $recordedBy,
        int $paidMinutes,
    ): array {
        $base = [
            'store_id' => self::STORE_ID,
            'employee_id' => $employeeId,
            'attendance_date' => $date->toDateString(),
            'recorded_by' => $recordedBy,
            'created_at' => $date->endOfDay(),
            'updated_at' => now(),
        ];

        if ($holiday) {
            return $base + $this->closedDay('holiday', $holiday->name);
        }

        if (!$schedule || (bool) $schedule->is_off) {
            return $base + $this->closedDay('day_off', 'Weekly schedule day off.');
        }

        $startTime = $schedule->start_time ?: $schedule->shift_start_time;
        $endTime = $schedule->end_time ?: $schedule->shift_end_time;
        if (!$startTime || !$endTime) {
            throw new RuntimeException(
                "Employee {$employeeId} has an incomplete weekly schedule on {$date->englishDayOfWeek}.",
            );
        }

        $scheduledStart = $this->dateTime($date, $startTime);
        $scheduledClose = $this->dateTime($date, $endTime);
        if ($scheduledClose->lte($scheduledStart)) {
            $scheduledClose = $scheduledClose->addDay();
        }

        $roll = $this->randomInt($employeeId, $date, 'status', 0, 99);
        if ($roll < 7) {
            return $base + $this->closedDay('absent', 'Randomized absence for the Basic Payroll demo.');
        }

        $isHalfDay = $roll >= 7 && $roll < 14;
        $isLate = !$isHalfDay && $roll >= 14 && $roll < 34;
        $hasOvertime = !$isHalfDay && $roll >= 94;
        $lateMinutes = $isLate ? $this->randomInt($employeeId, $date, 'late', 8, 40) : 0;
        $arrivalLead = $isLate ? $lateMinutes : -$this->randomInt($employeeId, $date, 'early', 0, 12);
        $clockIn = $scheduledStart->addMinutes($arrivalLead);
        $overtimeMinutes = $hasOvertime ? 60 : 0;
        $workedMinutes = $isHalfDay
            ? (int) round($paidMinutes / 2)
            : max(0, $paidMinutes - $lateMinutes + $overtimeMinutes);
        $clockOut = $isHalfDay
            ? $clockIn->addMinutes($workedMinutes)
            : $scheduledClose->addMinutes($overtimeMinutes);
        $notes = $isHalfDay
            ? 'Half-day attendance with complete clock records.'
            : ($isLate
                ? "Late arrival ({$lateMinutes} minutes)."
                : ($hasOvertime ? 'One hour of overtime for payroll demonstration.' : 'Completed scheduled workday.'));

        return $base + [
            'status' => 'present',
            'note' => self::NOTE_PREFIX . ' ' . $notes,
            'clock_in_at' => $clockIn,
            'clock_out_at' => $clockOut,
            'scheduled_close_at' => $scheduledClose,
            'worked_minutes' => $workedMinutes,
            'late_minutes' => $lateMinutes,
            'overtime_minutes' => $overtimeMinutes,
        ];
    }

    private function closedDay(string $status, string $note): array
    {
        return [
            'status' => $status,
            'note' => self::NOTE_PREFIX . ' ' . $note,
            'clock_in_at' => null,
            'clock_out_at' => null,
            'scheduled_close_at' => null,
            'worked_minutes' => 0,
            'late_minutes' => 0,
            'overtime_minutes' => 0,
        ];
    }

    private function dateTime(CarbonImmutable $date, string $time): CarbonImmutable
    {
        return CarbonImmutable::parse($date->toDateString() . ' ' . $time, self::TIMEZONE);
    }

    private function randomInt(int $employeeId, CarbonImmutable $date, string $salt, int $min, int $max): int
    {
        $hash = (int) sprintf('%u', crc32(implode('|', [
            self::STORE_ID,
            $employeeId,
            $date->toDateString(),
            $salt,
        ])));

        return $min + ($hash % ($max - $min + 1));
    }
}
