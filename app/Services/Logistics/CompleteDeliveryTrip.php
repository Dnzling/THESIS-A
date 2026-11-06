<?php

namespace App\Services\Logistics;

use App\Models\Logistics\DeliveryTrip;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CompleteDeliveryTrip
{
    public function afterDeliveryUpdate(Model $delivery): void
    {
        if (! $delivery->wasChanged('status') && ! $delivery->wasChanged('delivery_status')) {
            return;
        }

        $status = strtolower((string) ($delivery->delivery_status ?? $delivery->status));
        $tripId = (int) ($delivery->trip_id ?? 0);
        if ($status !== 'delivered' || $tripId === 0) {
            return;
        }

        DB::transaction(function () use ($delivery, $tripId): void {
            $trip = DeliveryTrip::query()->whereKey($tripId)->lockForUpdate()->first();
            if (! $trip || (int) $trip->store_id !== (int) $delivery->store_id
                || ! in_array($trip->status, ['in_transit', 'out_for_delivery'], true)) {
                return;
            }

            $total = $trip->ecommerceDeliveries()->count()
                + $trip->salesDeliveries()->count()
                + $trip->returnPickups()->count()
                + $trip->stockTransfers()->count();
            if ($total === 0) {
                return;
            }

            $remaining = $trip->ecommerceDeliveries()->where('status', '!=', 'delivered')->exists()
                || $trip->salesDeliveries()->where('status', '!=', 'delivered')->exists()
                || $trip->returnPickups()->where('status', '!=', 'delivered')->exists()
                || $trip->stockTransfers()->where(function ($query) {
                    $query->whereNull('delivery_status')->orWhere('delivery_status', '!=', 'delivered');
                })->exists();
            if ($remaining) {
                return;
            }

            $trip->status = 'completed';
            $trip->updated_by = $delivery->updated_by ?: $trip->updated_by;
            $trip->save();
        });
    }
}
