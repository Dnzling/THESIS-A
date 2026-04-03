<?php

namespace App\Models\Hr;

use App\Models\Hr\LeaveBalance;
use App\Models\Hr\Attendance;
use App\Models\Hr\Employee;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Leave extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'leaves';

    protected $fillable = [
        'employee_id',
        'leave_type',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'attachment_path',
        'is_paid',
        'deduct_from_balance',
        'handover_notes',
        'handover_to',
        'status',
        'approved_by',
        'rejected_reason',
        'approved_at'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'is_paid' => 'boolean',
        'deduct_from_balance' => 'boolean',
        'handover_notes' => 'array'
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function handoverTo()
    {
        return $this->belongsTo(Employee::class, 'handover_to');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_date', [$startDate, $endDate])
                     ->orWhereBetween('end_date', [$startDate, $endDate]);
    }

    // Accessors
    public function getDurationAttribute()
    {
        return $this->total_days . ' ' . ($this->total_days > 1 ? 'days' : 'day');
    }

    public function getStatusBadgeAttribute()
    {
        $colors = [
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'secondary'
        ];
        
        return "<span class='badge badge-{$colors[$this->status]}'>{$this->status}</span>";
    }

    // Methods
    public function approve($userId)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now()
        ]);

        if ($this->deduct_from_balance) {
            $this->deductFromBalance();
        }

        // Create attendance records for leave days
        $this->createLeaveAttendanceRecords();
    }

    public function reject($userId, $reason)
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $userId,
            'rejected_reason' => $reason,
            'approved_at' => now()
        ]);
    }

    protected function deductFromBalance()
    {
        $balance = LeaveBalance::where('employee_id', $this->employee_id)
            ->where('leave_type', $this->leave_type)
            ->where('year', $this->start_date->year)
            ->first();

        if ($balance) {
            $balance->used_days += $this->total_days;
            $balance->remaining_days = $balance->yearly_quota + $balance->carried_over - $balance->used_days - $balance->pending_days;
            $balance->save();
        }
    }

    protected function createLeaveAttendanceRecords()
    {
        $period = \Carbon\CarbonPeriod::create($this->start_date, $this->end_date);
        
        foreach ($period as $date) {
            Attendance::updateOrCreate(
                [
                    'employee_id' => $this->employee_id,
                    'attendance_date' => $date->format('Y-m-d')
                ],
                [
                    'status' => 'on_leave',
                    'notes' => "Leave: {$this->leave_type} - {$this->reason}"
                ]
            );
        }
    }
}