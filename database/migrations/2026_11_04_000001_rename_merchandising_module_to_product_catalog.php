<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('modules')->where('key', 'merchandising')->update([
            'name' => 'Product Catalog',
            'description' => 'Manage product listings, collections, promotions, 3D models, and product performance across branches.',
            'updated_at' => now(),
        ]);

        DB::table('navigation_items')->where('name', 'merchandising.storefront')->update([
            'is_active' => false,
            'parent_id' => null,
            'updated_at' => now(),
        ]);

        DB::table('permissions')->where('name', 'merchandising.storefront.view')->update([
            'is_active' => false,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('modules')->where('key', 'merchandising')->update([
            'name' => 'E-Commerce',
            'description' => 'Manage product listings, collections, promotions, 3D models, and storefront settings.',
            'updated_at' => now(),
        ]);

        DB::table('navigation_items')->where('name', 'merchandising.storefront')->update([
            'is_active' => true,
            'updated_at' => now(),
        ]);

        DB::table('permissions')->where('name', 'merchandising.storefront.view')->update([
            'is_active' => true,
            'updated_at' => now(),
        ]);
    }
};
