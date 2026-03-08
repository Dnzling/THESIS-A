<?php

namespace App\Models\Hr;

use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HolidaySchedule extends Model
{
    use HasFactory;

    protected $table = 'holiday_schedules';

    protected $fillable = [
        'name',
        'holiday_date',
        'holiday_type',
        'rate_multiplier',
        'is_working_holiday',
        'store_id',
        'description'
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'is_working_holiday' => 'boolean',
        'rate_multiplier' => 'decimal:2'
    ];

    // Relationships
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    // Scopes
    public function scopeForDate($query, $date)
    {
        return $query->where('holiday_date', $date);
    }

    public function scopeForYear($query, $year)
    {
        return $query->whereYear('holiday_date', $year);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('holiday_type', $type);
    }

    public function scopeWorkingHolidays($query)
    {
        return $query->where('is_working_holiday', true);
    }

    // Accessors
    public function getFormattedDateAttribute()
    {
        return $this->holiday_date->format('F d, Y');
    }

    public function getRateMultiplierFormattedAttribute()
    {
        return $this->rate_multiplier . 'x';
    }
}