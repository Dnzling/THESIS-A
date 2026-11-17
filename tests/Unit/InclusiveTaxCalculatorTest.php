<?php

namespace Tests\Unit;

use App\Services\Procurement\InclusiveTaxCalculator;
use PHPUnit\Framework\TestCase;

class InclusiveTaxCalculatorTest extends TestCase
{
    public function test_it_extracts_included_vat_from_a_gross_amount(): void
    {
        $this->assertSame(89285.71, InclusiveTaxCalculator::taxExclusiveAmount(100000, 12));
        $this->assertSame(10714.29, InclusiveTaxCalculator::includedTax(100000, 12));
    }

    public function test_it_does_not_add_tax_when_the_rate_is_zero_or_amount_is_non_positive(): void
    {
        $this->assertSame(100.0, InclusiveTaxCalculator::taxExclusiveAmount(100, 0));
        $this->assertSame(0.0, InclusiveTaxCalculator::includedTax(100, 0));
        $this->assertSame(0.0, InclusiveTaxCalculator::includedTax(-100, 12));
    }
}
