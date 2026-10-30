<?php

namespace App\Services\Logistics;

use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;
use App\Models\Store\StoreDeliveryFeeSetting;

class BulkyProductService
{
    public function isBulky(?Product $product, StoreDeliveryFeeSetting $setting, ?ProductVariation $variation = null): bool
    {
        if (!$product) return false;
        if ($product->is_bulky !== null) return (bool) $product->is_bulky;

        $weight = (float) ($variation?->weight_kg ?? $product->weight_kg ?? 0);
        if ($setting->bulky_weight_threshold_kg !== null && $weight > 0 && $weight >= (float) $setting->bulky_weight_threshold_kg) return true;

        $dimensions = fn (string $field) => (float) ($variation?->{$field} ?? $product->{$field} ?? 0);
        $sides = array_filter([$dimensions('length_cm'), $dimensions('width_cm'), $dimensions('height_cm'), $dimensions('depth_cm')], fn ($side) => $side > 0);
        if ($sides && $setting->bulky_longest_side_threshold_cm !== null && max($sides) >= (float) $setting->bulky_longest_side_threshold_cm) return true;
        $volumeSides = array_filter([$dimensions('length_cm'), $dimensions('width_cm'), $dimensions('height_cm')], fn ($side) => $side > 0);
        if (count($volumeSides) === 3 && $setting->bulky_volume_threshold_cbm !== null
            && array_product($volumeSides) / 1000000 >= (float) $setting->bulky_volume_threshold_cbm) return true;

        return false;
    }
}
