<?php

namespace App\Services;

use App\Models\Employee;
use Carbon\Carbon;

class EmployeeIdGenerationService
{
    /**
     * Generate next employee ID in format YYYY-XXXXX
     * XXXXX is incremental number that resets annually
     */
    public static function generateEmployeeId(): string
    {
        $currentYear = Carbon::now()->year;
        $prefix = $currentYear . '-';

        // Get the highest sequence number for current year
        $lastEmployee = Employee::where('employee_id', 'like', $prefix . '%')
            ->orderByRaw("CAST(SUBSTRING(employee_id, 6) AS UNSIGNED) DESC")
            ->first();

        if (!$lastEmployee) {
            $sequence = 1;
        } else {
            $lastSequence = (int) substr($lastEmployee->employee_id, 5);
            $sequence = $lastSequence + 1;
        }

        return $prefix . str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Create employee from accepted job offer
     */
    public static function createEmployeeFromOffer($jobOffer): Employee
    {
        $employee = Employee::create([
            'employee_id' => self::generateEmployeeId(),
            'first_name' => $jobOffer->application->first_name,
            'last_name' => $jobOffer->application->last_name,
            'email' => $jobOffer->application->email,
            'position' => $jobOffer->position,
            'department' => $jobOffer->department,
            'salary' => $jobOffer->salary,
            'start_date' => $jobOffer->start_date,
            'status' => 'Active'
        ]);

        // Auto-populate deductions based on company rules
        self::populateDeductions($employee, $jobOffer);

        return $employee;
    }

    /**
     * Populate default deductions for new employee
     */
    private static function populateDeductions(Employee $employee, $jobOffer): void
    {
        // Get company deduction configuration
        // This assumes you have a DeductionConfiguration model/table
        // Adjust based on your actual deduction system

        $deductions = [
            [
                'type' => 'SSS',
                'description' => 'Social Security System',
                'amount' => 0, // Calculate based on salary
                'is_automatic' => true
            ],
            [
                'type' => 'PhilHealth',
                'description' => 'PhilHealth Insurance',
                'amount' => 0, // Calculate based on salary
                'is_automatic' => true
            ],
            [
                'type' => 'PagIbig',
                'description' => 'Pag-IBIG Fund',
                'amount' => 100, // Fixed amount
                'is_automatic' => true
            ]
        ];

        // Save deductions to employee's deduction records
        // This depends on your deduction system implementation
        foreach ($deductions as $deduction) {
            // Example: $employee->deductions()->create($deduction);
        }
    }
}
