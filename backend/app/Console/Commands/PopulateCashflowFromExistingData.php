<?php

namespace App\Console\Commands;

use App\Services\Finance\CashflowService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PopulateCashflowFromExistingData extends Command
{
    protected $signature = 'finance:populate-cashflow
        {--store_id= : Populate only one store ID}
        {--dry-run : Preview required top-ups without writing data}';

    protected $description = 'Populate finance operating accounts using existing DB required payouts.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $storeId = $this->option('store_id');

        $stores = DB::table('stores')
            ->select('id', 'name')
            ->when($storeId, fn ($query) => $query->where('id', (int) $storeId))
            ->orderBy('id')
            ->get();

        if ($stores->isEmpty()) {
            $this->warn('No stores found for the provided filter.');
            return self::SUCCESS;
        }

        $cashflow = new CashflowService();
        $rows = [];

        foreach ($stores as $store) {
            $requiredInvoices = (float) DB::table('invoices')
                ->where('store_id', (int) $store->id)
                ->whereNull('deleted_at')
                ->where('status', 'approved')
                ->where('payment_status', 'pending')
                ->selectRaw('COALESCE(SUM(CASE WHEN payment_amount IS NOT NULL AND payment_amount > 0 THEN payment_amount WHEN net_amount IS NOT NULL AND net_amount > 0 THEN net_amount ELSE invoice_amount END), 0) as total')
                ->value('total');

            $requiredExpenses = (float) DB::table('finance_expenses')
                ->where('store_id', (int) $store->id)
                ->whereNull('deleted_at')
                ->where('status', 'approved')
                ->sum('amount');

            $requiredPayroll = (float) DB::table('payrolls as p')
                ->join('employees as e', 'e.id', '=', 'p.employee_id')
                ->where('e.store_id', (int) $store->id)
                ->where('p.status', 'released')
                ->sum('p.net_salary');

            $requiredTotal = round($requiredInvoices + $requiredExpenses + $requiredPayroll, 2);

            $account = $cashflow->getOrCreateOperatingAccount((int) $store->id, null);
            $currentBalance = round((float) $account->current_balance, 2);
            $topUpNeeded = round(max($requiredTotal - $currentBalance, 0), 2);

            if (!$dryRun && $topUpNeeded > 0) {
                $cashflow->topUp(
                    (int) $store->id,
                    $topUpNeeded,
                    null,
                    'Bootstrap required cashflow balance from existing approved payouts',
                    'system',
                    [
                        'source' => 'bootstrap_required_payouts',
                        'required_breakdown' => [
                            'approved_invoices_pending' => $requiredInvoices,
                            'approved_expenses' => $requiredExpenses,
                            'released_payroll' => $requiredPayroll,
                        ],
                    ]
                );
            }

            $rows[] = [
                'store_id' => (int) $store->id,
                'store_name' => (string) ($store->name ?? ('Store #' . $store->id)),
                'required_total' => number_format($requiredTotal, 2, '.', ''),
                'current_balance' => number_format($currentBalance, 2, '.', ''),
                'topup_applied' => number_format($topUpNeeded, 2, '.', ''),
                'mode' => $dryRun ? 'dry-run' : ($topUpNeeded > 0 ? 'updated' : 'no-change'),
            ];
        }

        $this->table(
            ['store_id', 'store_name', 'required_total', 'current_balance', 'topup_applied', 'mode'],
            $rows
        );

        $this->info($dryRun
            ? 'Dry-run completed. No data written.'
            : 'Cashflow population completed.');

        return self::SUCCESS;
    }
}
