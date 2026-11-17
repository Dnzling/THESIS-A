<?php

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class NorthwoodOctoberNovemberAttendanceSeeder extends Seeder
{
    private const STORE_ID = 1;
    private const APPROVER_EMPLOYEE_ID = 3;
    private const START_DATE = '2026-10-01';
    private const END_DATE = '2026-11-10';
    private const TIMEZONE = 'Asia/Manila';
    private const NOTE_PREFIX = '[Northwood attendance seed]';

    public function run(): void
    {
        $this->assertTargetStore();
        $this->assertActiveShift();

        $approverUserId = $this->approverUserId();
        $employees = DB::table('employees')
            ->where('store_id', self::STORE_ID)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->whereDate('hire_date', '<=', self::END_DATE)
            ->orderBy('id')
            ->get(['id', 'user_id', 'branch_id', 'hire_date', 'termination_date', 'contract_end_date']);

        if ($employees->isEmpty()) {
            throw new RuntimeException('Northwood Living has no active employees to seed.');
        }

        $inserted = 0;
        $skipped = 0;

        DB::transaction(function () use ($employees, $approverUserId, &$inserted, &$skipped): void {
            foreach ($employees as $employee) {
                foreach (CarbonPeriod::create(self::START_DATE, self::END_DATE) as $periodDate) {
                    $date = CarbonImmutable::instance($periodDate)->setTimezone(self::TIMEZONE)->startOfDay();

                    if (!$this->employeeWasActiveOn($employee, $date)) {
                        continue;
                    }

                    if ($this->attendanceExists((int) $employee->id, $date)) {
                        $skipped++;
                        continue;
                    }

                    $holiday = $this->nonWorkingHoliday($date);
                    $schedule = $this->resolveSchedule((int) $employee->id, $date, $approverUserId);

                    if (!$holiday && !$schedule) {
                        continue;
                    }

                    $attendance = $holiday
                        ? $this->holidayAttendance((int) $employee->id, $schedule, $date, $holiday, $approverUserId)
                        : $this->randomAttendance((int) $employee->id, $schedule, $date, $approverUserId);

                    DB::table('attendances')->insert($attendance);
                    $inserted++;
                }
            }
        });

        $this->command?->info("Northwood attendance seeded: {$inserted} inserted, {$skipped} existing rows preserved.");
    }

    private function assertTargetStore(): void
    {
        $storeName = DB::table('stores')->where('id', self::STORE_ID)->value('name');

        if ($storeName !== 'Northwood Living') {
            throw new RuntimeException('Store 1 is not Northwood Living; attendance was not seeded.');
        }
    }

    private function approverUserId(): int
    {
        $userId = DB::table('employees')
            ->where('id', self::APPROVER_EMPLOYEE_ID)
            ->where('store_id', self::STORE_ID)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->value('user_id');

        if (!$userId) {
            throw new RuntimeException('Employee 3 is unavailable as the attendance approver.');
        }

        return (int) $userId;
    }

    private function assertActiveShift(): void
    {
        $exists = DB::table('shifts')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->where(function ($query): void {
                $query->where('store_id', self::STORE_ID)->orWhereNull('store_id');
            })
            ->exists();

        if (!$exists) {
            throw new RuntimeException('Northwood Living has no active store or global shift.');
        }
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
        return DB::table('attendances')
            ->where('employee_id', $employeeId)
            ->whereDate('attendance_date', $date->toDateString())
            ->exists();
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
            ->first(['id', 'name']);
    }

    private function resolveSchedule(int $employeeId, CarbonImmutable $date, int $approverUserId): ?object
    {
        $existing = DB::table('shift_schedules as ss')
            ->join('shifts as s', 's.id', '=', 'ss.shift_id')
            ->where('ss.employee_id', $employeeId)
            ->whereDate('ss.schedule_date', $date->toDateString())
            ->whereNull('ss.deleted_at')
            ->where('ss.status', '!=', 'cancelled')
            ->first($this->scheduleColumns());

        if ($existing) {
            return $existing;
        }

        $shift = DB::table('shift_assignments as sa')
            ->join('shifts as s', 's.id', '=', 'sa.shift_id')
            ->where('sa.employee_id', $employeeId)
            ->whereDate('sa.start_date', '<=', $date->toDateString())
            ->where(function ($query) use ($date): void {
                $query->whereNull('sa.end_date')->orWhereDate('sa.end_date', '>=', $date->toDateString());
            })
            ->whereNull('sa.deleted_at')
            ->whereNull('s.deleted_at')
            ->where('s.is_active', true)
            ->orderByDesc('sa.start_date')
            ->first([
                's.id as shift_id', 's.start_time', 's.end_time', 's.break_start', 's.break_end',
                's.week_days', 's.grace_period_minutes', 'sa.id as assignment_id',
            ]);

        $shift ??= DB::table('shifts as s')
            ->whereNull('s.deleted_at')
            ->where('s.is_active', true)
            ->where(function ($query): void {
                $query->where('s.store_id', self::STORE_ID)->orWhereNull('s.store_id');
            })
            ->orderByRaw('CASE WHEN s.store_id = ? THEN 0 ELSE 1 END', [self::STORE_ID])
            ->orderBy('s.id')
            ->first([
                's.id as shift_id', 's.start_time', 's.end_time', 's.break_start', 's.break_end',
                's.week_days', 's.grace_period_minutes', DB::raw('NULL as assignment_id'),
            ]);

        if (!$shift || !$this->isShiftWorkday($shift->week_days, $date)) {
            return null;
        }

        DB::table('shift_schedules')->insertOrIgnore([
            'employee_id' => $employeeId,
            'shift_id' => $shift->shift_id,
            'assignment_id' => $shift->assignment_id,
            'template_id' => null,
            'schedule_date' => $date->toDateString(),
            'generation_method' => 'bulk',
            'status' => 'completed',
            'notes' => self::NOTE_PREFIX . ' Generated for payroll attendance history.',
            'metadata' => json_encode(['source' => 'northwood_attendance_seed']),
            'assigned_by' => $approverUserId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('shift_schedules as ss')
            ->join('shifts as s', 's.id', '=', 'ss.shift_id')
            ->where('ss.employee_id', $employeeId)
            ->whereDate('ss.schedule_date', $date->toDateString())
            ->whereNull('ss.deleted_at')
            ->first($this->scheduleColumns());
    }

    /** @return list<string> */
    private function scheduleColumns(): array
    {
        return [
            'ss.id as schedule_id', 's.id as shift_id', 's.start_time', 's.end_time',
            's.break_start', 's.break_end', 's.week_days', 's.grace_period_minutes',
        ];
    }

    private function isShiftWorkday(?string $weekDays, CarbonImmutable $date): bool
    {
        $days = json_decode((string) $weekDays, true);
        if (!is_array($days) || $days === []) {
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        }

        return in_array(strtolower($date->englishDayOfWeek), array_map('strtolower', $days), true);
    }

    private function holidayAttendance(
        int $employeeId,
        ?object $schedule,
        CarbonImmutable $date,
        object $holiday,
        int $approverUserId,
    ): array {
        return $this->baseAttendance($employeeId, $schedule, $date, $approverUserId) + [
            'clock_in' => null,
            'clock_out' => null,
            'clock_in_method' => null,
            'clock_out_method' => null,
            'break_start' => null,
            'break_end' => null,
            'break_minutes' => 0,
            'late_minutes' => 0,
            'early_departure_minutes' => 0,
            'overtime_minutes' => 0,
            'night_differential_minutes' => 0,
            'total_worked_minutes' => 0,
            'status' => 'holiday',
            'notes' => self::NOTE_PREFIX . ' ' . $holiday->name,
        ];
    }

    private function randomAttendance(int $employeeId, object $schedule, CarbonImmutable $date, int $approverUserId): array
    {
        $roll = $this->randomInt($employeeId, $date, 'status', 0, 99);
        $base = $this->baseAttendance($employeeId, $schedule, $date, $approverUserId);

        if ($roll < 5) {
            return $base + $this->emptyDay('absent', 'Randomized absence for payroll testing.');
        }

        $shiftStart = $this->dateTime($date, $schedule->start_time);
        $shiftEnd = $this->dateTime($date, $schedule->end_time);
        if ($shiftEnd->lte($shiftStart)) {
            $shiftEnd = $shiftEnd->addDay();
        }

        if ($roll < 10) {
            $clockIn = $shiftStart->addMinutes($this->randomInt($employeeId, $date, 'half-in', 0, 10));
            $clockOut = $clockIn->addHours(4);

            return $base + $this->workedDay(
                $clockIn,
                $clockOut,
                null,
                null,
                0,
                max(0, $shiftStart->diffInMinutes($clockIn, false)),
                max(0, $clockOut->diffInMinutes($shiftEnd, false)),
                240,
                'half_day',
                'Randomized half-day attendance for payroll testing.',
            );
        }

        $isLate = $roll < 30;
        $grace = max(0, (int) ($schedule->grace_period_minutes ?? 0));
        $arrivalOffset = $isLate
            ? $this->randomInt($employeeId, $date, 'late', $grace + 1, $grace + 45)
            : $this->randomInt($employeeId, $date, 'arrival', -12, $grace);
        $departureOffset = $this->randomInt($employeeId, $date, 'departure', 0, 15);
        $clockIn = $shiftStart->addMinutes($arrivalOffset);
        $clockOut = $shiftEnd->subMinutes($departureOffset);
        $breakStart = $this->dateTime($date, $schedule->break_start ?: '12:00:00');
        $breakEnd = $this->dateTime($date, $schedule->break_end ?: '13:00:00');
        $breakMinutes = max(0, $breakStart->diffInMinutes($breakEnd, false));
        $workedMinutes = max(0, $clockIn->diffInMinutes($clockOut, false) - $breakMinutes);
        $lateMinutes = max(0, $shiftStart->diffInMinutes($clockIn, false));

        return $base + $this->workedDay(
            $clockIn,
            $clockOut,
            $breakStart,
            $breakEnd,
            $breakMinutes,
            $lateMinutes,
            max(0, $clockOut->diffInMinutes($shiftEnd, false)),
            $workedMinutes,
            $isLate ? 'late' : 'present',
            $isLate ? "Randomized late arrival ({$lateMinutes} minutes)." : null,
        );
    }

    private function baseAttendance(int $employeeId, ?object $schedule, CarbonImmutable $date, int $approverUserId): array
    {
        return [
            'employee_id' => $employeeId,
            'shift_id' => $schedule?->shift_id,
            'schedule_id' => $schedule?->schedule_id,
            'attendance_date' => $date->toDateString(),
            'clock_in_ip' => null,
            'clock_out_ip' => null,
            'clock_in_location' => 'Northwood Living',
            'clock_out_location' => 'Northwood Living',
            'is_ot_approved' => false,
            'is_restday_work' => false,
            'ot_approved_by' => null,
            'ot_approved_at' => null,
            'approved_by' => $approverUserId,
            'approved_at' => $date->endOfDay(),
            'created_at' => $date->endOfDay(),
            'updated_at' => now(),
        ];
    }

    private function emptyDay(string $status, string $note): array
    {
        return [
            'clock_in' => null,
            'clock_out' => null,
            'clock_in_method' => null,
            'clock_out_method' => null,
            'break_start' => null,
            'break_end' => null,
            'break_minutes' => 0,
            'late_minutes' => 0,
            'early_departure_minutes' => 0,
            'overtime_minutes' => 0,
            'night_differential_minutes' => 0,
            'total_worked_minutes' => 0,
            'status' => $status,
            'notes' => self::NOTE_PREFIX . ' ' . $note,
        ];
    }

    private function workedDay(
        CarbonImmutable $clockIn,
        CarbonImmutable $clockOut,
        ?CarbonImmutable $breakStart,
        ?CarbonImmutable $breakEnd,
        int $breakMinutes,
        int $lateMinutes,
        int $earlyDepartureMinutes,
        int $workedMinutes,
        string $status,
        ?string $note,
    ): array {
        return [
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'clock_in_method' => 'manual',
            'clock_out_method' => 'manual',
            'break_start' => $breakStart,
            'break_end' => $breakEnd,
            'break_minutes' => $breakMinutes,
            'late_minutes' => $lateMinutes,
            'early_departure_minutes' => $earlyDepartureMinutes,
            'overtime_minutes' => 0,
            'night_differential_minutes' => 0,
            'total_worked_minutes' => $workedMinutes,
            'status' => $status,
            'notes' => self::NOTE_PREFIX . ($note ? ' ' . $note : ''),
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
