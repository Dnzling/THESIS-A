<?php

namespace Database\Seeders\Hr;

use App\Models\Hr\PayrollItem;
use App\Models\Hr\Payroll;
use App\Models\Hr\Employee;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PayrollItemSeeder extends Seeder
{
    public function run(): void
    {
        // Get January 2026 payrolls for all employees except user_id 12
        $payrolls = Payroll::whereHas('employee', function($query) {
            $query->whereIn('user_id', [2, 3, 4, 5, 6, 7, 8, 9, 10, 11]);
        })->get();
        
        $payrollItems = [];

        foreach ($payrolls as $payroll) {
            $employee = $payroll->employee;
            $roleId = $employee->role_id;
            $halfMonthSalary = $payroll->base_salary;
            
            // ============ EARNINGS (Basic Salary) ============
            $payrollItems[] = [
                'payroll_id' => $payroll->id,
                'type' => 'earning',
                'name' => 'Basic Salary',
                'amount' => $halfMonthSalary,
                'calculation_type' => 'fixed',
                'rate' => $halfMonthSalary * 2,
                'quantity' => 0.5,
                'notes' => 'Basic salary for ' . $payroll->payPeriod->name,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
            
            // ============ OVERTIME ============
            if ($payroll->overtime_hours > 0) {
                $hourlyRate = $this->getHourlyRate($roleId);
                $overtimeRate = $hourlyRate * 1.25;
                
                $payrollItems[] = [
                    'payroll_id' => $payroll->id,
                    'type' => 'earning',
                    'name' => 'Overtime Pay',
                    'amount' => $payroll->overtime_amount,
                    'calculation_type' => 'hourly',
                    'rate' => $overtimeRate,
                    'quantity' => $payroll->overtime_hours,
                    'notes' => $payroll->overtime_hours . ' hours overtime at 125% rate',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
            
            // ============ BONUSES ============
            if ($roleId == 3) { // Store Manager - Performance Bonus
                $payrollItems[] = [
                    'payroll_id' => $payroll->id,
                    'type' => 'bonus',
                    'name' => 'Performance Bonus',
                    'amount' => 3000.00,
                    'calculation_type' => 'percentage',
                    'rate' => 12.5,
                    'quantity' => 1,
                    'notes' => 'Store performance bonus - Q1',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
            
            if ($roleId == 10) { // Sales Staff - Commission
                $payrollItems[] = [
                    'payroll_id' => $payroll->id,
                    'type' => 'bonus',
                    'name' => 'Sales Commission',
                    'amount' => 4500.00,
                    'calculation_type' => 'percentage',
                    'rate' => 3.5,
                    'quantity' => 128571.43,
                    'notes' => '3.5% commission on sales of ₱128,571.43',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
            
            if ($roleId == 11) { // Delivery Staff - Delivery Incentive
                $payrollItems[] = [
                    'payroll_id' => $payroll->id,
                    'type' => 'bonus',
                    'name' => 'Delivery Incentive',
                    'amount' => 2250.00,
                    'calculation_type' => 'rate',
                    'rate' => 45.00,
                    'quantity' => 50,
                    'notes' => '₱45 per delivery x 50 deliveries',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
            
            // ============ ALLOWANCES ============
            if ($roleId == 2) { // Store Admin
                $payrollItems[] = [
                    'payroll_id' => $payroll->id,
                    'type' => 'allowance',
                    'name' => 'Transportation Allowance',
                    'amount' => 1000.00,
                    'calculation_type' => 'fixed',
                    'rate' => 1000.00,
                    'quantity' => 1,
                    'notes' => 'Monthly transportation allowance',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
                
                $payrollItems[] = [
                    'payroll_id' => $payroll->id,
                    'type' => 'allowance',
                    'name' => 'Rice Subsidy',
                    'amount' => 750.00,
                    'calculation_type' => 'fixed',
                    'rate' => 750.00,
                    'quantity' => 1,
                    'notes' => 'Monthly rice subsidy',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
            
            if ($roleId == 4) { // HR Manager
                $payrollItems[] = [
                    'payroll_id' => $payroll->id,
                    'type' => 'allowance',
                    'name' => 'Communication Allowance',
                    'amount' => 1500.00,
                    'calculation_type' => 'fixed',
                    'rate' => 1500.00,
                    'quantity' => 1,
                    'notes' => 'Monthly communication allowance',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
            
            if ($roleId == 5) { // Accountant
                $payrollItems[] = [
                    'payroll_id' => $payroll->id,
                    'type' => 'allowance',
                    'name' => 'Clothing Allowance',
                    'amount' => 600.00,
                    'calculation_type' => 'fixed',
                    'rate' => 600.00,
                    'quantity' => 1,
                    'notes' => 'Monthly clothing allowance',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
            
            if ($roleId == 7) { // Warehouse Manager
                $payrollItems[] = [
                    'payroll_id' => $payroll->id,
                    'type' => 'allowance',
                    'name' => 'Hazard Pay',
                    'amount' => 500.00,
                    'calculation_type' => 'fixed',
                    'rate' => 500.00,
                    'quantity' => 1,
                    'notes' => 'Monthly hazard pay for warehouse staff',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
            
            if ($roleId == 8) { // Inventory Staff
                $payrollItems[] = [
                    'payroll_id' => $payroll->id,
                    'type' => 'allowance',
                    'name' => 'Inventory Allowance',
                    'amount' => 400.00,
                    'calculation_type' => 'fixed',
                    'rate' => 400.00,
                    'quantity' => 1,
                    'notes' => 'Monthly inventory handling allowance',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
            
            // ============ DEDUCTIONS ============
            // SSS Contribution
            $sssAmount = $this->getSSSContribution($halfMonthSalary * 2);
            $payrollItems[] = [
                'payroll_id' => $payroll->id,
                'type' => 'deduction',
                'name' => 'SSS Contribution',
                'amount' => $sssAmount / 2, // Half month
                'calculation_type' => 'fixed',
                'rate' => $sssAmount,
                'quantity' => 0.5,
                'notes' => 'Monthly SSS contribution (half month)',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
            
            // PhilHealth
            $monthlySalary = $halfMonthSalary * 2;
            $philhealthAmount = $monthlySalary * 0.025; // 2.5%
            $payrollItems[] = [
                'payroll_id' => $payroll->id,
                'type' => 'deduction',
                'name' => 'PhilHealth',
                'amount' => round($philhealthAmount / 2, 2),
                'calculation_type' => 'percentage',
                'rate' => 2.5,
                'quantity' => $monthlySalary,
                'notes' => '2.5% of monthly basic salary (half month)',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
            
            // Pag-IBIG
            $payrollItems[] = [
                'payroll_id' => $payroll->id,
                'type' => 'deduction',
                'name' => 'Pag-IBIG',
                'amount' => 50.00, // Half of monthly 100
                'calculation_type' => 'fixed',
                'rate' => 100.00,
                'quantity' => 0.5,
                'notes' => 'Monthly Pag-IBIG contribution (half month)',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
            
            // ============ TAX ============
            if ($payroll->tax_amount > 0) {
                $taxableIncome = $halfMonthSalary - ($sssAmount/2 + $philhealthAmount/2 + 50);
                
                $payrollItems[] = [
                    'payroll_id' => $payroll->id,
                    'type' => 'tax',
                    'name' => 'Withholding Tax',
                    'amount' => $payroll->tax_amount,
                    'calculation_type' => 'percentage',
                    'rate' => $this->getTaxRate($roleId),
                    'quantity' => $taxableIncome,
                    'notes' => 'Withholding tax on taxable income',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
            
            // ============ NIGHT DIFFERENTIAL (Cashier only) ============
            if ($roleId == 6 && $payroll->pay_period_id % 2 == 0) { // Cashier, 2nd half
                $payrollItems[] = [
                    'payroll_id' => $payroll->id,
                    'type' => 'earning',
                    'name' => 'Night Differential',
                    'amount' => 850.75,
                    'calculation_type' => 'hourly',
                    'rate' => 106.34,
                    'quantity' => 8,
                    'notes' => '8 hours night differential',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
        }

        // Insert all payroll items
        foreach ($payrollItems as $item) {
            PayrollItem::create($item);
        }
    }
    
    private function getHourlyRate($roleId): float
    {
        $hourlyRates = [
            2 => 343.75,  // Store Admin (55000/160)
            3 => 300.00,  // Store Manager (48000/160)
            4 => 325.00,  // HR Manager (52000/160)
            5 => 281.25,  // Accountant (45000/160)
            6 => 156.25,  // Cashier (25000/160)
            7 => 175.00,  // Warehouse Manager (28000/160)
            8 => 175.00,  // Inventory Staff (28000/160)
            9 => 200.00,  // Supplier Coordinator (32000/160)
            10 => 162.50, // Sales Staff (26000/160)
            11 => 150.00, // Delivery Staff (24000/160)
        ];
        
        return $hourlyRates[$roleId] ?? 187.50;
    }
    
    private function getSSSContribution($monthlySalary): float
    {
        $sssBrackets = [
            55000 => 900.00,
            52000 => 850.00,
            50000 => 825.00,
            48000 => 800.00,
            45000 => 775.00,
            32000 => 650.00,
            28000 => 600.00,
            26000 => 575.00,
            25000 => 550.00,
            24000 => 525.00,
            20000 => 500.00,
        ];
        
        $contribution = 500.00;
        foreach ($sssBrackets as $bracket => $amount) {
            if ($monthlySalary >= $bracket) {
                $contribution = $amount;
            }
        }
        
        return $contribution;
    }
    
    private function getTaxRate($roleId): float
    {
        $taxRates = [
            2 => 0.15, // Store Admin
            3 => 0.15, // Store Manager
            4 => 0.15, // HR Manager
            5 => 0.12, // Accountant
            6 => 0.10, // Cashier
            7 => 0.10, // Warehouse Manager
            8 => 0.10, // Inventory Staff
            9 => 0.12, // Supplier Coordinator
            10 => 0.10, // Sales Staff
            11 => 0.08, // Delivery Staff
        ];
        
        return $taxRates[$roleId] ?? 0.10;
    }
}