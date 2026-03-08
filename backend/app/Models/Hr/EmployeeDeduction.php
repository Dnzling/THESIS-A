<?php
// app/Models/Hr/EmployeeDeduction.php

namespace App\Models\Hr;

use App\Models\Hr\Employee;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeDeduction extends Model
{
    use SoftDeletes;

    protected $table = 'employee_deductions';

    protected $fillable = [
        'employee_id',
        'deduction_type_id',
        'effective_date',
        'end_date',
        'reference_number',
        'notes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function deductionType(): BelongsTo
    {
        return $this->belongsTo(DeductionType::class, 'deduction_type_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ==================== ACCESSORS (Get from DeductionType) ====================

    public function getAmountAttribute(): ?float
    {
        return $this->deductionType?->default_amount;
    }

    public function getFrequencyAttribute(): ?string
    {
        return $this->deductionType?->frequency;
    }

    public function getCalculationTypeAttribute(): ?string
    {
        return $this->deductionType?->calculation_type;
    }

    public function getPercentageValueAttribute(): ?float
    {
        return $this->deductionType?->percentage_value;
    }

    public function getPercentageBasisAttribute(): ?string
    {
        return $this->deductionType?->percentage_basis;
    }

    public function getIsMandatoryAttribute(): bool
    {
        return $this->deductionType?->is_mandatory ?? false;
    }

    public function getIsTaxableAttribute(): bool
    {
        return $this->deductionType?->is_taxable ?? false;
    }

    public function getShowOnPayslipAttribute(): bool
    {
        return $this->deductionType?->show_on_payslip ?? true;
    }

    public function getCategoryAttribute(): ?string
    {
        return $this->deductionType?->category;
    }

    public function getDeductionNameAttribute(): ?string
    {
        return $this->deductionType?->name;
    }

    public function getDeductionCodeAttribute(): ?string
    {
        return $this->deductionType?->code;
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->whereHas('deductionType', function ($q) {
                $q->where('is_active', true);
            });
    }

    public function scopeCurrent($query)
    {
        $today = now()->toDateString();
        
        return $query->where('effective_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $today);
            });
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    // ==================== METHODS ====================

    public function isCurrentlyActive(): bool
    {
        // Check if record is active
        if (!$this->is_active) {
            return false;
        }

        // Check if deduction type is active
        if (!$this->deductionType?->is_active) {
            return false;
        }

        // Check effective dates
        $today = now()->toDateString();

        if ($this->effective_date > $today) {
            return false; // Not yet effective
        }

        if ($this->end_date && $this->end_date < $today) {
            return false; // Already expired
        }

        return true;
    }

    public function calculateAmount(float $basicSalary = 0, float $grossSalary = 0): float
    {
        $type = $this->deductionType;

        if (!$type || !$this->isCurrentlyActive()) {
            return 0;
        }

        return match ($type->calculation_type) {
            'fixed' => (float) $type->default_amount,
            'percentage' => $this->calculatePercentage($basicSalary, $grossSalary),
            'formula' => $this->calculateFormula($basicSalary, $grossSalary),
            default => 0,
        };
    }

    private function calculatePercentage(float $basicSalary, float $grossSalary): float
    {
        $type = $this->deductionType;
        $percentage = (float) ($type?->percentage_value ?? 0);

        $basis = match ($type?->percentage_basis) {
            'basic' => $basicSalary,
            'gross' => $grossSalary,
            'taxable' => $grossSalary,
            default => $basicSalary,
        };

        $amount = $basis * ($percentage / 100);

        // Apply min/max limits
        if ($type?->min_amount && $amount < $type->min_amount) {
            $amount = $type->min_amount;
        }

        if ($type?->max_amount && $amount > $type->max_amount) {
            $amount = $type->max_amount;
        }

        return $amount;
    }

    private function calculateFormula(float $basicSalary, float $grossSalary): float
    {
        // Custom formula logic based on formula_data
        return 0;
    }
}