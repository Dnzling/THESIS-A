<?php
// app/Models/Hr/Payroll.php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    protected $table = 'payrolls';

    protected $fillable = [
        'employee_id',
        'pay_period_id',
        'base_salary',
        'overtime_hours',
        'overtime_amount',
        'deductions_total',
        'bonuses_total',
        'allowances_total',
        'tax_amount',
        'net_salary',
        'late_deduction',
        'late_minutes',
        'late_occurrences',
        'status',
        'payment_date',
        'payment_method',
        'reference_number',
        'notes',
        'approved_by',
        'approved_at',
        'paid_by',
        'paid_at',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'deductions_total' => 'decimal:2',
        'bonuses_total' => 'decimal:2',
        'allowances_total' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'late_deduction' => 'decimal:2',
        'payment_date' => 'date',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'created_at' => 'datetime'   
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payPeriod(): BelongsTo
    {
        return $this->belongsTo(PayPeriod::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function paidBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'paid_by');
    }

    public function scopeForPeriod($query, $periodId)
    {
        return $query->where('pay_period_id', $periodId);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function calculateNetSalary()
    {
        return ($this->base_salary + $this->overtime_amount + $this->bonuses_total + $this->allowances_total)
            - ($this->deductions_total + $this->tax_amount);
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->whereHas('employee', function ($q) use ($storeId) {
            $q->where('store_id', $storeId);
        });
    }

    public function scopeByUserStore($query)
    {
        $user = auth()->user();
        if ($user && $user->store_id) {
            return $query->whereHas('employee', function ($q) use ($user) {
                $q->where('store_id', $user->store_id);
            });
        }
        return $query->whereRaw('1 = 0'); // Return empty or throw exception
    }
}
