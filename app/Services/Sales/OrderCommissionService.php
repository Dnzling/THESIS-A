<?php

namespace App\Services\Sales;

use App\Models\Ecommerce\EcommerceOrder;
use App\Models\Admin\SubscriptionPlan;
use App\Models\PlatformRevenue;
use App\Models\Sales\SalesOrder;
use App\Models\Store\Store;

class OrderCommissionService
{
    /**
     * Calculate a commission snapshot for an order.
     *
     * Commission is deducted from the store's merchandise proceeds. Shipping
     * is excluded and the customer's order total is not increased.
     */
    public function calculate(
        int $storeId,
        float $subtotal,
        float $discountAmount,
        float $totalAmount
    ): array {
        $store = Store::query()->find($storeId);
        $planId = (int) ($store?->getRawOriginal('subscription_tier') ?? 0);
        $percentage = max(0, (float) (
            SubscriptionPlan::query()->whereKey($planId)->value('commission_percentage') ?? 0
        ));
        $baseAmount = max(0, $subtotal - $discountAmount);
        $commissionAmount = round($baseAmount * ($percentage / 100), 2);

        return [
            'commission_percentage' => round($percentage, 2),
            'commission_base_amount' => round($baseAmount, 2),
            'commission_amount' => $commissionAmount,
            'store_net_amount' => round(max(0, $totalAmount - $commissionAmount), 2),
        ];
    }

    public function record(EcommerceOrder|SalesOrder $order, bool $collected): ?PlatformRevenue
    {
        $amount = (float) $order->commission_amount;
        if ($amount <= 0) {
            return null;
        }

        $orderType = $order instanceof EcommerceOrder ? 'ecommerce' : 'pos';

        $revenue = PlatformRevenue::firstOrCreate(
            ['reference' => "commission:{$orderType}:{$order->id}"],
            [
                'store_id' => $order->store_id,
                'source' => 'order_commission',
                'amount' => $amount,
                'currency' => 'PHP',
                'metadata' => [
                    'order_type' => $orderType,
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
