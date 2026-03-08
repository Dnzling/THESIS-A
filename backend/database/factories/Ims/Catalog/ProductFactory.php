<?php

namespace Database\Factories\Ims\Catalog;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Ims\Catalog\Category;
use App\Models\Ims\Catalog\Material;
use App\Models\Ims\Catalog\Color;

class ProductFactory extends Factory
{
    public function definition()
    {
        $category = Category::inRandomOrder()->first() ?? Category::factory()->create();
        $material = Material::inRandomOrder()->first() ?? Material::factory()->create();
        $color = Color::inRandomOrder()->first() ?? Color::factory()->create();
        
        $furnitureTypes = [
            ['name' => 'Sofa', 'width' => 220, 'height' => 85, 'depth' => 95, 'weight' => 80],
            ['name' => 'Dining Table', 'width' => 180, 'height' => 75, 'depth' => 90, 'weight' => 50],
            ['name' => 'Office Chair', 'width' => 60, 'height' => 110, 'depth' => 60, 'weight' => 15],
            ['name' => 'Bed Frame', 'width' => 200, 'height' => 60, 'depth' => 150, 'weight' => 70],
            ['name' => 'Bookshelf', 'width' => 120, 'height' => 180, 'depth' => 40, 'weight' => 35],
            ['name' => 'Coffee Table', 'width' => 120, 'height' => 45, 'depth' => 60, 'weight' => 25],
            ['name' => 'Wardrobe', 'width' => 200, 'height' => 210, 'depth' => 60, 'weight' => 120],
            ['name' => 'Dining Chair', 'width' => 50, 'height' => 95, 'depth' => 55, 'weight' => 8],
            ['name' => 'TV Console', 'width' => 180, 'height' => 50, 'depth' => 40, 'weight' => 30],
            ['name' => 'Study Desk', 'width' => 140, 'height' => 75, 'depth' => 70, 'weight' => 40],
        ];
        
        $furniture = $this->faker->randomElement($furnitureTypes);
        $costPrice = $this->faker->numberBetween(1000, 10000);
        $sellingPrice = $costPrice * 1.5; // 50% markup
        
        $categoryCode = strtoupper(substr($category->name, 0, 3));
        $materialCode = $material->code;
        $colorCode = $color->code;
        $unique = str_pad($this->faker->numberBetween(1, 999), 3, '0', STR_PAD_LEFT);
        
        return [
            'sku' => "{$categoryCode}-{$materialCode}-{$colorCode}-{$unique}",
            'name' => "{$furniture['name']} {$material->name} {$color->name}",
            'description' => $this->faker->paragraph(3),
            'category_id' => $category->id,
            'material_id' => $material->id,
            'color_id' => $color->id,
            'width_cm' => $furniture['width'],
            'height_cm' => $furniture['height'],
            'depth_cm' => $furniture['depth'],
            'weight_kg' => $furniture['weight'],
            'cost_price' => $costPrice,
            'selling_price' => $sellingPrice,
            'profit_margin' => (($sellingPrice - $costPrice) / $costPrice) * 100,
            'min_stock_level' => 5,
            'reorder_point' => 10,
            'has_3d_model' => $this->faker->boolean(30),
            'is_active' => $this->faker->boolean(90),
        ];
    }
    
    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }
    
    public function with3dModel()
    {
        return $this->state(function (array $attributes) {
            return [
                'has_3d_model' => true,
                'model_file_path' => '3d-models/' . str()::slug($attributes['name']) . '.glb',
            ];
        });
    }
    
    public function lowStock()
    {
        return $this->state(function (array $attributes) {
            return [
                'min_stock_level' => 10,
                'reorder_point' => 15,
            ];
        });
    }
    
    public function sofa()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => $this->faker->randomElement(['Leather Sofa', 'Fabric Sofa', 'Sectional Sofa']),
                'width_cm' => 220,
                'height_cm' => 85,
                'depth_cm' => 95,
                'weight_kg' => 80,
            ];
        });
    }
    
    public function chair()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => $this->faker->randomElement(['Office Chair', 'Dining Chair', 'Accent Chair']),
                'width_cm' => 60,
                'height_cm' => 110,
                'depth_cm' => 60,
                'weight_kg' => 15,
            ];
        });
    }
}