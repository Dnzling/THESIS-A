<?php

namespace App\Services\Logistics;

use App\Models\ProductCatalog\Product;
use App\Models\Store\StoreDeliveryFeeSetting;

class BulkyProductService
{
    public function isBulky(?Product $product, StoreDeliveryFeeSetting $setting): bool
    {
        if (!$product) return false;
        if ($product->is_bulky !== null) return (bool) $product->is_bulky;

        $weight = (float) ($product->weight_kg ?? 0);
        if ($setting->bulky_weight_threshold_kg !== null && $weight > 0 && $weight >= (float) $setting->bulky_weight_threshold_kg) return true;

        $sides = array_filter([(float) ($product->length_cm ?? 0), (float) ($product->width_cm ?? 0), (float) ($product->height_cm ?? 0)], fn ($side) => $side > 0);
        if ($sides && $setting->bulky_longest_side_threshold_cm !== null && max($sides) >= (float) $setting->bulky_longest_side_threshold_cm) return true;
        if (count($sides) === 3 && $setting->bulky_volume_threshold_cbm !== null
            && array_product($sides) / 1000000 >= (float) $setting->bulky_volume_threshold_cbm) return true;

        return false;
    }
}
