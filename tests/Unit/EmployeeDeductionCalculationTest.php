<?php

namespace Tests\Unit;

use App\Models\Hr\DeductionType;
use App\Models\Hr\EmployeeDeduction;
use Carbon\Carbon;
use Tests\TestCase;

class EmployeeDeductionCalculationTest extends TestCase
{
    public function test_deduction_uses_payroll_period_date_instead_of_current_date(): void
    {
        Carbon::setTestNow('2026-10-27 12:00:00');

        try {
            $type = new DeductionType([
                'is_active' => true,
                'calculation_type' => 'fixed',
                'default_amount' => 250,
            ]);
            $deduction = new EmployeeDeduction([
                'is_active' => true,
                'effective_date' => '2026-09-01',
                'end_date' => '2026-09-15',
            ]);
            $deduction->setRelation('deductionType', $type);

            $this->assertSame(250.0, $deduction->calculateAmount(1000, 1200, Carbon::parse('2026-09-15')));
            $this->assertSame(0.0, $deduction->calculateAmount(1000, 1200));
        } finally {
            Carbon::setTestNow();
        }
    }
}
