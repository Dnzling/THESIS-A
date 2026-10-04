<?php

namespace App\Services\Hr;

use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeWeeklySchedule;
use App\Models\Hr\Shift;
use App\Models\Hr\ShiftAssignment;
use App\Models\Hr\ShiftSchedule;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class AttendanceClockInService
{
    public function resolveSchedule(Employee $employee, string $date, Carbon $clockInAt, ?int $actorId = null): array
    {
        $dailySchedule = ShiftSchedule::query()
            ->with('shift')
            ->where('employee_id', $employee->id)
            ->whereDate('schedule_date', $date)
            ->first();

        if ($dailySchedule?->status === 'cancelled') {
            return ['schedule' => $dailySchedule, 'shift' => null, 'start_time' => null, 'is_rest_day' => false];
        }

        if ($dailySchedule?->shift) {
            return [
                'schedule' => $dailySchedule,
                'shift' => $dailySchedule->shift,
                'start_time' => $dailySchedule->metadata['start_time'] ?? $dailySchedule->shift->start_time,
                'is_rest_day' => false,
            ];
        }

        $weekday = strtolower($clockInAt->format('l'));
        $weekly = EmployeeWeeklySchedule::query()
            ->with('shift')
            ->where('employee_id', $employee->id)
            ->where('day_of_week', $weekday)
            ->where(fn ($query) => $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $date))
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date))
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();

        if ($weekly) {
            if ($weekly->is_off) {
                return ['schedule' => null, 'shift' => null, 'start_time' => null, 'is_rest_day' => true];
            }

            if ($weekly->shift) {
                $dailySchedule = $this->materializeSchedule(
                    $employee,
                    $date,
                    $weekly->shift,
                    null,
                    $actorId,
                    [
                        'weekly_schedule_id' => $weekly->id,
                        'start_time' => $weekly->start_time ?: $weekly->shift->start_time,
                        'end_time' => $weekly->end_time ?: $weekly->shift->end_time,
                    ]
                );

                return [
                    'schedule' => $dailySchedule,
                    'shift' => $weekly->shift,
                    'start_time' => $weekly->start_time ?: $weekly->shift->start_time,
                    'is_rest_day' => false,
                ];
            }
        }

        $assignment = ShiftAssignment::query()
            ->with('shift')
            ->where('employee_id', $employee->id)
            ->active($date)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get()
            ->first(fn (ShiftAssignment $candidate) => $this->assignmentWorksOn($candidate, $weekday));

        if ($assignment?->shift) {
            $dailySchedule = $this->materializeSchedule(
                $employee,
                $date,
                $assignment->shift,
                $assignment,
                $actorId,
            );

            return [
                'schedule' => $dailySchedule,
                'shift' => $assignment->shift,
                'start_time' => $assignment->shift->start_time,
                'is_rest_day' => false,
            ];
        }

        $hasActiveAssignment = ShiftAssignment::query()->where('employee_id', $employee->id)->active($date)->exists();
        if ($hasActiveAssignment || ($weekly && $weekly->is_off)) {
            return ['schedule' => null, 'shift' => null, 'start_time' => null, 'is_rest_day' => true];
        }

        return ['schedule' => null, 'shift' => null, 'start_time' => null, 'is_rest_day' => false];
    }

    public function calculateStatus(
        array $resolved,
        CarbonInterface $clockInAt,
        string $date,
        string $timezone,
        array $attendanceRules = [],
    ): array {
        if ($resolved['is_rest_day']) {
            return ['status' => 'present', 'late_minutes' => 0];
        }

        if (!$resolved['shift'] || !$resolved['start_time']) {
            return ['status' => 'unscheduled', 'late_minutes' => 0];
        }

        $clockInAt = Carbon::instance($clockInAt)->setTimezone($timezone);
        $shiftStart = $this->combineDateAndTime($date, $resolved['start_time'], $timezone);
        $minutesAfterStart = (int) max(0, $shiftStart->diffInMinutes($clockInAt, false));
        $shiftGrace = (int) ($resolved['shift']->grace_period_minutes ?? 15);
        $gracePeriod = array_key_exists('gracePeriod', $attendanceRules)
            ? max(0, (int) $attendanceRules['gracePeriod'])
            : $shiftGrace;
        $lateMinutes = $minutesAfterStart > $gracePeriod ? $minutesAfterStart - $gracePeriod : 0;

        return [
            'status' => $lateMinutes >= 120 ? 'half_day' : ($lateMinutes > 0 ? 'late' : 'present'),
            'late_minutes' => $lateMinutes,
        ];
    }

    private function assignmentWorksOn(ShiftAssignment $assignment, string $weekday): bool
    {
        $pattern = is_array($assignment->recurring_pattern) ? $assignment->recurring_pattern : [];
        $workingDays = $pattern['week_days'] ?? $pattern['days'] ?? $assignment->shift?->week_days ?? [];

        if (!is_array($workingDays) || $workingDays === []) {
            return true;
        }

        return in_array($weekday, array_map(fn ($day) => strtolower((string) $day), $workingDays), true);
    }

    private function materializeSchedule(
        Employee $employee,
        string $date,
        Shift $shift,
        ?ShiftAssignment $assignment,
        ?int $actorId,
        array $metadata = [],
    ): ShiftSchedule {
        return ShiftSchedule::firstOrCreate(
            ['employee_id' => $employee->id, 'schedule_date' => $date],
            [
                'shift_id' => $shift->id,
                'assignment_id' => $assignment?->id,
                'generation_method' => 'manual',
                'status' => 'scheduled',
                'assigned_by' => $actorId,
                'metadata' => $metadata,
            ],
        )->load('shift');
    }

    private function combineDateAndTime(string $date, mixed $time, string $timezone): Carbon
    {
        $timeValue = $time instanceof CarbonInterface ? $time->format('H:i:s') : (string) $time;
        if (preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $timeValue)) {
            return Carbon::parse($timeValue, $timezone);
        }

        return Carbon::parse($date . ' ' . $timeValue, $timezone);
    }

}
