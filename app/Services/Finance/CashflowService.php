<?php

namespace App\Services\Finance;

use App\Models\Finance\FinanceAccount;
use App\Models\Finance\FinanceCashflowTransaction;
use Illuminate\Support\Facades\DB;

class CashflowService
{
    public function getOrCreateOperatingAccount(int $storeId, ?int $userId = null): FinanceAccount
    {
        return FinanceAccount::firstOrCreate(
            [
                'store_id' => $storeId,
                'type' => 'operating',
            ],
            [
                'name' => 'Main Operating Account',
                'currency' => 'PHP',
                'current_balance' => 0,
                'is_active' => true,
                'created_by' => $userId,
            ]
        );
    }

    public function getAvailableBalance(int $storeId): float
    {
        $account = $this->getOrCreateOperatingAccount($storeId);
        return (float) $account->current_balance;
    }

    public function topUp(
        int $storeId,
        float $amount,
        ?int $userId = null,
        ?string $description = 'Manual top-up',
        ?string $paymentMethod = null,
        array $meta = []
    ): FinanceCashflowTransaction {
        if ($amount <= 0) {
            throw new \RuntimeException('Top-up amount must be greater than zero.');
        }

        return DB::transaction(function () use ($storeId, $amount, $userId, $description, $paymentMethod, $meta) {
            $account = FinanceAccount::where('store_id', $storeId)
                ->where('type', 'operating')
                ->lockForUpdate()
                ->first();

            if (!$account) {
                $account = $this->getOrCreateOperatingAccount($storeId, $userId);
                $account = FinanceAccount::whereKey($account->id)->lockForUpdate()->first();
            }

            $before = (float) $account->current_balance;
            $after = $before + $amount;

            $account->update(['current_balance' => $after]);

            return FinanceCashflowTransaction::create([
                'finance_account_id' => $account->id,
                'store_id' => $storeId,
                'direction' => 'in',
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'payment_method' => $paymentMethod,
                'description' => $description,
                'meta' => $meta,
                'created_by' => $userId,
            ]);
        });
    }

    public function credit(
        int $storeId,
        float $amount,
        string $referenceType,
        int|string $referenceId,
        ?int $userId = null,
        ?string $description = null,
        ?string $paymentMethod = null,
        array $meta = []
    ): FinanceCashflowTransaction {
        if ($amount <= 0) {
            throw new \RuntimeException('Credit amount must be greater than zero.');
        }

        return DB::transaction(function () use ($storeId, $amount, $referenceType, $referenceId, $userId, $description, $paymentMethod, $meta) {
            $account = FinanceAccount::where('store_id', $storeId)
                ->where('type', 'operating')
                ->lockForUpdate()
                ->first();

            if (!$account) {
                $account = $this->getOrCreateOperatingAccount($storeId, $userId);
                $account = FinanceAccount::whereKey($account->id)->lockForUpdate()->first();
            }

            if (!(bool) $account->is_active) {
                throw new \RuntimeException('Finance operating account is inactive.');
            }

            $before = (float) $account->current_balance;
            $after = $before + $amount;
            $account->update(['current_balance' => $after]);

            return FinanceCashflowTransaction::create([
                'finance_account_id' => $account->id,
                'store_id' => $storeId,
                'direction' => 'in',
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'reference_type' => $referenceType,
                'reference_id' => (int) $referenceId,
                'payment_method' => $paymentMethod,
                'description' => $description,
                'meta' => $meta,
                'created_by' => $userId,
            ]);
        });
    }

    public function debit(
        int $storeId,
        float $amount,
        string $referenceType,
        int|string $referenceId,
        ?int $userId = null,
        ?string $description = null,
        ?string $paymentMethod = null,
        array $meta = []
    ): FinanceCashflowTransaction {
        if ($amount <= 0) {
            throw new \RuntimeException('Debit amount must be greater than zero.');
        }

        return DB::transaction(function () use ($storeId, $amount, $referenceType, $referenceId, $userId, $description, $paymentMethod, $meta) {
            $account = FinanceAccount::where('store_id', $storeId)
                ->where('type', 'operating')
                ->lockForUpdate()
                ->first();

            if (!$account) {
                $account = $this->getOrCreateOperatingAccount($storeId, $userId);
                $account = FinanceAccount::whereKey($account->id)->lockForUpdate()->first();
            }

            if (!(bool) $account->is_active) {
                throw new \RuntimeException('Finance operating account is inactive.');
            }

            $before = (float) $account->current_balance;
            if ($before < $amount) {
                throw new \RuntimeException('Insufficient funds. Available: ' . number_format($before, 2) . ', Required: ' . number_format($amount, 2));
            }

            $after = $before - $amount;
            $account->update(['current_balance' => $after]);

            return FinanceCashflowTransaction::create([
                'finance_account_id' => $account->id,
                'store_id' => $storeId,
                'direction' => 'out',
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'reference_type' => $referenceType,
                'reference_id' => (int) $referenceId,
                'payment_method' => $paymentMethod,
                'description' => $description,
                'meta' => $meta,
                'created_by' => $userId,
            ]);
        });
    }
}
