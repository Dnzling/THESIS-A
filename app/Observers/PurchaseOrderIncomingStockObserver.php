<?php

namespace App\Observers;

use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Services\Inventory\IncomingPurchaseOrderStockService;

class PurchaseOrderIncomingStockObserver
{
    public function saved(PurchaseOrder $order): void
    {
        if ($order->wasChanged('status') || $order->wasChanged('branch_id')) {
            $service = app(IncomingPurchaseOrderStockService::class);
            $service->syncPurchaseOrder($order);
            if ($order->wasChanged('branch_id')) {
                foreach ($order->items as $item) {
                    $service->syncItem((int) $order->store_id, (int) $order->getOriginal('branch_id'), (int) $item->product_id, $item->variation_id);
                }
            }
        }
    }

    public function deleted(PurchaseOrder $order): void
    {
        app(IncomingPurchaseOrderStockService::class)->syncPurchaseOrder($order);
    }
}
