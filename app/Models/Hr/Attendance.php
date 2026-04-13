<?php

namespace App\Models\Hr;

use App\Models\Core\User;
use App\Models\Hr\OvertimeRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Attendance extends Model
{
    use SoftDeletes;

    protected $table = 'attendances';

    protected $fillable = [
        'employee_id',
        'shift_id',
        'schedule_id',
        'attendance_date',
        'clock_in',
        'clock_out',
        'clock_in_method',
        'clock_out_method',
        'clock_in_ip',
        'clock_out_ip',
        'clock_in_location',
        'clock_out_location',
        'break_start',
        'break_end',
        'break_minutes',
        'late_minutes',
        'early_departure_minutes',
        'overtime_minutes',
        'night_differential_minutes',
        'total_worked_minutes',
        'status',
        'is_ot_approved',
        'is_restday_work',
        'ot_approved_by',
        'ot_approved_at',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'break_start' => 'datetime',
        'break_end' => 'datetime',
        'is_ot_approved' => 'boolean',
        'is_restday_work' => 'boolean',
        'ot_approved_at' => 'datetime'
    ];

    // ==================== RELATIONSHIPS ====================


    public function overtimeRequest()
    {
        return $this->belongsTo(User::class);
    }
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ShiftSchedule::class, 'schedule_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function otApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ot_approved_by');
    }

    // ==================== ACCESSORS ====================

    public function getFormattedDateAttribute(): string
    {
        return $this->attendance_date->format('M d, Y');
    }

    public function getClockInTimeAttribute(): ?string
    {
        return $this->clock_in ? $this->clock_in->format('h:i A') : null;
    }

    public function getClockOutTimeAttribute(): ?string
    {
        return $this->clock_out ? $this->clock_out->format('h:i A') : null;
    }

    public function getTotalHoursWorkedAttribute(): float
    {
        return round($this->total_worked_minutes / 60, 2);
    }

    // ==================== SCOPES ====================

    public function scopeToday($query)
    {
        return $query->whereDate('attendance_date', now()->toDateString());
    }

    public function scopePresent($query)
    {
        return $query->whereIn('status', ['present', 'late']);
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    public function scopeLate($query)
    {
        return $query->where('status', 'late');
    }

    public function scopeByEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('attendance_date', [$startDate, $endDate]);
    }

    public function scopeByMonth($query, int $month, int $year)
    {
        return $query->whereMonth('attendance_date', $month)
            ->whereYear('attendance_date', $year);
    }

// ==================== METHODS ====================

    /**
     * Calculate late minutes based on shift start time
     */
    public function calculateLate(): void
    {
        if (!$this->shift || !$this->clock_in) {
            return;
        }

        $shiftStart = $this->getShiftStartCarbon();
        $clockIn = Carbon::parse($this->clock_in);

        if ($clockIn > $shiftStart) {
            $minutesLate = abs($shiftStart->diffInMinutes($clockIn, false));

            // If employee clocks in 120+ minutes after shift start, mark as half day.
            if ($minutesLate >= 120) {
                $this->late_minutes = $minutesLate;
                $this->status = 'half_day';
                $this->save();
                return;
            }
            $gracePeriod = $this->shift->grace_period_minutes ?? 15;

            if ($minutesLate > $gracePeriod) {
                $this->late_minutes = $minutesLate - $gracePeriod;
                if ($this->status === 'present') {
                    $this->status = 'late';
                }
            } else {
                $this->late_minutes = 0;
            }
        } else {
            $this->late_minutes = 0;
        }

        $this->save();
    }

    /**
     * Get shift start time as Carbon instance
     */
    protected function getShiftStartCarbon(): Carbon
    {
        $startTime = $this->shift->start_time;

        // Check if start_time already contains a date
        if (preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $startTime)) {
            return Carbon::parse($startTime);
        }

        // Just a time, concatenate with attendance date
        return Carbon::parse($this->attendance_date->format('Y-m-d') . ' ' . $startTime);
    }

    /**
     * Get shift end time as Carbon instance (handles overnight shifts).
     */
    protected function getShiftEndCarbon(): Carbon
    {
        $endTime = $this->shift->end_time;

        // Check if end_time already contains a date
        if (preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', (string) $endTime)) {
            return Carbon::parse($endTime);
        }

        $start = $this->getShiftStartCarbon();
        $end = Carbon::parse($this->attendance_date->format('Y-m-d') . ' ' . $endTime);

        // Overnight shift: end time is on the next day.
        if ($end->lessThanOrEqualTo($start)) {
            $end = $end->addDay();
        }

        return $end;
    }

    protected function appendNoteOnce(string $note): void
    {
        $note = trim($note);
        if ($note === '') {
            return;
        }

        $existing = (string) ($this->notes ?? '');
        if ($existing !== '' && str_contains(strtolower($existing), strtolower($note))) {
            return;
        }

        $this->notes = trim($existing === '' ? $note : ($existing . "\n" . $note));
    }

    /**
     * Calculate total worked minutes
     */
    public function calculateTotalWorked(): void
    {
        if (!$this->clock_in || !$this->clock_out) {
            return;
        }

        $clockIn = Carbon::parse($this->clock_in);
        $clockOut = Carbon::parse($this->clock_out);

        // Shift-aligned calculations (work hours count only when fully within the scheduled window).
        if ($this->shift) {
            $shiftStart = $this->getShiftStartCarbon();
            $shiftEnd = $this->getShiftEndCarbon();

            if ($clockIn->lt($shiftStart)) {
                $this->appendNoteOnce('EARLY');
            }

            if ($clockOut->gt($shiftEnd)) {
                $hasOvertimeRequest = OvertimeRequest::query()
                    ->where('attendance_id', $this->id)
                    ->whereIn('status', ['pending', 'approved'])
                    ->exists();

                if (!$hasOvertimeRequest) {
                    $this->appendNoteOnce('Overtime not included (no overtime request filed).');
                }
            }

            $isWithinShift = $clockIn->greaterThanOrEqualTo($shiftStart) && $clockOut->lessThanOrEqualTo($shiftEnd);
            if (!$isWithinShift) {
                $this->total_worked_minutes = 0;
                $this->overtime_minutes = 0;
                $this->night_differential_minutes = 0;
                $this->save();
                return;
            }
        }

        // Calculate total minutes between clock in and out
        $totalMinutes = $clockIn->diffInMinutes($clockOut, false);

        // Subtract break minutes if available
        $breakMinutes = $this->break_minutes ?? 0;
        $this->total_worked_minutes = max(0, $totalMinutes - $breakMinutes);

        // Overtime is tracked via overtime requests; don't infer from raw clock span here.
        $this->overtime_minutes = (int) ($this->overtime_minutes ?? 0);

        // Calculate night differential (10 PM to 6 AM)
        $this->calculateNightDifferential();

        $this->save();
    }

    /**
     * Calculate night differential hours (10 PM - 6 AM)
     */
    public function calculateNightDifferential(): void
    {
        if (!$this->clock_in || !$this->clock_out) {
            $this->night_differential_minutes = 0;
            return;
        }

        $nightStart = Carbon::parse($this->attendance_date->format('Y-m-d') . ' 22:00:00');
        $nightEnd = Carbon::parse($this->attendance_date->format('Y-m-d') . ' 23:59:59');
        $morningEnd = Carbon::parse($this->attendance_date->format('Y-m-d') . ' 06:00:00');

        $clockIn = Carbon::parse($this->clock_in);
        $clockOut = Carbon::parse($this->clock_out);

        $nightMinutes = 0;

        // Night shift portion (10 PM - 12 AM)
        if ($clockIn < $nightEnd && $clockOut > $nightStart) {
            $start = $clockIn < $nightStart ? $nightStart : $clockIn;
            $end = $clockOut > $nightEnd ? $nightEnd : $clockOut;
            if ($end > $start) {
                $nightMinutes += $start->diffInMinutes($end, false);
            }
        }

        // Early morning portion (12 AM - 6 AM)
        $nextDayMorning = Carbon::parse($this->attendance_date->format('Y-m-d') . ' 00:00:00')->addDay();
        if ($clockOut > $nextDayMorning && $clockOut <= $morningEnd->addDay()) {
            $start = $nextDayMorning;
            $end = $clockOut > $morningEnd->addDay() ? $morningEnd->addDay() : $clockOut;
            if ($end > $start) {
                $nightMinutes += $start->diffInMinutes($end, false);
            }
        }

        $this->night_differential_minutes = max(0, $nightMinutes);
    }

    /**
     * Check if attendance is for today
     */
    public function isToday(): bool
    {
        return $this->attendance_date->isToday();
    }

    /**
     * Check if employee was late
     */
    public function isLate(): bool
    {
        return $this->status === 'late' || $this->late_minutes > 0;
    }

    /**
     * Check if employee has overtime
     */
    public function hasOvertime(): bool
    {
        return $this->overtime_minutes > 0;
    }

    /**
     * Get status display label
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'present' => 'Present',
            'absent' => 'Absent',
            'late' => 'Late',
            'half_day' => 'Half Day',
            'on_leave' => 'On Leave',
            'holiday' => 'Holiday'
        ];

        return $labels[$this->status] ?? $this->status;
    }
}
