<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DefaultFurnitureCategoryConfigTest extends TestCase
{
    public function test_store_category_presets_have_exactly_two_levels(): void
    {
        $config = require __DIR__.'/../../config/default_furniture_categories.php';
        $catalog = $config['catalog'] ?? [];

        $this->assertCount(12, $catalog);
        $this->assertArrayNotHasKey('Shop by Room', $catalog);
        $this->assertArrayNotHasKey('rooms', $config);

        foreach ($catalog as $category => $subcategories) {
            $this->assertIsString($category);
            $this->assertNotSame('', trim($category));
            $this->assertNotEmpty($subcategories, "{$category} must contain subcategories.");
            $this->assertSame(
                count($subcategories),
                count(array_unique($subcategories)),
                "{$category} contains duplicate subcategories."
            );

            foreach ($subcategories as $subcategory) {
                $this->assertIsString($subcategory);
                $this->assertNotSame('', trim($subcategory));
            }
        }
    }
}
