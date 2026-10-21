<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('inventory:reorder-suggestions:auto-run')->everyMinute();

Schedule::call(function () {
    \App\Models\Ecommerce\EcommerceOrder::query()
        ->whereIn('payment_method', ['card', 'e_wallet'])
        ->whereIn('payment_status', ['unpaid', 'failed'])
        ->whereNotNull('pending_snapshot')
        ->where('created_at', '<=', now()->subMinutes(30))
        ->chunkById(50, function ($orders) {
            foreach ($orders as $order) {
                $snapshot = (array) ($order->pending_snapshot ?? []);
                if (empty($snapshot['stock_reserved'])) {
                    continue;
                }

                $intent = \App\Models\PaymongoIntent::query()
                    ->where('payable_type', 'ecommerce_order')
                    ->where('payable_id', $order->id)
                    ->latest('id')->first();
                $expired = !$intent || in_array(strtolower((string) $intent->status), ['expired', 'cancelled', 'canceled', 'failed'], true);
                if ($intent && str_starts_with((string) $intent->payment_intent_id, 'cs_')) {
                    $session = app(\App\Services\Payment\PaymongoService::class)
                        ->retrieveCheckoutSession((string) $intent->payment_intent_id);
                    $payments = (array) data_get($session, 'data.attributes.payments', []);
                    $paid = collect($payments)->contains(fn ($payment) => strtolower((string) data_get($payment, 'attributes.status')) === 'paid');
                    if ($paid) {
                        continue;
                    }
                    $expired = in_array(strtolower((string) data_get($session, 'data.attributes.status')), ['expired', 'cancelled', 'canceled', 'failed'], true);
                }

                if (!$expired) {
                    continue;
                }

                try {
                    \Illuminate\Support\Facades\DB::transaction(function () use ($order) {
                        $fresh = \App\Models\Ecommerce\EcommerceOrder::query()->lockForUpdate()->find($order->id);
                        if (!$fresh || !in_array($fresh->payment_status, ['unpaid', 'failed'], true) || empty(((array) ($fresh->pending_snapshot ?? []))['stock_reserved'])) {
                            return;
                        }
                        app(\App\Services\Ecommerce\EcommerceStockReservationService::class)->release($fresh);
                        $fresh->status = 'cancelled';
                        $fresh->payment_status = 'failed';
                        $fresh->save();
                    });
                } catch (\Throwable $exception) {
                    report($exception);
                }
            }
        });
})->name('ecommerce-expired-reservations')->everyFiveMinutes()->withoutOverlapping();

Schedule::call(function () {
    \App\Models\Store\Store::query()
        ->where('subscription_status', 'trial')
        ->where('trial_ends_at', '<=', now())
        ->chunkById(100, function ($stores) {
            foreach ($stores as $store) {
                app(\App\Services\Store\StoreTrialService::class)->expireIfNeeded($store);
            }
        });
})->hourly();

Schedule::call(function () {
    \App\Models\Hr\Employee::query()
        ->whereNotNull('resignation_date')
        ->whereDate('last_working_day', '<', now('Asia/Manila')->toDateString())
        ->where('status', '!=', 'terminated')
        ->chunkById(100, function ($employees) {
            foreach ($employees as $employee) {
                \Illuminate\Support\Facades\DB::transaction(function () use ($employee) {
                    $employee->update([
                        'status' => 'terminated',
                        'termination_date' => $employee->last_working_day,
                        'termination_reason' => $employee->resignation_reason,
                    ]);
                    $employee->user?->update(['is_active' => false]);
                });
            }
        });
})->dailyAt('00:05')->timezone('Asia/Manila');
