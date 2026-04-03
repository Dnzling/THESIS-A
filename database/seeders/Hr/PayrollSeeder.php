<?php

namespace Database\Seeders\Hr;

use App\Models\Hr\Payroll;
use App\Models\Hr\Employee;
use App\Models\Hr\PayPeriod;
use App\Models\Core\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PayrollSeeder extends Seeder
{
    public function run(): void
    {
        // Get all employees (excluding user_id 1 - Super Admin)
        $employees = Employee::whereIn('user_id', [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12])->get();
        
        // Get January 2026 pay periods
        $payPeriodJan1 = PayPeriod::where('name', 'Pay Period 1 - January 2026 (1st Half)')->first();
        $payPeriodJan2 = PayPeriod::where('name', 'Pay Period 2 - January 2026 (2nd Half)')->first();
        
        // Get users for approval and payment
        $superAdmin = User::find(1); // Denz Declarado
        $hrManager = User::find(4); // Edwin Vasquez
        $accountant = User::find(5); // MC Mendoza
        
        $payrolls = [];
        $payrollId = 1;
        
        // Generate payroll for each employee for January pay periods
        foreach ($employees as $employee) {
            // JANUARY 1ST HALF - DRAFT
            $payrolls[] = $this->createPayroll(
                $payrollId++,
                $employee,
                $payPeriodJan1,
                'draft',
                null, // payment_date
                null, // payment_method
                null, // reference_number
                null, // notes
                null, // approved_by
                null, // approved_at
                null, // paid_by
                null, // paid_at
                Carbon::now(), // created_at
                Carbon::now() // updated_at
            );
            
            // JANUARY 2ND HALF - DRAFT
            $payrolls[] = $this->createPayroll(
                $payrollId++,
                $employee,
                $payPeriodJan2,
                'draft',
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                Carbon::now(),
                Carbon::now()
            );
        }
        
        // Insert all payrolls
        foreach ($payrolls as $payroll) {
            Payroll::create($payroll);
        }
    }
    
    private function createPayroll(
        $id,
        $employee,
        $payPeriod,
        $status,
        $payment_date,
        $payment_method,
        $reference_number,
        $notes,
        $approved_by,
        $approved_at,
        $paid_by,
        $paid_at,
        $created_at,
        $updated_at
    ): array {
        
        // Calculate amounts based on employee role
        $baseSalary = $employee->salary ?? $this->getDefaultSalary($employee->role_id);
        $halfMonthSalary = $baseSalary / 2;
        
        // Calculate other amounts based on role
        $overtime_hours = $this->getOvertimeHours($employee->role_id);
        $overtime_amount = $this->getOvertimeAmount($employee->role_id, $overtime_hours);
        $bonuses_total = $this->getBonusesTotal($employee->role_id);
        $allowances_total = $this->getAllowancesTotal($employee->role_id);
        $deductions_total = $this->getDeductionsTotal($employee->role_id, $halfMonthSalary);
        $tax_amount = $this->getTaxAmount($employee->role_id, $halfMonthSalary, $deductions_total);
        
        // Calculate net salary
        $grossSalary = $halfMonthSalary + $overtime_amount + $bonuses_total + $allowances_total;
        $netSalary = $grossSalary - $deductions_total - $tax_amount;
        
        // Set default notes if not provided
        if (!$notes) {
            $notes = $status === 'draft' 
                ? 'Draft payroll for ' . $payPeriod->name
                : 'Payroll for ' . $payPeriod->name;
        }
        
        return [
            'id' => $id,
            'employee_id' => $employee->id,
            'pay_period_id' => $payPeriod->id,
            'base_salary' => $halfMonthSalary,
            'overtime_hours' => $overtime_hours,
            'overtime_amount' => $overtime_amount,
            'deductions_total' => $deductions_total,
            'bonuses_total' => $bonuses_total,
            'allowances_total' => $allowances_total,
            'tax_amount' => $tax_amount,
            'net_salary' => $netSalary,
            'status' => $status,
            'payment_date' => $payment_date,
            'payment_method' => $payment_method,
            'reference_number' => $reference_number,
            'notes' => $notes,
            'approved_by' => $approved_by,
            'approved_at' => $approved_at,
            'paid_by' => $paid_by,
            'paid_at' => $paid_at,
            'created_at' => $created_at,
            'updated_at' => $updated_at,
        ];
    }
    
    private function getDefaultSalary($roleId): float
    {
        $salaries = [
            2 => 55000.00, // Store Admin
            3 => 48000.00, // Store Manager
            4 => 52000.00, // HR Manager
            5 => 45000.00, // Accountant
            6 => 25000.00, // Cashier
            7 => 28000.00, // Warehouse Manager
            8 => 28000.00, // Inventory Staff
            9 => 32000.00, // Supplier Coordinator
            10 => 26000.00, // Sales Staff
            11 => 24000.00, // Delivery Staff
        ];
        
        return $salaries[$roleId] ?? 30000.00;
    }
    
    private function getOvertimeHours($roleId): float
    {
        $overtime = [
            2 => 8.0,  // Store Admin
            3 => 5.0,  // Store Manager
            4 => 4.0,  // HR Manager
            5 => 3.0,  // Accountant
            6 => 12.0, // Cashier
            7 => 10.0, // Warehouse Manager
            8 => 8.0,  // Inventory Staff
            9 => 6.0,  // Supplier Coordinator
            10 => 7.0, // Sales Staff
            11 => 15.0, // Delivery Staff
        ];
        
        return $overtime[$roleId] ?? 0;
    }
    
    private function getOvertimeAmount($roleId, $hours): float
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
        
        $rate = $hourlyRates[$roleId] ?? 187.50;
        return $rate * $hours * 1.25; // 25% overtime premium
    }
    
    private function getBonusesTotal($roleId): float
    {
        $bonuses = [
            2 => 0,      // Store Admin
            3 => 3000.00, // Store Manager (performance bonus)
            4 => 0,      // HR Manager
            5 => 0,      // Accountant
            6 => 0,      // Cashier
            7 => 0,      // Warehouse Manager
            8 => 0,      // Inventory Staff
            9 => 0,      // Supplier Coordinator
            10 => 4500.00, // Sales Staff (commission)
            11 => 2250.00, // Delivery Staff (delivery incentive)
        ];
        
        return $bonuses[$roleId] ?? 0;
    }
    
    private function getAllowancesTotal($roleId): float
    {
        $allowances = [
            2 => 1750.00, // Store Admin (1000 transpo + 750 rice)
            3 => 0,       // Store Manager
            4 => 1500.00, // HR Manager (communication)
            5 => 600.00,  // Accountant (clothing)
            6 => 0,       // Cashier
            7 => 500.00,  // Warehouse Manager (hazard pay)
            8 => 400.00,  // Inventory Staff (inventory allowance)
            9 => 0,       // Supplier Coordinator
            10 => 0,      // Sales Staff
            11 => 0,      // Delivery Staff
        ];
        
        return $allowances[$roleId] ?? 0;
    }
    
    private function getDeductionsTotal($roleId, $halfMonthSalary): float
    {
        $monthlySalary = $halfMonthSalary * 2;
        
        $sss = [
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
        ];
        
        // Find closest SSS bracket
        $sssContribution = 500.00;
        foreach ($sss as $bracket => $amount) {
            if ($monthlySalary >= $bracket) {
                $sssContribution = $amount;
            }
        }
        
        $philhealth = $monthlySalary * 0.025; // 2.5%
        $pagibig = 100.00;
        
        // Pro-rate for half month
        return ($sssContribution + $philhealth + $pagibig) / 2;
    }
    
    private function getTaxAmount($roleId, $halfMonthSalary, $deductions_total): float
    {
        // Simplified tax calculation (15% of taxable income)
        $taxableIncome = $halfMonthSalary - $deductions_total;
        
        if ($taxableIncome <= 0) {
            return 0;
        }
        
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
        
        $rate = $taxRates[$roleId] ?? 0.10;
        return round($taxableIncome * $rate, 2);
    }
    
    private function generateReferenceNumber($prefix, $employeeId): string
    {
        return strtoupper($prefix) . '-' . date('Ymd') . '-' . str_pad($employeeId, 4, '0', STR_PAD_LEFT);
    }
}