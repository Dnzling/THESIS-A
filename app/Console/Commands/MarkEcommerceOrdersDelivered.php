<?php

namespace App\Console\Commands;

use App\Models\Core\User;
use App\Models\Ecommerce\EcommerceOrder;
use App\Models\Ecommerce\EcommerceOrderDelivery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MarkEcommerceOrdersDelivered extends Command
{
    protected $signature = 'ops:mark-ecommerce-delivered
        {order_numbers* : One or more ecommerce order numbers (e.g., ECO-20260411-0015)}
        {--delivered_at= : Delivered timestamp (defaults to now)}
        {--payment_status=paid : Set ecommerce_orders.payment_status (paid|unpaid|refunded|failed)}
        {--dry-run : Show what would change without writing}
    ';

    protected $description = 'Mark one or more ecommerce orders as delivered and ensure ecommerce_order_deliveries is set to delivered.';

    public function handle(): int
    {
        $orderNumbers = array_values(array_unique(array_map('trim', (array) $this->argument('order_numbers'))));
        $deliveredAt = $this->option('delivered_at') ? now()->parse((string) $this->option('delivered_at')) : now();
        $paymentStatus = (string) $this->option('payment_status');
        $dryRun = (bool) $this->option('dry-run');

        $orders = EcommerceOrder::query()
            ->whereIn('order_number', $orderNumbers)
            ->get();

        $found = $orders->pluck('order_number')->all();
        $missing = array_values(array_diff($orderNumbers, $found));

        if (!empty($missing)) {
            $this->warn('Missing orders: ' . implode(', ', $missing));
        }
        if ($orders->isEmpty()) {
            $this->error('No matching orders found.');
            return 1;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . 'Marking ' . $orders->count() . ' order(s) as delivered...');

        $actorId = auth()->id();
        if (!$actorId) {
            $actorId = User::query()->orderBy('id')->value('id');
        }

        $changes = [];

        $apply = function () use ($orders, $deliveredAt, $paymentStatus, $actorId, &$changes): void {
            foreach ($orders as $order) {
                $beforeOrderStatus = (string) $order->status;
                $beforePaymentStatus = (string) $order->payment_status;

                $order->status = 'delivered';
                if ($paymentStatus) {
                    $order->payment_status = $paymentStatus;
                }
                $order->save();

                $delivery = EcommerceOrderDelivery::query()->firstOrNew(['order_id' => $order->id]);
                $delivery->store_id = $delivery->store_id ?: (int) $order->store_id;
                $delivery->status = 'delivered';
                $delivery->delivered_at = $deliveredAt;
                $delivery->updated_by = $actorId;
                if (!$delivery->exists) {
                    $delivery->created_by = $actorId;
                }
                $delivery->save();

                $changes[] = [
                    'order_number' => $order->order_number,
                    'order_status' => "{$beforeOrderStatus} -> delivered",
                    'payment_status' => "{$beforePaymentStatus} -> {$order->payment_status}",
                    'delivery_id' => $delivery->id,
                ];
            }
        };

        if ($dryRun) {
            // Just simulate the output
            foreach ($orders as $order) {
                $deliveryExists = EcommerceOrderDelivery::query()->where('order_id', $order->id)->exists();
                $changes[] = [
                    'order_number' => $order->order_number,
                    'order_status' => ((string) $order->status) . ' -> delivered',
                    'payment_status' => ((string) $order->payment_status) . ' -> ' . $paymentStatus,
                    'delivery' => $deliveryExists ? 'update delivery' : 'create delivery',
                ];
            }
        } else {
            DB::transaction($apply);
        }

        $this->table(array_keys($changes[0] ?? ['order_number' => '']), $changes);
        $this->info('Done.');

        return 0;
    }
}

