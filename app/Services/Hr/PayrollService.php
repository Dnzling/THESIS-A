<?php

namespace App\Services\Hr;

use App\Models\Hr\Employee;

class PayrollService
{
    public static function deriveHourlyRate(Employee $employee, float $divisor = 173.33): float
    {
        // If an explicit hourly_rate exists, use it
        if (!is_null($employee->hourly_rate) && $employee->hourly_rate > 0) {
            return (float) $employee->hourly_rate;
        }

        // Fallback: derive from monthly salary if available
        // Employees store their configured monthly pay in salary.
        // Keep basic_salary as a legacy fallback for older records.
        $monthly = (float) ($employee->salary ?? $employee->basic_salary ?? 0);
        if ($monthly <= 0) {
            return 0.0;
        }

        if (($employee->pay_type ?? 'monthly') === 'daily') {
            $employee->loadMissing('store');
            $settings = is_array($employee->store?->settings) ? $employee->store->settings : [];
            $payrollSettings = $settings['hr_payroll_settings'] ?? [];
            $workforceRules = $settings['hr_workforce_rules'] ?? [];
            $paidHours = (float) ($payrollSettings['paidHours'] ?? $workforceRules['dailyHours'] ?? 8);

            return round($monthly / max(1, min(24, $paidHours)), 4);
        }

        return round($monthly / $divisor, 4);
    }
}
