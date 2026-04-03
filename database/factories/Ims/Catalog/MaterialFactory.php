<?php

namespace Database\Factories\Ims\Catalog;

use Illuminate\Database\Eloquent\Factories\Factory;

class MaterialFactory extends Factory
{
    public function definition()
    {
        $materialTypes = [
            ['name' => 'Oak Wood', 'unit' => 'sqm', 'price' => 150.00],
            ['name' => 'Leather', 'unit' => 'sqm', 'price' => 80.00],
            ['name' => 'Fabric', 'unit' => 'sqm', 'price' => 25.00],
            ['name' => 'Metal Frame', 'unit' => 'pcs', 'price' => 45.00],
            ['name' => 'Plywood', 'unit' => 'sheet', 'price' => 35.00],
            ['name' => 'Foam', 'unit' => 'cubic meter', 'price' => 120.00],
            ['name' => 'Glass', 'unit' => 'sqm', 'price' => 95.00],
            ['name' => 'Marble', 'unit' => 'slab', 'price' => 300.00],
            ['name' => 'Velvet', 'unit' => 'yard', 'price' => 65.00],
            ['name' => 'Stainless Steel', 'unit' => 'kg', 'price' => 55.00],
        ];
        
        $material = $this->faker->randomElement($materialTypes);
        $code = strtoupper(substr(preg_replace('/[^a-z]/i', '', $material['name']), 0, 3));
        
        return [
            'name' => $material['name'],
            'code' => $code,
            'description' => $this->faker->sentence(10),
            'unit_price' => $material['price'],
            'unit_of_measure' => $material['unit'],
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
    
    public function withSupplier($supplierId)
    {
        return $this->state(function (array $attributes) use ($supplierId) {
            return [
                'supplier_id' => $supplierId,
            ];
        });
    }
    
    public function custom($name, $unit, $price)
    {
        return $this->state(function (array $attributes) use ($name, $unit, $price) {
            $code = strtoupper(substr(preg_replace('/[^a-z]/i', '', $name), 0, 3));
            
            return [
                'name' => $name,
                'code' => $code,
                'unit_price' => $price,
                'unit_of_measure' => $unit,
            ];
        });
    }
}