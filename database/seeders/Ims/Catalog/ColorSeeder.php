<?php

namespace Database\Seeders\Ims\Catalog;

use Illuminate\Database\Seeder;
use App\Models\Ims\Catalog\Color;

class ColorSeeder extends Seeder
{
    /**
     * Common furniture colors
     */
    private $colors = [
        ['name' => 'Black', 'code' => 'BLK', 'hex_code' => '#000000'],
        ['name' => 'White', 'code' => 'WHT', 'hex_code' => '#FFFFFF'],
        ['name' => 'Brown', 'code' => 'BRN', 'hex_code' => '#8B4513'],
        ['name' => 'Beige', 'code' => 'BEI', 'hex_code' => '#F5F5DC'],
        ['name' => 'Gray', 'code' => 'GRY', 'hex_code' => '#808080'],
        ['name' => 'Charcoal', 'code' => 'CHC', 'hex_code' => '#36454F'],
        ['name' => 'Navy Blue', 'code' => 'NVY', 'hex_code' => '#000080'],
        ['name' => 'Burgundy', 'code' => 'BGY', 'hex_code' => '#800020'],
        ['name' => 'Walnut', 'code' => 'WLN', 'hex_code' => '#773F1A'],
        ['name' => 'Mahogany', 'code' => 'MGN', 'hex_code' => '#C04000'],
        ['name' => 'Oak', 'code' => 'OAK', 'hex_code' => '#D2B48C'],
        ['name' => 'Cherry', 'code' => 'CHY', 'hex_code' => '#9F3810'],
        ['name' => 'Maple', 'code' => 'MPL', 'hex_code' => '#E3B778'],
        ['name' => 'Espresso', 'code' => 'ESP', 'hex_code' => '#4B3832'],
        ['name' => 'Ivory', 'code' => 'IVR', 'hex_code' => '#FFFFF0'],
        ['name' => 'Cream', 'code' => 'CRM', 'hex_code' => '#FFFDD0'],
        ['name' => 'Teal', 'code' => 'TEL', 'hex_code' => '#008080'],
        ['name' => 'Olive Green', 'code' => 'OLV', 'hex_code' => '#808000'],
        ['name' => 'Taupe', 'code' => 'TPE', 'hex_code' => '#483C32'],
        ['name' => 'Slate', 'code' => 'SLT', 'hex_code' => '#708090'],
    ];

    public function run()
    {
        foreach ($this->colors as $color) {
            Color::firstOrCreate(
                ['code' => $color['code']],
                [
                    'name' => $color['name'],
                    'hex_code' => $color['hex_code'],
                    'is_active' => true,
                ]
            );
        }
    }
}