<?php

namespace Database\Seeders\Hr;

use Illuminate\Database\Seeder;
use App\Models\Hr\LeaveBalance;
use App\Models\Hr\Employee;
use Carbon\Carbon;

class LeaveBalanceSeeder extends Seeder
{
    public function run(): void
    {
        $currentYear = Carbon::now()->year;
        $employees = Employee::where('status', 'active')->get();
        
        $leaveConfigs = [
            'vacation' => 15,  // 15 days vacation leave
            'sick' => 15,       // 15 days sick leave
            'personal' => 3,    // 3 days personal leave
            'maternity' => 60,  // 60 days maternity (special case)
            'paternity' => 7,   // 7 days paternity
            'bereavement' => 3, // 3 days bereavement
        ];

        foreach ($employees as $employee) {
            foreach ($leaveConfigs as $type => $quota) {
                // Adjust quota based on employment type AND store policies
                $adjustedQuota = $this->calculateQuotaByEmploymentTypeAndStore(
                    $quota, 
                    $employee->employment_type,
                    $employee->store_id  // Pass store_id for store-specific rules
                );

                // Check for carried over from previous year
                $carriedOver = $this->getCarriedOverBalance(
                    $employee->id, 
                    $type, 
                    $currentYear,
                    $employee->store_id  // Pass store_id
                );

                LeaveBalance::create([
                    'employee_id' => $employee->id,
                    'store_id' => $employee->store_id,  // ✅ Added store_id
                    'leave_type' => $type,
                    'yearly_quota' => $adjustedQuota,
                    'used_days' => 0,
                    'pending_days' => 0,
                    'remaining_days' => $adjustedQuota + $carriedOver,
                    'carried_over' => $carriedOver,
                    'expired_days' => 0,
                    'year' => $currentYear,
                    'expiry_date' => Carbon::create($currentYear, 12, 31),
                    'status' => 'active',
                    'created_by' => 1, // System user ID
                ]);
            }
        }
    }

    private function calculateQuotaByEmploymentTypeAndStore($baseQuota, $employmentType, $storeId)
    {
        // Base adjustment by employment type
        $quota = match($employmentType) {
            'part_time' => $baseQuota * 0.5,  // Half for part-time
            'contract' => $baseQuota * 0.75,  // 75% for contractual
            'intern' => 0,                     // No leaves for interns
            default => $baseQuota,              // Full-time gets full quota
        };
        
        // Store-specific rules (example)
        $storeMultipliers = [
            1 => 1.0,  // Store 1: Standard
            2 => 1.2,  // Store 2: 20% more leaves (premium store)
            3 => 0.9,  // Store 3: 10% less leaves (probationary store)
            4 => 1.0,  // Store 4: Standard
            5 => 1.1,  // Store 5: 10% more leaves
        ];
        
        $multiplier = $storeMultipliers[$storeId] ?? 1.0;
        
        return round($quota * $multiplier, 2);
    }

    private function getCarriedOverBalance($employeeId, $leaveType, $currentYear, $storeId)
    {
        // Check last year's balance for carry over
        $lastYearBalance = LeaveBalance::where('employee_id', $employeeId)
            ->where('store_id', $storeId)  // ✅ Added store_id
            ->where('leave_type', $leaveType)
            ->where('year', $currentYear - 1)
            ->where('status', 'active')
            ->first();

        if ($lastYearBalance && $lastYearBalance->remaining_days > 0) {
            // Store-specific carry over limits
            $maxCarryOver = $this->getMaxCarryOverByStore($storeId, $leaveType);
            
            return $leaveType === 'vacation' 
                ? min($lastYearBalance->remaining_days, $maxCarryOver) 
                : 0;
        }

        return 0;
    }
    
    private function getMaxCarryOverByStore($storeId, $leaveType)
    {
        // Different stores have different carry over policies
        $carryOverLimits = [
            1 => ['vacation' => 5, 'sick' => 10],    // Store 1: 5 days vacation, 10 days sick
            2 => ['vacation' => 10, 'sick' => 15],   // Store 2: More generous
            3 => ['vacation' => 3, 'sick' => 5],     // Store 3: Strict limits
            4 => ['vacation' => 5, 'sick' => 5],     // Store 4: Equal limits
            5 => ['vacation' => 7, 'sick' => 7],     // Store 5: Medium limits
        ];
        
        return $carryOverLimits[$storeId][$leaveType] ?? 5; // Default 5 days
    }
}