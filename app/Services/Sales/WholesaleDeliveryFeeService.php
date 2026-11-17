<?php

namespace App\Services\Sales;

use App\Models\Store\Branch;
use App\Models\Store\StoreDeliveryFeeSetting;
use Illuminate\Validation\ValidationException;

class WholesaleDeliveryFeeService
{
    public function estimate(int $storeId, int $branchId, float $subtotal, float $latitude, float $longitude): float
    {
        $branch = Branch::query()->where('store_id', $storeId)->findOrFail($branchId);
        if ($branch->latitude === null || $branch->longitude === null) {
            throw ValidationException::withMessages(['branch_id' => 'Set the fulfilment branch map pin before quoting delivery.']);
        }

        $settings = StoreDeliveryFeeSetting::query()->where('store_id', $storeId)->first();
        if ($settings && !$settings->is_active) {
            throw ValidationException::withMessages(['delivery_required' => 'Delivery is not enabled for this store.']);
        }

        $latDelta = deg2rad($latitude - (float) $branch->latitude);
        $lngDelta = deg2rad($longitude - (float) $branch->longitude);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad((float) $branch->latitude)) * cos(deg2rad($latitude)) * sin($lngDelta / 2) ** 2;
        $distance = 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a));
        if ($settings?->max_delivery_distance_km !== null && $distance > (float) $settings->max_delivery_distance_km) {
            throw ValidationException::withMessages(['delivery_address' => 'The destination is outside the store delivery range.']);
        }
        if ($settings?->free_shipping_min_order !== null && $subtotal >= (float) $settings->free_shipping_min_order) {
            return 0;
        }

        return round((float) ($settings?->base_fee ?? 100) + $distance * (float) ($settings?->per_km_fee ?? 10), 2);
    }
}
