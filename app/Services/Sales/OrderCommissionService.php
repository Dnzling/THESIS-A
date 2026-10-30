<?php

namespace App\Services\Sales;

use App\Models\PlatformRevenue;
use App\Models\Sales\SalesOrder;

class OrderCommissionService
{
    public function record(SalesOrder $order, bool $collected): ?PlatformRevenue
    {
        $amount = (float) $order->commission_amount;
        if ($amount <= 0) {
            return null;
        }

        $revenue = PlatformRevenue::firstOrCreate(
            ['reference' => "commission:pos:{$order->id}"],
            [
                'store_id' => $order->store_id,
                'source' => 'order_commission',
                'amount' => $amount,
                'currency' => 'PHP',
                'metadata' => [
                    'order_type' => 'pos',
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'commission_percentage' => (float) $order->commission_percentage,
                    'commission_base_amount' => (float) $order->commission_base_amount,
                    'store_net_amount' => (float) $order->store_net_amount,
                    'collection_status' => $collected ? 'collected' : 'receivable',
                ],
                'paid_at' => $collected ? now() : null,
            ]
        );

        // A commission may first be recorded as a receivable (for example,
        // COD) and collected later. Keep the existing idempotent row while
        // promoting it to collected revenue when settlement completes.
        if ($collected && !$revenue->paid_at) {
            $metadata = $revenue->metadata ?? [];
            $metadata['collection_status'] = 'collected';
            $revenue->update([
                'metadata' => $metadata,
                'paid_at' => now(),
            ]);
        }

        return $revenue;
    }
}
