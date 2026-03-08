<?php
// app/Models/Hr/DeductionType.php

namespace App\Models\Hr;

use App\Models\Core\User;
use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeductionType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'deduction_types';

    protected $fillable = [
        'store_id',
        'code',
        'name',
        'description',
        'category',
        'frequency',
        'calculation_type',
        'percentage_value',
        'formula_data',
        'min_amount',
        'max_amount',
        'percentage_basis',
        'is_mandatory',
        'is_taxable',
        'is_active',
        'default_amount',
        'percentage_rate',
        'show_on_payslip',
        'sort_order',
        'created_by'
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'is_taxable' => 'boolean',
        'is_active' => 'boolean',
        'show_on_payslip' => 'boolean',
        'default_amount' => 'float',
        'percentage_value' => 'float',
        'min_amount' => 'float',
        'max_amount' => 'float',
        'percentage_rate' => 'float',
        'formula_data' => 'array',
        'sort_order' => 'integer'
    ];

    // Relationships

    // In DeductionType.php model
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    // Add store scope
    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function employeeDeductions()
    {
        return $this->hasMany(EmployeeDeduction::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeGovernment($query)
    {
        return $query->where('category', 'government');
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    public function scopeShownOnPayslip($query)
    {
        return $query->where('show_on_payslip', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Accessors
    public function getCategoryLabelAttribute()
    {
        $labels = [
            'government' => 'Government Mandated',
            'company' => 'Company Benefit',
            'loan' => 'Loan',
            'benefit' => 'Benefit',
            'other' => 'Other'
        ];

        return $labels[$this->category] ?? $this->category;
    }

    public function getCalculationTypeLabelAttribute()
    {
        $labels = [
            'fixed' => 'Fixed Amount',
            'percentage' => 'Percentage of Salary',
            'formula' => 'Formula Based'
        ];

        return $labels[$this->calculation_type] ?? $this->calculation_type;
    }

    public function getFormattedValueAttribute()
    {
        switch ($this->calculation_type) {
            case 'percentage':
                return $this->percentage_value . '% of ' . ucfirst($this->percentage_basis) . ' salary';

            case 'formula':
                return 'Complex formula';

            default:
                return $this->default_amount ? '₱' . number_format($this->default_amount, 2) : '₱0.00';
        }
    }

    public function getFormattedRangeAttribute()
    {
        if ($this->min_amount || $this->max_amount) {
            $min = $this->min_amount ? '₱' . number_format($this->min_amount, 2) : 'No min';
            $max = $this->max_amount ? '₱' . number_format($this->max_amount, 2) : 'No max';
            return "Min: {$min}, Max: {$max}";
        }
        return 'No limits';
    }

    public function getDisplayNameAttribute()
    {
        $name = $this->name;

        if ($this->calculation_type === 'percentage') {
            $name .= ' (' . $this->percentage_value . '%)';
        }

        return $name;
    }

    // Methods
    public function calculateDeduction($salary, $grossPay = null, $taxableIncome = null)
    {
        switch ($this->calculation_type) {
            case 'percentage':
                $basis = $this->getPercentageBasis($salary, $grossPay, $taxableIncome);
                $amount = $basis * ($this->percentage_value / 100);

                // Apply min/max limits
                if ($this->min_amount && $amount < $this->min_amount) {
                    $amount = $this->min_amount;
                }
                if ($this->max_amount && $amount > $this->max_amount) {
                    $amount = $this->max_amount;
                }

                return round($amount, 2);

            case 'formula':
                return $this->calculateFormula($salary);

            default:
                return $this->default_amount ?? 0;
        }
    }

    private function getPercentageBasis($salary, $grossPay, $taxableIncome)
    {
        switch ($this->percentage_basis) {
            case 'gross':
                return $grossPay ?? $salary;
            case 'taxable':
                return $taxableIncome ?? $salary;
            default: // 'basic'
                return $salary;
        }
    }

    private function calculateFormula($salary)
    {
        if (!$this->formula_data) {
            return 0;
        }

        // Example for SSS formula (brackets)
        if ($this->code === 'SSS' && isset($this->formula_data['brackets'])) {
            foreach ($this->formula_data['brackets'] as $bracket) {
                if ($salary >= $bracket['min'] && $salary <= $bracket['max']) {
                    return $bracket['amount'];
                }
            }
            return $this->formula_data['max_amount'] ?? 0;
        }

        return 0;
    }
}
