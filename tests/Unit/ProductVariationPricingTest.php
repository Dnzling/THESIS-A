<?php

namespace Tests\Unit;

use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;
use PHPUnit\Framework\TestCase;

class ProductVariationPricingTest extends TestCase
{
    public function test_variation_inherits_parent_selling_price_when_no_override_is_set(): void
    {
        $product = new Product(['base_price' => 5500]);
        $variation = new ProductVariation(['base_price' => null, 'discounted_price' => null, 'price_adjustment' => 0]);
        $variation->setRelation('product', $product);

        $this->assertEquals(5500, $variation->final_price);
    }

    public function test_variation_selling_price_override_takes_precedence(): void
    {
        $product = new Product(['base_price' => 5500]);
        $variation = new ProductVariation(['base_price' => 6200, 'discounted_price' => null]);
        $variation->setRelation('product', $product);

        $this->assertEquals(6200, $variation->final_price);
    }
}
