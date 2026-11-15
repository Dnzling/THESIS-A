<?php

namespace App\Services\Procurement;

final class InclusiveTaxCalculator
{
    public static function taxExclusiveAmount(float $grossAmount, float $rate): float
    {
        if ($grossAmount <= 0 || $rate <= 0) {
            return round(max(0, $grossAmount), 2);
        }

        return round($grossAmount / (1 + ($rate / 100)), 2);
    }

    public static function includedTax(float $grossAmount, float $rate): float
    {
        if ($grossAmount <= 0 || $rate <= 0) {
            return 0.0;
        }

        return round($grossAmount - self::taxExclusiveAmount($grossAmount, $rate), 2);
    }
}
