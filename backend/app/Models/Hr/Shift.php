<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Store\Store;
use App\Models\Hr\ShiftAssignment;
use App\Models\Hr\ShiftSchedule;
use App\Models\Hr\Attendance;

class Shift extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'shifts';

    protected $fillable = [
        'name',
        'code',
        'start_time',
        'end_time',
        'break_start',
        'break_end',
        'total_hours',
        'shift_type',
        'week_days',
        'grace_period_minutes',
        'has_night_diff',
        'night_diff_rate',
        'min_employees_required',
        'color',
        'is_active',
        'store_id',
        'description'
    ];

    protected $casts = [
        'week_days' => 'array',
        'has_night_diff' => 'boolean',
        'is_active' => 'boolean',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'break_start' => 'datetime:H:i',
        'break_end' => 'datetime:H:i'
    ];

    // Relationships
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function assignments()
    {
        return $this->hasMany(ShiftAssignment::class);
    }

    public function schedules()
    {
        return $this->hasMany(ShiftSchedule::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('shift_type', $type);
    }

    // Accessors
    public function getFormattedTimeAttribute()
    {
        return date('h:i A', strtotime($this->start_time)) . ' - ' . 
               date('h:i A', strtotime($this->end_time));
    }

    public function getDurationAttribute()
    {
        $start = strtotime($this->start_time);
        $end = strtotime($this->end_time);
        $hours = ($end - $start) / 3600;
        return number_format($hours, 2) . ' hours';
    }
}