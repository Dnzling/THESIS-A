<?php

namespace App\Services\Inventory;

use App\Models\Inventory\BranchInventory;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class IncomingPurchaseOrderStockService
{
    public const ACTIVE_STATUSES = [
        'pending_receipt',
        'partially_received',
        'supplier_accepted',
        'in_transit',
        'out_for_delivery',
        'delivered',
    ];

    public function syncPurchaseOrder(PurchaseOrder $order): void
    {
        foreach ($order->items()->select('product_id', 'variation_id')->distinct()->get() as $item) {
            $this->syncItem((int) $order->store_id, (int) $order->branch_id, (int) $item->product_id, $item->variation_id);
        }
    }

    public function syncItem(int $storeId, int $branchId, int $productId, ?int $variationId): void
    {
        $incoming = (int) DB::table('purchase_order_items as poi')
            ->join('purchase_orders as po', 'po.id', '=', 'poi.purchase_order_id')
            ->where('po.store_id', $storeId)
            ->where('po.branch_id', $branchId)
            ->whereNull('po.deleted_at')
            ->whereIn('po.status', self::ACTIVE_STATUSES)
            ->where('poi.product_id', $productId)
            ->when($variationId === null,
                fn ($query) => $query->whereNull('poi.variation_id'),
                fn ($query) => $query->where('poi.variation_id', $variationId))
            ->selectRaw('COALESCE(SUM(GREATEST(0, poi.quantity_ordered - poi.quantity_received - poi.quantity_rejected)), 0) as incoming')
            ->value('incoming');

        $key = [
            'store_id' => $storeId,
            'branch_id' => $branchId,
            'product_id' => $productId,
            'variation_id' => $variationId,
        ];
        $stock = BranchInventory::query()->where($key)->first();
        if (!$stock && $incoming === 0) {
            return;
        }

        $stock ??= BranchInventory::firstOrCreate($key, [
            'quantity_on_hand' => 0,
            'quantity_available' => 0,
            'quantity_incoming' => 0,
            'stock_status' => 'out_of_stock',
        ]);
        if ((int) $stock->quantity_incoming !== $incoming) {
            $stock->quantity_incoming = $incoming;
            $stock->save();
        }
    }
}
