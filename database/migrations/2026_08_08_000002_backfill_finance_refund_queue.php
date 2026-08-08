<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $returns = DB::table('ecommerce_order_returns as returns')
            ->join('ecommerce_orders as orders', 'orders.id', '=', 'returns.order_id')
            ->join('ecommerce_order_items as items', 'items.id', '=', 'returns.order_item_id')
            ->where('returns.return_type', 'refund')
            ->whereIn('returns.status', ['approved', 'received', 'refund_pending'])
            ->select([
                'returns.id', 'returns.store_id', 'returns.status', 'returns.requested_quantity',
                'orders.order_number', 'orders.shipping_name', 'items.unit_price',
            ])
            ->get();

        foreach ($returns as $return) {
            DB::table('sales_refunds')->updateOrInsert(
                [
                    'store_id' => $return->store_id,
                    'order_type' => 'ecommerce_return',
                    'order_id' => $return->id,
                ],
                [
                    'order_number' => $return->order_number,
                    'customer_name' => $return->shipping_name,
                    'reason' => 'Approved customer return #' . $return->id,
                    'amount' => round((float) $return->unit_price * (int) $return->requested_quantity, 2),
                    'status' => in_array($return->status, ['received', 'refund_pending'], true)
                        ? 'pending'
                        : 'pending_inspection',
                    'notes' => $return->status === 'approved'
                        ? 'Awaiting Inventory inspection before Finance can release the refund.'
                        : 'Inventory inspection complete. Ready for Finance approval.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('sales_refunds')
            ->where('order_type', 'ecommerce_return')
            ->where('status', 'pending_inspection')
            ->delete();
    }
};
