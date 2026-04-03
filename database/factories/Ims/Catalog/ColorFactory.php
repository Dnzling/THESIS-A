<?php

namespace Database\Factories\Ims\Catalog;

use Illuminate\Database\Eloquent\Factories\Factory;

class ColorFactory extends Factory
{
    public function definition()
    {
        $colorName = $this->faker->unique()->colorName;
        $colorCode = strtoupper(substr($colorName, 0, 3));
        
        // Common furniture colors with hex codes
        $commonColors = [
            ['name' => 'Black', 'code' => 'BLK', 'hex' => '#000000'],
            ['name' => 'White', 'code' => 'WHT', 'hex' => '#FFFFFF'],
            ['name' => 'Brown', 'code' => 'BRN', 'hex' => '#8B4513'],
            ['name' => 'Beige', 'code' => 'BEI', 'hex' => '#F5F5DC'],
            ['name' => 'Gray', 'code' => 'GRY', 'hex' => '#808080'],
            ['name' => 'Walnut', 'code' => 'WLN', 'hex' => '#773F1A'],
            ['name' => 'Mahogany', 'code' => 'MGN', 'hex' => '#C04000'],
            ['name' => 'Oak', 'code' => 'OAK', 'hex' => '#D2B48C'],
            ['name' => 'Cherry', 'code' => 'CHY', 'hex' => '#9F3810'],
            ['name' => 'Espresso', 'code' => 'ESP', 'hex' => '#4B3832'],
        ];
        
        $color = $this->faker->randomElement($commonColors);
        
        return [
            'name' => $color['name'],
            'code' => $color['code'],
            'hex_code' => $color['hex'],
            'is_active' => $this->faker->boolean(95),
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
    
    public function custom($name, $hexCode)
    {
        return $this->state(function (array $attributes) use ($name, $hexCode) {
            return [
                'name' => $name,
                'code' => strtoupper(substr($name, 0, 3)),
                'hex_code' => $hexCode,
            ];
        });
    }
}