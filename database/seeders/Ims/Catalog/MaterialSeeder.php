<?php

namespace Database\Seeders\Ims\Catalog;

use Illuminate\Database\Seeder;
use App\Models\Ims\Catalog\Material;

class MaterialSeeder extends Seeder
{
    /**
     * Common furniture materials with prices
     */
    private $materials = [
        ['name' => 'Oak Wood', 'code' => 'OAK', 'unit_price' => 150.00, 'unit' => 'sqm'],
        ['name' => 'Leather', 'code' => 'LEA', 'unit_price' => 80.00, 'unit' => 'sqm'],
        ['name' => 'Fabric', 'code' => 'FAB', 'unit_price' => 25.00, 'unit' => 'sqm'],
        ['name' => 'Metal Frame', 'code' => 'MET', 'unit_price' => 45.00, 'unit' => 'pcs'],
        ['name' => 'Plywood', 'code' => 'PLY', 'unit_price' => 35.00, 'unit' => 'sheet'],
        ['name' => 'Foam', 'code' => 'FOA', 'unit_price' => 120.00, 'unit' => 'cubic meter'],
        ['name' => 'Glass', 'code' => 'GLS', 'unit_price' => 95.00, 'unit' => 'sqm'],
        ['name' => 'Marble', 'code' => 'MAR', 'unit_price' => 300.00, 'unit' => 'slab'],
        ['name' => 'Velvet', 'code' => 'VEL', 'unit_price' => 65.00, 'unit' => 'yard'],
        ['name' => 'Stainless Steel', 'code' => 'SS', 'unit_price' => 55.00, 'unit' => 'kg'],
        ['name' => 'Walnut Wood', 'code' => 'WLN', 'unit_price' => 180.00, 'unit' => 'sqm'],
        ['name' => 'Mahogany', 'code' => 'MGN', 'unit_price' => 220.00, 'unit' => 'sqm'],
        ['name' => 'Teak Wood', 'code' => 'TEK', 'unit_price' => 200.00, 'unit' => 'sqm'],
        ['name' => 'Rattan', 'code' => 'RAT', 'unit_price' => 40.00, 'unit' => 'roll'],
        ['name' => 'Wicker', 'code' => 'WIC', 'unit_price' => 35.00, 'unit' => 'roll'],
        ['name' => 'Polyester Fabric', 'code' => 'POL', 'unit_price' => 20.00, 'unit' => 'sqm'],
        ['name' => 'Cotton Fabric', 'code' => 'COT', 'unit_price' => 30.00, 'unit' => 'sqm'],
        ['name' => 'Aluminum', 'code' => 'ALU', 'unit_price' => 50.00, 'unit' => 'kg'],
        ['name' => 'Brass Fittings', 'code' => 'BRS', 'unit_price' => 75.00, 'unit' => 'pcs'],
        ['name' => 'Crystal', 'code' => 'CRY', 'unit_price' => 150.00, 'unit' => 'pcs'],
    ];

    public function run()
    {
        foreach ($this->materials as $material) {
            Material::firstOrCreate(
                ['code' => $material['code']],
                [
                    'name' => $material['name'],
                    'unit_price' => $material['unit_price'],
                    'unit_of_measure' => $material['unit'],
                    'description' => $this->getDescription($material['name']),
                    'is_active' => true,
                ]
            );
        }
    }
    
    private function getDescription($materialName)
    {
        $descriptions = [
            'Oak Wood' => 'Premium quality oak wood for furniture making',
            'Leather' => 'Genuine leather upholstery material',
            'Fabric' => 'High-quality fabric for furniture covering',
            'Metal Frame' => 'Sturdy metal frames for furniture structure',
            'Plywood' => 'Multi-layer plywood for furniture panels',
            'Foam' => 'Comfort foam for cushioning and padding',
            'Glass' => 'Tempered glass for tabletops and shelves',
            'Marble' => 'Natural marble for luxury furniture surfaces',
            'Velvet' => 'Luxurious velvet fabric for premium furniture',
            'Stainless Steel' => 'Rust-resistant stainless steel for modern furniture',
        ];
        
        return $descriptions[$materialName] ?? "High-quality {$materialName} for furniture manufacturing";
    }
}