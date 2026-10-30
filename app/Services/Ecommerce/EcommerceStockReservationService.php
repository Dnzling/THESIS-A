<?php

namespace App\Services\Ecommerce;

use App\Models\Ecommerce\EcommerceOrder;
use App\Models\Inventory\BranchInventory;
use Illuminate\Support\Facades\DB;

class EcommerceStockReservationService
{
    public function release(EcommerceOrder $order): void
    {
        DB::transaction(function () use ($order) {
            $order = EcommerceOrder::query()->lockForUpdate()->findOrFail($order->id);
            $snapshot = (array) ($order->pending_snapshot ?? []);
            if (empty($snapshot['stock_reserved'])) {
                return;
            }

            foreach ($this->reservedRows($order, $snapshot) as $row) {
                $inventory = BranchInventory::query()->lockForUpdate()->find($row['inventory_id']);
                if (!$inventory) {
                    throw new \RuntimeException('Reserved inventory no longer exists.');
                }
                if ((int) $inventory->quantity_reserved < $row['quantity']) {
                    throw new \RuntimeException('Reserved inventory is below the order quantity.');
                }
                $inventory->quantity_reserved -= $row['quantity'];
                $inventory->quantity_available += $row['quantity'];
                $inventory->updateStockStatus();
            }

            $order->pending_snapshot = null;
            $order->pending_cart_id = null;
            $order->save();
        });
    }

    public function consume(EcommerceOrder $order): void
    {
        DB::transaction(function () use ($order) {
            $order = EcommerceOrder::query()->lockForUpdate()->findOrFail($order->id);
            $snapshot = (array) ($order->pending_snapshot ?? []);
            if (empty($snapshot['stock_reserved']) || !empty($snapshot['items'])) {
                return;
            }

            foreach ($this->reservedRows($order, $snapshot) as $row) {
                $inventory = BranchInventory::query()->lockForUpdate()->find($row['inventory_id']);
                if (!$inventory || (int) $inventory->quantity_reserved < $row['quantity']) {
                    throw new \RuntimeException('Reserved inventory is unavailable.');
                }
                $inventory->quantity_reserved -= $row['quantity'];
                $inventory->quantity_on_hand -= $row['quantity'];
                $inventory->updateStockStatus();
            }

            $order->pending_snapshot = null;
            $order->save();
        });
    }

    private function reservedRows(EcommerceOrder $order, array $snapshot): array
    {
        if (!empty($snapshot['items'])) {
            return collect($snapshot['items'])->map(fn ($item) => [
                'inventory_id' => (int) ($item['branch_inventory_id'] ?? 0),
                'quantity' => (int) ($item['quantity'] ?? 0),
            ])->all();
        }

        return $order->items()->get()->map(fn ($item) => [
            'inventory_id' => (int) $item->branch_inventory_id,
            'quantity' => (int) $item->quantity,
        ])->all();
    }
}
