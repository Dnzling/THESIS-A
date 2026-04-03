<?php

namespace App\Services\Finance;

use App\Models\Finance\FinanceExpense;
use App\Models\Inventory\InventoryConfiguration;

class FinanceExpenseService
{
    public function requiresFinanceApproval(int $storeId, float $amount): bool
    {
        $config = InventoryConfiguration::where('store_id', $storeId)->first();
        if (!$config) {
            return false;
        }

        return $config->requiresFinanceApproval($amount);
    }

    /**
     * Ensure a finance expense exists for a reference.
     */
    public function ensureExpense(array $data, bool $autoApprove = false, ?int $approverId = null): FinanceExpense
    {
        $expense = FinanceExpense::where('reference_type', $data['reference_type'])
            ->where('reference_id', $data['reference_id'])
            ->first();

        if (!$expense) {
            $expense = FinanceExpense::create($data);
        } else {
            $expense->update([
                'amount' => $data['amount'],
                'expense_date' => $data['expense_date'],
                'description' => $data['description'] ?? $expense->description,
                'notes' => $data['notes'] ?? $expense->notes,
                'category' => $data['category'] ?? $expense->category,
                'currency' => $data['currency'] ?? $expense->currency,
            ]);
        }

        if ($autoApprove && $expense->status === 'pending_approval') {
            $expense->update([
                'status' => 'approved',
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);
        }

        return $expense->fresh();
    }
}
