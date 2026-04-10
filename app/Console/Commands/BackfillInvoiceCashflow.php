<?php

namespace App\Console\Commands;

use App\Models\Procurement\Invoice\Invoice;
use App\Services\Finance\CashflowService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillInvoiceCashflow extends Command
{
    protected $signature = 'finance:backfill-invoice-cashflow
        {--store_id= : Backfill only one store ID}
        {--dry-run : Preview records without writing data}';

    protected $description = 'Create missing cashflow out transactions for already-paid supplier invoices.';

    public function handle(): int
    {
        $storeId = $this->option('store_id');
        $dryRun = (bool) $this->option('dry-run');
        $cashflow = new CashflowService();

        $query = Invoice::query()
            ->whereNull('deleted_at')
            ->where('payment_status', 'paid')
            ->when($storeId, fn ($q) => $q->where('store_id', (int) $storeId))
            ->orderBy('id');

        $rows = $query->get();
        $processed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($rows as $invoice) {
            $amount = (float) ($invoice->payment_amount ?: $invoice->net_amount ?: $invoice->invoice_amount ?: 0);
            $store = (int) $invoice->store_id;

            if ($store <= 0 || $amount <= 0) {
                $skipped++;
                continue;
            }

            $alreadyExists = DB::table('finance_cashflow_transactions')
                ->where('store_id', $store)
                ->where('direction', 'out')
                ->where('reference_type', 'invoice')
                ->where('reference_id', (int) $invoice->id)
                ->exists();

            if ($alreadyExists) {
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $processed++;
                continue;
            }

            try {
                $cashflow->debit(
                    $store,
                    $amount,
                    'invoice',
                    (int) $invoice->id,
                    null,
                    'Backfill invoice payment ' . ($invoice->invoice_number ?? ('#' . $invoice->id)),
                    (string) ($invoice->payment_method ?: 'system')
                );
                $processed++;
            } catch (\Throwable $e) {
                $failed++;
                $this->warn("Failed invoice #{$invoice->id} ({$invoice->invoice_number}): {$e->getMessage()}");
            }
        }

        $this->info("Processed: {$processed} | Skipped: {$skipped} | Failed: {$failed}");

        return self::SUCCESS;
    }
}

