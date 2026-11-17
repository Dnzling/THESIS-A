<?php

namespace Tests\Unit;

use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;
use App\Models\Store\StoreDeliveryFeeSetting;
use App\Services\Logistics\BulkyProductService;
use PHPUnit\Framework\TestCase;

class BulkyProductServiceTest extends TestCase
{
    public function test_thresholds_classify_products_and_explicit_override_wins(): void
    {
        $service = new BulkyProductService();
        $setting = new StoreDeliveryFeeSetting([
            'bulky_weight_threshold_kg' => 30,
            'bulky_longest_side_threshold_cm' => 180,
            'bulky_volume_threshold_cbm' => 0.5,
        ]);

        $this->assertTrue($service->isBulky(new Product(['weight_kg' => 30]), $setting));
        $this->assertTrue($service->isBulky(new Product(['length_cm' => 180]), $setting));
        $this->assertTrue($service->isBulky(new Product(['length_cm' => 100, 'width_cm' => 100, 'height_cm' => 50]), $setting));
        $this->assertFalse($service->isBulky(new Product(['weight_kg' => 10, 'length_cm' => 100]), $setting));
        $this->assertFalse($service->isBulky(new Product(['weight_kg' => 50, 'is_bulky' => false]), $setting));
        $this->assertTrue($service->isBulky(new Product(['is_bulky' => true]), $setting));
    }

    public function test_variation_shipping_attributes_override_parent_values(): void
    {
        $service = new BulkyProductService();
        $setting = new StoreDeliveryFeeSetting([
            'bulky_weight_threshold_kg' => 30,
            'bulky_longest_side_threshold_cm' => 150,
        ]);
        $product = new Product(['weight_kg' => 10, 'length_cm' => 100]);
        $heavyVariation = new ProductVariation(['weight_kg' => 35]);
        $longVariation = new ProductVariation(['weight_kg' => 10, 'length_cm' => 180]);

        $this->assertTrue($service->isBulky($product, $setting, $heavyVariation));
        $this->assertTrue($service->isBulky($product, $setting, $longVariation));
        $this->assertFalse($service->isBulky($product, $setting));
    }
}
