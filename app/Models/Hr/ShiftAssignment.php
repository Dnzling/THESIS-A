<?php

namespace App\Models\Hr;

use App\Models\Hr\Shift;
use App\Models\Hr\ScheduleTemplate;
use App\Models\Hr\ShiftSchedule;
use App\Models\Hr\Employee;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'shift_assignments';

    protected $fillable = [
        'employee_id',
        'shift_id',
        'template_id',
        'start_date',
        'end_date',
        'assignment_type',
        'cover_for',
        'recurring_pattern',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'recurring_pattern' => 'array'
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

    public function template()
    {
        return $this->belongsTo(ScheduleTemplate::class);
    }

    public function coverFor()
    {
        return $this->belongsTo(Employee::class, 'cover_for');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function schedules()
    {
        return $this->hasMany(ShiftSchedule::class, 'assignment_id');
    }

    // Scopes
    public function scopeActive($query, $date = null)
    {
        $date = $date ?? now();
        return $query->where('start_date', '<=', $date)
                     ->where(function($q) use ($date) {
                         $q->where('end_date', '>=', $date)
                           ->orWhereNull('end_date');
                     });
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopePermanent($query)
    {
        return $query->where('assignment_type', 'permanent');
    }

    // Accessors
    public function getIsActiveAttribute()
    {
        $today = now()->startOfDay();
        return $this->start_date <= $today && 
               ($this->end_date === null || $this->end_date >= $today);
    }
}