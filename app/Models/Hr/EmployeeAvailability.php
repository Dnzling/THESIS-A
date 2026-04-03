<?php

namespace App\Models\Hr;

use App\Models\Hr\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeAvailability extends Model
{
    use HasFactory;

    protected $table = 'employee_availability';

    protected $fillable = [
        'employee_id',
        'day_of_week',
        'available_from',
        'available_to',
        'is_available',
        'effective_from',
        'effective_to',
        'notes'
    ];

    protected $casts = [
        'available_from' => 'datetime:H:i',
        'available_to' => 'datetime:H:i',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_available' => 'boolean'
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Scopes
    public function scopeActive($query, $date = null)
    {
        $date = $date ?? now();
        return $query->where('effective_from', '<=', $date)
                     ->where(function($q) use ($date) {
                         $q->where('effective_to', '>=', $date)
                           ->orWhereNull('effective_to');
                     });
    }

    public function scopeByDay($query, $day)
    {
        return $query->where('day_of_week', $day);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    // Methods
    public function isAvailableForTime($time)
    {
        if (!$this->is_available) {
            return false;
        }
        
        $time = date('H:i', strtotime($time));
        return $time >= $this->available_from && $time <= $this->available_to;
    }
}