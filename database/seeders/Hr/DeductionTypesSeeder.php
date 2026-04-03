<?php
// database/seeders/DeductionTypeSeeder.php

namespace Database\Seeders\Hr;

use App\Models\Hr\DeductionType;
use Illuminate\Database\Seeder;

class DeductionTypesSeeder extends Seeder
{
    public function run()
    {
        $deductionTypes = [
            // Government Mandated - Percentage based
            [
                'store_id' => 1,
                'code' => 'SSS',
                'name' => 'SSS Contribution',
                'category' => 'government',
                'frequency' => 'monthly',
                'calculation_type' => 'formula',
                'formula_data' => [
                    'brackets' => [
                        ['min' => 0, 'max' => 10000, 'amount' => 500],
                        ['min' => 10001, 'max' => 20000, 'amount' => 800],
                        ['min' => 20001, 'max' => 30000, 'amount' => 1100],
                        ['min' => 30001, 'max' => 40000, 'amount' => 1350],
                    ],
                    'max_amount' => 1500
                ],
                'is_mandatory' => true,
                'is_taxable' => false,
                'show_on_payslip' => true,
                'sort_order' => 10,
                'description' => 'Social Security System contribution'
            ],
            [
                'store_id' => 1,
                'code' => 'PHILHEALTH',
                'name' => 'PhilHealth',
                'category' => 'government',
                'frequency' => 'monthly',
                'calculation_type' => 'percentage',
                'percentage_value' => 1.5, // 1.5% of salary
                'percentage_basis' => 'basic',
                'min_amount' => 150,
                'max_amount' => 1200,
                'is_mandatory' => true,
                'is_taxable' => false,
                'show_on_payslip' => true,
                'sort_order' => 20,
                'description' => 'Philippine Health Insurance Corporation'
            ],
            [
                'store_id' => 1,
                'code' => 'PAGIBIG',
                'name' => 'Pag-IBIG Fund',
                'category' => 'government',
                'frequency' => 'monthly',
                'calculation_type' => 'percentage',
                'percentage_value' => 2.0, // 2% of salary
                'percentage_basis' => 'basic',
                'min_amount' => 100,
                'max_amount' => 200,
                'is_mandatory' => true,
                'is_taxable' => false,
                'show_on_payslip' => true,
                'sort_order' => 30,
                'description' => 'Home Development Mutual Fund'
            ],

            // Company Benefits - Fixed
            [
                'store_id' => 1,
                'code' => 'HMO',
                'name' => 'Health Insurance',
                'category' => 'company',
                'frequency' => 'monthly',
                'calculation_type' => 'fixed',
                'default_amount' => 500,
                'is_mandatory' => false,
                'is_taxable' => false,
                'show_on_payslip' => true,
                'sort_order' => 40,
                'description' => 'Company health maintenance organization'
            ],

            // Loans - Fixed
            [
                'store_id' => 1,
                'code' => 'SALARY_LOAN',
                'name' => 'Salary Loan',
                'category' => 'loan',
                'frequency' => 'monthly',
                'calculation_type' => 'fixed',
                'is_mandatory' => false,
                'is_taxable' => false,
                'show_on_payslip' => true,
                'sort_order' => 50,
                'description' => 'Employee salary loan deduction'
            ],

            // Tax - Percentage of taxable income
            [
                'store_id' => 1,
                'code' => 'WITHHOLDING_TAX',
                'name' => 'Withholding Tax',
                'category' => 'government',
                'frequency' => 'monthly',
                'calculation_type' => 'percentage',
                'percentage_basis' => 'taxable',
                'formula_data' => [
                    'brackets' => [
                        ['min' => 0, 'max' => 20833, 'rate' => 0, 'fixed' => 0],
                        ['min' => 20834, 'max' => 33332, 'rate' => 15, 'fixed' => 0],
                        ['min' => 33333, 'max' => 83332, 'rate' => 20, 'fixed' => 1875],
                        ['min' => 83333, 'max' => 333332, 'rate' => 25, 'fixed' => 11875],
                    ]
                ],
                'is_mandatory' => true,
                'is_taxable' => false,
                'show_on_payslip' => true,
                'sort_order' => 100,
                'description' => 'Monthly withholding tax'
            ]
        ];

        foreach ($deductionTypes as $type) {
            DeductionType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
