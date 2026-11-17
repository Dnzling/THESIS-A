<?php

namespace App\Observers;

use App\Models\Procurement\PurchaseOrder\PurchaseOrderItem;
use App\Services\Inventory\IncomingPurchaseOrderStockService;

class PurchaseOrderItemIncomingStockObserver
{
    public function saved(PurchaseOrderItem $item): void
    {
        $order = $item->purchaseOrder;
        if ($order) {
            $service = app(IncomingPurchaseOrderStockService::class);
            $service->syncItem(
                (int) $order->store_id,
                (int) $order->branch_id,
                (int) $item->product_id,
                $item->variation_id
            );
            if ($item->wasChanged('product_id') || $item->wasChanged('variation_id')) {
                $service->syncItem(
                    (int) $order->store_id,
                    (int) $order->branch_id,
                    (int) $item->getOriginal('product_id'),
                    $item->getOriginal('variation_id')
                );
            }
        }
    }

    public function deleted(PurchaseOrderItem $item): void
    {
        $this->saved($item);
    }
}
