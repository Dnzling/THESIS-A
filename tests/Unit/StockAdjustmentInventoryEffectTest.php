<?php

use App\Services\Inventory\StockAdjustmentInventoryEffect;

it('keeps damaged units on hand while removing them from saleable stock', function () {
    $effect = (new StockAdjustmentInventoryEffect())->calculate('damage', -3);

    expect($effect)->toBe([
        'on_hand_change' => 0,
        'available_change' => -3,
        'damaged_change' => 3,
        'transaction_type' => 'damage',
    ]);
});

it('continues to deduct physical and available stock for ordinary negative adjustments', function () {
    $effect = (new StockAdjustmentInventoryEffect())->calculate('writeoff', -3);

    expect($effect)->toBe([
        'on_hand_change' => -3,
        'available_change' => -3,
        'damaged_change' => 0,
        'transaction_type' => 'adjustment',
    ]);
});

it('rejects damage adjustments that do not reduce saleable stock', function () {
    expect(fn () => (new StockAdjustmentInventoryEffect())->calculate('damage', 0))
        ->toThrow(\InvalidArgumentException::class);
});

