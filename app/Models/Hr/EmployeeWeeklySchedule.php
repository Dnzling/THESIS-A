<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeWeeklySchedule extends Model
{
    use HasFactory;

    protected $table = 'employee_weekly_schedules';

    protected $fillable = [
        'employee_id',
        'day_of_week',
        'shift_id',
        'start_time',
        'end_time',
        'is_off',
        'effective_from',
        'effective_to',
        'notes',
    ];

    protected $casts = [
        'is_off' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }
}
