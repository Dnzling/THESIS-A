<?php

namespace App\Models\Hr;

use App\Models\Hr\Attendance;
use App\Models\Hr\Employee;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeRequest extends Model
{
    use HasFactory;

    protected $table = 'overtime_requests';

    protected $fillable = [
        'employee_id',
        'attendance_id',
        'ot_start',
        'ot_end',
        'ot_minutes',
        'ot_type',
        'rate_multiplier',
        'reason',
        'status',
        'approved_by',
        'rejection_reason',
        'approved_at'
    ];

    protected $casts = [
        'ot_start' => 'datetime',
        'ot_end' => 'datetime',
        'approved_at' => 'datetime',
        'rate_multiplier' => 'decimal:2'
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
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
        return $query->whereBetween('ot_start', [$startDate, $endDate]);
    }

    // Accessors
    public function getDurationFormattedAttribute()
    {
        $hours = floor($this->ot_minutes / 60);
        $minutes = $this->ot_minutes % 60;
        return sprintf('%02d:%02d', $hours, $minutes);
    }

    public function getStatusBadgeAttribute()
    {
        $colors = [
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger'
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

        // Update attendance OT approval
        if ($this->attendance) {
            $this->attendance->update([
                'is_ot_approved' => true,
                'ot_approved_by' => $userId,
                'ot_approved_at' => now()
            ]);
        }
    }

    public function reject($userId, $reason)
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $userId,
            'rejection_reason' => $reason,
            'approved_at' => now()
        ]);
    }
}
