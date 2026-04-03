<?php

namespace App\Services\Sales;

use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesPayment;
use App\Models\Sales\SalesReceipt;
use Illuminate\Support\Facades\DB;

class SalesOrderSettlementService
{
    public function settlePaid(
        SalesOrder $order,
        string $paymentMethod,
        string $paymentReference,
        ?SalesPayment $payment = null
    ): SalesOrder {
        return DB::transaction(function () use ($order, $paymentMethod, $paymentReference, $payment) {
            $lockedOrder = SalesOrder::query()
                ->with(['items'])
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($lockedOrder->payment_status === 'paid') {
                return $lockedOrder->fresh(['items', 'payment', 'receipt', 'branch']);
            }

            foreach ($lockedOrder->items as $item) {
                if (!$item->branch_inventory_id) {
                    continue;
                }

                $inventory = BranchInventory::query()
                    ->lockForUpdate()
                    ->findOrFail($item->branch_inventory_id);

                $qty = (int) $item->quantity;
                if ((int) $inventory->quantity_available < $qty) {
                    throw new \RuntimeException("Insufficient stock to settle order {$lockedOrder->order_number}.");
                }

                $before = (int) $inventory->quantity_available;
                $inventory->quantity_available = max(0, (int) $inventory->quantity_available - $qty);
                $inventory->quantity_on_hand = max(0, (int) $inventory->quantity_on_hand - $qty);
                $inventory->stock_status = $inventory->quantity_available <= 0
                    ? 'out_of_stock'
                    : ($inventory->quantity_available <= $inventory->reorder_point ? 'low_stock' : 'in_stock');
                $inventory->save();

                InventoryTransaction::create([
                    'transaction_number' => 'TXN-SALE-' . now()->format('YmdHis') . '-' . random_int(1000, 9999),
                    'store_id' => $lockedOrder->store_id,
                    'branch_id' => $lockedOrder->branch_id ?: $inventory->branch_id,
                    'product_id' => $item->product_id,
                    'variation_id' => $item->variation_id,
                    'transaction_type' => 'sale',
                    'quantity_before' => $before,
                    'quantity_change' => -$qty,
                    'quantity_after' => (int) $inventory->quantity_available,
                    'reference_type' => 'sales_pos_order',
                    'reference_id' => $lockedOrder->id,
                    'notes' => "POS sale {$lockedOrder->order_number}",
                    'unit_cost' => (float) ($inventory->average_cost ?? 0),
                    'total_value' => (float) $item->line_total,
                    'requires_approval' => false,
                    'approval_status' => 'auto_approved',
                    'created_by' => $lockedOrder->created_by,
                    'transaction_date' => now(),
                ]);
            }

            $receipt = SalesReceipt::firstOrCreate(
                ['sales_order_id' => $lockedOrder->id],
                [
                    'store_id' => $lockedOrder->store_id,
                    'branch_id' => $lockedOrder->branch_id,
                    'sales_payment_id' => $payment?->id,
                    'receipt_number' => $this->nextReceiptNumber(),
                    'amount' => $lockedOrder->total_amount,
                    'currency' => 'PHP',
                    'payment_method' => $paymentMethod,
                    'payment_reference' => $paymentReference,
                    'issued_at' => now(),
                    'issued_by' => $lockedOrder->created_by,
                    'payload' => [
                        'order_number' => $lockedOrder->order_number,
                        'customer_name' => $lockedOrder->customer_name,
                    ],
                ]
            );

            $lockedOrder->update([
                'status' => 'completed',
                'payment_status' => 'paid',
                'payment_channel' => $paymentMethod,
                'payment_reference' => $paymentReference,
                'paid_at' => now(),
                'receipt_number' => $receipt->receipt_number,
            ]);

            if ($payment) {
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'provider_reference' => $paymentReference ?: $payment->provider_reference,
                ]);
            }

            return $lockedOrder->fresh(['items', 'payment', 'receipt', 'branch']);
        });
    }

    public function markPaymentFailed(SalesOrder $order, ?SalesPayment $payment = null): SalesOrder
    {
        return DB::transaction(function () use ($order, $payment) {
            $lockedOrder = SalesOrder::query()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->payment_status !== 'paid') {
                $lockedOrder->update([
                    'payment_status' => 'failed',
                    'status' => 'pending_payment',
                ]);
            }

            if ($payment && $payment->status !== 'paid') {
                $payment->update(['status' => 'failed']);
            }

            return $lockedOrder->fresh(['items', 'payment', 'receipt', 'branch']);
        });
    }

    private function nextReceiptNumber(): string
    {
        $prefix = 'OR-' . now()->format('Ymd') . '-';
        $last = SalesReceipt::query()
            ->where('receipt_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('receipt_number');

        $seq = 1;
        if ($last && preg_match('/(\d+)$/', (string) $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}

