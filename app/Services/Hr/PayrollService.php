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
        $monthly = (float) ($employee->basic_salary ?? 0);
        if ($monthly <= 0) {
            return 0.0;
        }

        return round($monthly / $divisor, 4);
    }
}
