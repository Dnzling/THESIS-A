<?php

namespace App\Models\Hr;

use App\Models\Store\Store;
use App\Models\Hr\Employee;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveBalance extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'leave_balances';

    protected $fillable = [
        'store_id',
        'employee_id',
        'created_by',
        'updated_by',
        'leave_type',
        'yearly_quota',
        'used_days',
        'pending_days',
        'remaining_days',
        'carried_over',
        'expired_days',
        'year',
        'expiry_date',
        'status',
        'notes',
        'deleted_by'
    ];

    protected $casts = [
        'year' => 'integer',
        'expiry_date' => 'date',
        'yearly_quota' => 'float',
        'used_days' => 'float',
        'pending_days' => 'float',
        'remaining_days' => 'float',
        'carried_over' => 'float',
        'expired_days' => 'float'
    ];

    // Relationships
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('leave_type', $type);
    }

    // Methods
    public function updateRemainingDays()
    {
        $this->remaining_days = $this->yearly_quota + $this->carried_over - $this->used_days - $this->pending_days;
        $this->save();
    }

    public function addPendingDays($days)
    {
        $this->pending_days += $days;
        $this->updateRemainingDays();
    }

    public function removePendingDays($days)
    {
        $this->pending_days -= $days;
        if ($this->pending_days < 0) {
            $this->pending_days = 0;
        }
        $this->updateRemainingDays();
    }

    public function addUsedDays($days)
    {
        $this->used_days += $days;
        $this->updateRemainingDays();
    }

    public function carryOverTo($nextYear)
    {
        $maxCarryOver = 5; // Configurable
        
        $carryOverAmount = min($this->remaining_days, $maxCarryOver);
        
        if ($carryOverAmount > 0) {
            $nextYearBalance = self::firstOrCreate(
                [
                    'employee_id' => $this->employee_id,
                    'leave_type' => $this->leave_type,
                    'year' => $nextYear
                ],
                [
                    'store_id' => $this->store_id,
                    'yearly_quota' => 0,
                    'carried_over' => 0
                ]
            );
            
            $nextYearBalance->carried_over += $carryOverAmount;
            $nextYearBalance->updateRemainingDays();
        }
        
        // Mark expired days
        $expiredAmount = $this->remaining_days - $carryOverAmount;
        if ($expiredAmount > 0) {
            $this->expired_days = $expiredAmount;
        }
        
        $this->status = 'carried_over';
        $this->save();
    }
}