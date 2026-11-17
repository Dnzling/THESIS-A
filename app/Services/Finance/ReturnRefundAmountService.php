<?php

namespace App\Services\Finance;

use App\Models\CRM\EcommerceOrderReturn;
use App\Models\Finance\FinanceRefund;

class ReturnRefundAmountService
{
    /**
     * Calculate a return refund from the inspected quantity and the actual order item price,
     * reserving previous requests against the amount paid for the source order.
     *
     * @return array{unit_price: float, quantity: int, item_subtotal: float, order_discount_allocated: float, paid_item_subtotal: float, return_fee: float, requested_amount: float, paid_total: float, previously_reserved: float, available_paid_balance: float, amount: float}
     */
    public function calculate(EcommerceOrderReturn $return, ?int $quantity = null): array
    {
        $return->loadMissing(['order', 'salesOrder', 'orderItem', 'salesOrderItem']);
        $sourceOrder = $return->sourceOrder();
        $sourceItem = $return->sourceOrderItem();
        $quantity ??= (int) ($return->received_quantity ?: $return->requested_quantity ?: 1);
        $unitPrice = round((float) ($sourceItem?->unit_price ?? 0), 2);
        $orderedQuantity = max(1, (int) ($sourceItem?->quantity ?? 1));
        $fullItemSubtotal = (float) ($sourceItem?->line_subtotal
            ?? $sourceItem?->line_total
            ?? ($unitPrice * $orderedQuantity));
        $itemSubtotal = round(max(0, $fullItemSubtotal) * min(max(0, $quantity), $orderedQuantity) / $orderedQuantity, 2);
        $orderSubtotal = max(0.0, (float) ($sourceOrder?->subtotal ?? $fullItemSubtotal));
        $orderDiscount = max(0.0, (float) ($sourceOrder?->discount_amount ?? 0));
        $orderDiscountAllocated = $orderSubtotal > 0
            ? round(min($itemSubtotal, $orderDiscount * ($itemSubtotal / $orderSubtotal)), 2)
            : 0.0;
        $paidItemSubtotal = max(0, round($itemSubtotal - $orderDiscountAllocated, 2));
        $returnFee = round((float) ($return->return_fee ?? 0), 2);
        $requestedAmount = max(0, round($paidItemSubtotal - $returnFee, 2));
        $paidTotal = in_array((string) ($sourceOrder?->payment_status ?? ''), ['paid', 'succeeded', 'completed'], true)
            ? (float) ($sourceOrder?->total_amount ?? 0)
            : 0.0;

        $returnIds = EcommerceOrderReturn::query()->where('store_id', $return->store_id)
            ->when($return->source_type === 'pos', fn ($query) => $query
                ->where('source_type', 'pos')->where('sales_order_id', $sourceOrder?->id),
                fn ($query) => $query->where('source_type', '!=', 'pos')->where('order_id', $sourceOrder?->id))
            ->where('id', '!=', $return->id)->pluck('id');

        $previouslyReserved = $returnIds->isEmpty() ? 0.0 : (float) FinanceRefund::query()
            ->where('store_id', $return->store_id)
            ->where('order_type', 'ecommerce_return')
            ->whereIn('order_id', $returnIds)
            ->whereNotIn('status', ['rejected', 'transfer_failed'])
            ->sum('amount');

        $available = max(0, round($paidTotal - $previouslyReserved, 2));

        return [
            'unit_price' => $unitPrice,
            'quantity' => max(0, $quantity),
            'item_subtotal' => $itemSubtotal,
            'order_discount_allocated' => $orderDiscountAllocated,
            'paid_item_subtotal' => $paidItemSubtotal,
            'return_fee' => $returnFee,
            'requested_amount' => $requestedAmount,
            'paid_total' => round($paidTotal, 2),
            'previously_reserved' => round($previouslyReserved, 2),
            'available_paid_balance' => $available,
            'amount' => min($requestedAmount, $available),
        ];
    }
}
