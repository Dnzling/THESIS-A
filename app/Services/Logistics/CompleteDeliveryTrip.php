<?php

namespace App\Services\Logistics;

use App\Models\Logistics\DeliveryTrip;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CompleteDeliveryTrip
{
    public function afterDeliveryUpdate(Model $delivery): void
    {
        if (! $delivery->wasChanged('status') && ! $delivery->wasChanged('delivery_status') && ! $delivery->wasChanged('replacement_status')) {
            return;
        }

        $tripId = (int) ($delivery->replacement_trip_id ?? $delivery->trip_id ?? 0);
        $this->reconcile($tripId, (int) $delivery->store_id, (int) ($delivery->updated_by ?? 0));
    }

    public function reconcile(int $tripId, int $storeId, int $actorId = 0): void
    {
        if ($tripId === 0 || $storeId === 0) return;
        DB::transaction(function () use ($tripId, $storeId, $actorId): void {
            $trip = DeliveryTrip::query()->whereKey($tripId)->lockForUpdate()->first();
            if (! $trip || (int) $trip->store_id !== $storeId
                || ! in_array($trip->status, ['in_transit', 'out_for_delivery'], true)) {
                return;
            }

            $total = $trip->ecommerceDeliveries()->count()
                + $trip->salesDeliveries()->count()
                + $trip->returnPickups()->count()
                + $trip->stockTransfers()->count()
                + $trip->replacementReturns()->count()
                + $trip->purchaseOrderShipments()->count();
            if ($total === 0) {
                return;
            }

            $remaining = $trip->ecommerceDeliveries()->whereNotIn('status', ['delivered', 'cancelled', 'failed_delivery', 'ready_for_dispatch'])->exists()
                || $trip->salesDeliveries()->whereNotIn('status', ['delivered', 'cancelled', 'failed_delivery', 'ready_for_dispatch'])->exists()
                || $trip->returnPickups()->whereNotIn('status', ['delivered', 'cancelled', 'failed_delivery', 'ready_for_dispatch'])->exists()
                || $trip->replacementReturns()->whereNotIn('replacement_status', ['delivered', 'replaced', 'delivery_failed', 'reserved'])->exists()
                || $trip->stockTransfers()->whereNotIn('delivery_status', ['delivered', 'cancelled', 'failed_delivery', 'ready_for_dispatch'])->exists()
                || $trip->purchaseOrderShipments()->whereNotIn('status', ['delivered', 'cancelled', 'failed_delivery', 'ready_for_dispatch'])->exists();
            if ($remaining) {
                return;
            }

            $trip->status = 'completed';
            $trip->updated_by = $actorId ?: $trip->updated_by;
            $trip->save();
        });
    }
}
