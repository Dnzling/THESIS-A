<?php

namespace App\Services\Logistics;

use App\Models\CRM\EcommerceOrderReturn;
use App\Models\Store\StoreDeliveryFeeSetting;

class ReturnChargeService
{
    public function __construct(private readonly BulkyProductService $bulkyProducts) {}

    public function calculate(EcommerceOrderReturn $return, string $responsibility): array
    {
        $return->loadMissing(['order.delivery', 'orderItem.product', 'orderItem.branchInventory.variation']);
        $quantity = max(1, (int) $return->requested_quantity);
        $setting = StoreDeliveryFeeSetting::query()->where('store_id', $return->store_id)->first();
        $product = $return->orderItem?->product;
        $variation = $return->orderItem?->branchInventory?->variation;
        $unitWeight = (float) ($variation?->weight_kg ?? $product?->weight_kg ?? 0);
        $totalWeight = $unitWeight * $quantity;
        $distance = (float) ($return->order?->delivery?->distance_km ?? 0);
        $isBulky = $setting && $this->bulkyProducts->isBulky($product, $setting);
        if ($setting && $variation && !$isBulky) {
            $sides = array_filter([(float) $variation->length_cm, (float) $variation->width_cm, (float) $variation->height_cm], fn ($side) => $side > 0);
            $isBulky = ($setting->bulky_weight_threshold_kg !== null && $unitWeight >= (float) $setting->bulky_weight_threshold_kg)
                || ($sides && $setting->bulky_longest_side_threshold_cm !== null && max($sides) >= (float) $setting->bulky_longest_side_threshold_cm)
                || (count($sides) === 3 && $setting->bulky_volume_threshold_cbm !== null && array_product($sides) / 1000000 >= (float) $setting->bulky_volume_threshold_cbm);
        }

        $base = $setting && $setting->is_active ? (float) $setting->base_fee : 0.0;
        $distanceFee = $setting && $setting->is_active ? $distance * (float) $setting->per_km_fee : 0.0;
        $weightFee = $setting && $setting->is_active ? $totalWeight * (float) $setting->per_kg_fee : 0.0;
        $bulkyFee = $setting && $setting->is_active && $isBulky ? $quantity * (float) $setting->bulky_item_surcharge : 0.0;
        $raw = $base + $distanceFee + $weightFee + $bulkyFee;
        $calculated = $setting && $setting->is_active ? $raw : 0.0;
        $charged = $responsibility === 'customer' ? round($calculated, 2) : 0.0;

        return [
            'fee' => $charged,
            'breakdown' => [
                'responsibility' => $responsibility,
                'waived' => $responsibility === 'store',
                'calculated_fee' => round($calculated, 2),
                'base_fee' => round($base, 2),
                'distance_km' => round($distance, 2),
                'distance_fee' => round($distanceFee, 2),
                'unit_weight_kg' => round($unitWeight, 2),
                'total_weight_kg' => round($totalWeight, 2),
                'weight_fee' => round($weightFee, 2),
                'bulky' => $isBulky,
                'bulky_quantity' => $isBulky ? $quantity : 0,
                'bulky_surcharge' => round($bulkyFee, 2),
            ],
        ];
    }
}
