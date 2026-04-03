<?php

namespace App\Models\Hr;

use App\Models\Hr\Shift;
use App\Models\Hr\ShiftAssignment;
use App\Models\Hr\ScheduleTemplate;
use App\Models\Hr\Attendance;
use App\Models\Hr\ShiftSwapRequest;
use App\Models\Hr\Employee;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'shift_schedules';

    protected $fillable = [
        'employee_id',
        'shift_id',
        'assignment_id',
        'template_id',
        'schedule_date',
        'generation_method',
        'status',
        'notes',
        'metadata',
        'assigned_by'
    ];

    protected $casts = [
        'schedule_date' => 'date',
        'metadata' => 'array'
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function assignment()
    {
        return $this->belongsTo(ShiftAssignment::class);
    }

    public function template()
    {
        return $this->belongsTo(ScheduleTemplate::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function attendance()
    {
        return $this->hasOne(Attendance::class, 'schedule_id');
    }

    public function swapRequests()
    {
        return $this->hasMany(ShiftSwapRequest::class, 'requestor_schedule_id');
    }

    public function swapRequestsAsRequestor()
    {
        return $this->hasMany(ShiftSwapRequest::class, 'requestor_schedule_id');
    }

    public function swapRequestsAsReceiver()
    {
        return $this->hasMany(ShiftSwapRequest::class, 'receiver_schedule_id');
    }

    // Scopes
    public function scopeForDate($query, $date)
    {
        return $query->where('schedule_date', $date);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('schedule_date', [$startDate, $endDate]);
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Accessors
    public function getDateFormattedAttribute()
    {
        return $this->schedule_date->format('M d, Y');
    }

    public function getDayNameAttribute()
    {
        return $this->schedule_date->format('l');
    }

    // Methods
    public function markAsCompleted()
    {
        $this->update(['status' => 'completed']);
    }

    public function cancel()
    {
        $this->update(['status' => 'cancelled']);
    }

    public function canBeSwapped()
    {
        return $this->status === 'scheduled' && 
               $this->schedule_date->isFuture();
    }
}