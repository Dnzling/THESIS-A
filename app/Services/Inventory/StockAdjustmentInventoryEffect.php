<?php

namespace App\Services\Inventory;

use InvalidArgumentException;

class StockAdjustmentInventoryEffect
{
    public function calculate(string $adjustmentType, int $difference): array
    {
        if ($adjustmentType === 'damage') {
            if ($difference >= 0) {
                throw new InvalidArgumentException('Damaged stock must reduce the saleable quantity.');
            }

            return [
                'on_hand_change' => 0,
                'available_change' => $difference,
                'damaged_change' => abs($difference),
                'transaction_type' => 'damage',
            ];
        }

        return [
            'on_hand_change' => $difference,
            'available_change' => $difference,
            'damaged_change' => 0,
            'transaction_type' => 'adjustment',
        ];
    }
}

