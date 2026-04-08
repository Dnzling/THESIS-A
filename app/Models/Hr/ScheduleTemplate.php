<?php

namespace App\Models\Hr;

use App\Models\Store\Store;
use App\Models\User;
use App\Models\Hr\ShiftAssignment;
use App\Models\Hr\ShiftSchedule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduleTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'schedule_templates';

    protected $fillable = [
        'name',
        'store_id',
        'pattern',
        'valid_from',
        'valid_to',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'pattern' => 'array',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function assignments()
    {
        return $this->hasMany(ShiftAssignment::class);
    }

    public function schedules()
    {
        return $this->hasMany(ShiftSchedule::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValidForDate($query, $date)
    {
        return $query->where('valid_from', '<=', $date)
                     ->where(function($q) use ($date) {
                         $q->where('valid_to', '>=', $date)
                           ->orWhereNull('valid_to');
                     });
    }

    // Methods
    public function generateSchedule($employeeId, $startDate, $endDate)
    {
        // Logic to generate schedule from template
        $schedules = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $dayOfWeek = strtolower($currentDate->format('l'));
            
            if (isset($this->pattern[$dayOfWeek])) {
                $shiftData = $this->pattern[$dayOfWeek];
                
                if ($shiftData['is_working']) {
                    $schedules[] = [
                        'employee_id' => $employeeId,
                        'shift_id' => $shiftData['shift_id'],
                        'schedule_date' => $currentDate->format('Y-m-d'),
                        'status' => 'scheduled'
                    ];
                }
            }
            
            $currentDate->addDay();
        }
        
        return $schedules;
    }
}