<?php

namespace Database\Seeders\Hr;

use App\Models\Hr\DeductionType;
use App\Models\Hr\EmployeeDeduction;
use Illuminate\Database\Seeder;

class EmployeeDeductionsSeeder extends Seeder
{
    public function run(): void
    {
        // Get all existing employee deductions
        $employeeDeductions = EmployeeDeduction::all();

        // Group by unique combinations
        $grouped = $employeeDeductions->groupBy(function ($item) {
            return json_encode([
                'amount' => $item->amount,
                'frequency' => $item->frequency,
                'is_mandatory' => $item->is_mandatory,
                'is_taxable' => $item->is_taxable,
            ]);
        });

        foreach ($grouped as $key => $group) {
            $data = json_decode($key, true);
            $firstItem = $group->first();

            // Check if deduction type already exists
            $deductionType = DeductionType::where('store_id', $firstItem->employee->store_id ?? 1)
                ->where('default_amount', $data['amount'])
                ->where('frequency', $data['frequency'])
                ->where('is_mandatory', $data['is_mandatory'])
                ->where('is_taxable', $data['is_taxable'])
                ->first();

            // Create if not exists
            if (!$deductionType) {
                $deductionType = DeductionType::create([
                    'store_id' => $firstItem->employee->store_id ?? 1,
                    'code' => 'Migrated_' . uniqid(),
                    'name' => 'Migrated Deduction ' . $data['amount'],
                    'category' => 'company',
                    'calculation_type' => 'fixed',
                    'default_amount' => $data['amount'],
                    'frequency' => $data['frequency'],
                    'is_mandatory' => $data['is_mandatory'],
                    'is_taxable' => $data['is_taxable'],
                    'is_active' => true,
                    'show_on_payslip' => true,
                    'created_by' => auth()->id() ?? 1,
                ]);
            }

            // Update all employee deductions in this group
            foreach ($group as $item) {
                $item->update(['deduction_type_id' => $deductionType->id]);
            }
        }

        $this->command->info('Employee deductions migrated successfully!');
    }
}