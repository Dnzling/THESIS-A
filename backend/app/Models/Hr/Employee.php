<?php

namespace App\Models\Hr;

use App\Models\Core\Role;
use App\Models\Core\User;
use App\Models\Store\Branch;
use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $table = 'employees';

    protected $primaryKey = 'id'; // This is the numeric primary key
    public $incrementing = true; // Auto-incrementing
    protected $keyType = 'int'; // Integer type

    protected $fillable = [
        'user_id',
        'store_id',
        'employee_number',
        'first_name',
        'last_name',
        'phone',
        'address',
        'date_of_birth',
        'gender',
        'hire_date',
        'position',
        'department',
        'employment_type',
        'salary',
        'bank_account',
        'tax_id',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'id_document_path',
        'contract_path',
        'status',
        'termination_date',
        'termination_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'salary' => 'decimal:2',
        'termination_date' => 'date',
        'settings' => 'array',
    ];

    public function deductions()
    {
        return $this->hasMany(EmployeeDeduction::class, 'employee_id');
    }

  
    public function activeDeductions()
    {
        return $this->hasMany(EmployeeDeduction::class, 'employee_id')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }


    /**
     * Get the user associated with the employee.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the store where the employee works.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    /**
     * Get the employee's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Check if employee is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if employee is a store manager/admin.
     */
    public function isStoreManager(): bool
    {
        return $this->position === 'store_manager';
    }

    /**
     * Check if employee is in HR department.
     */
    public function isHr(): bool
    {
        return $this->position === 'hr_manager' || $this->department === 'hr';
    }

    /**
     * Check if employee is an accountant.
     */
    public function isAccountant(): bool
    {
        return $this->position === 'accountant' || $this->department === 'accounting';
    }

    /**
     * Check if employee is sales staff.
     */
    public function isSalesStaff(): bool
    {
        return in_array($this->position, ['sales_assistant', 'cashier']);
    }

    /**
     * Scope a query to only include active employees.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include employees of a specific store.
     */
    public function scopeOfStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    /**
     * Scope a query to only include employees by position.
     */
    public function scopeByPosition($query, $position)
    {
        return $query->where('position', $position);
    }

    /**
     * Scope a query to only include employees by department.
     */
    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    /**
     * Calculate years of service.
     */
    public function getYearsOfServiceAttribute(): float
    {
        return $this->hire_date->diffInYears(now());
    }

    /**
     * Get the employee's position display name.
     */
    public function getPositionDisplayAttribute(): string
    {
        $positions = [
            'store_manager' => 'Store Manager',
            'hr_manager' => 'HR Manager',
            'accountant' => 'Accountant',
            'sales_assistant' => 'Sales Assistant',
            'warehouse_manager' => 'Warehouse Manager',
            'cashier' => 'Cashier',
            'delivery_driver' => 'Delivery Driver',
            'other' => 'Other',
        ];

        return $positions[$this->position] ?? ucfirst(str_replace('_', ' ', $this->position));
    }

    /**
     * Get the employee's employment type display name.
     */
    public function getEmploymentTypeDisplayAttribute(): string
    {
        $types = [
            'full_time' => 'Full Time',
            'part_time' => 'Part Time',
            'contract' => 'Contract',
            'intern' => 'Intern',
        ];

        return $types[$this->employment_type] ?? ucfirst($this->employment_type);
    }

    public static function generateEmployeeNumber($roleId)
    {
        // Get role with code from database
        $role = Role::find($roleId);

        if (!$role || !$role->code) {
            // Fallback to default prefix
            return null;
        } else {
            // Use the role code as prefix (e.g., HR, SA, ADM, EMP)
            $prefix = strtoupper($role->code);
        }

        // Find the last employee number for this role prefix
        $lastEmployee = self::where('employee_number', 'like', $prefix . '%')
            ->orderBy('employee_number', 'desc')
            ->first();

        if ($lastEmployee) {
            // Extract the numeric part
            $lastNumber = (int) preg_replace('/[^0-9]/', '', $lastEmployee->employee_number);
            $nextNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '00001';
        }

        return $prefix . "-" . $nextNumber;
    }
}
