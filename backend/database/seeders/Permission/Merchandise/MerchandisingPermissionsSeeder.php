<?php

namespace Database\Seeders\Permission;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MerchandisingPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $permissions = [
            // Dashboard
            ['name' => 'merchandising.dashboard.view', 'display_name' => 'View Product Catalog Dashboard', 'module' => 'merchandising'],

            // Products
            ['name' => 'merchandising.products.view', 'display_name' => 'View Products', 'module' => 'merchandising'],
            ['name' => 'merchandising.products.create', 'display_name' => 'Create Products', 'module' => 'merchandising'],
            ['name' => 'merchandising.products.edit', 'display_name' => 'Edit Products', 'module' => 'merchandising'],
            ['name' => 'merchandising.products.delete', 'display_name' => 'Delete Products', 'module' => 'merchandising'],

            // Variations
            ['name' => 'merchandising.variations.view', 'display_name' => 'View Product Variations', 'module' => 'merchandising'],
            ['name' => 'merchandising.variations.edit', 'display_name' => 'Edit Product Variations', 'module' => 'merchandising'],

            // Assets (3D Models)
            ['name' => 'merchandising.assets.view', 'display_name' => 'View 3D Models & Assets', 'module' => 'merchandising'],
            ['name' => 'merchandising.assets.upload', 'display_name' => 'Upload Assets', 'module' => 'merchandising'],
            ['name' => 'merchandising.assets.delete', 'display_name' => 'Delete Assets', 'module' => 'merchandising'],

            // Inventory
            ['name' => 'merchandising.inventory.view', 'display_name' => 'View Inventory Status', 'module' => 'merchandising'],
            ['name' => 'merchandising.inventory.edit', 'display_name' => 'Update Inventory', 'module' => 'merchandising'],

            // Categories
            ['name' => 'merchandising.categories.view', 'display_name' => 'View Categories', 'module' => 'merchandising'],
            ['name' => 'merchandising.categories.edit', 'display_name' => 'Edit Categories', 'module' => 'merchandising'],

            // Attributes
            ['name' => 'merchandising.attributes.view', 'display_name' => 'View Product Attributes', 'module' => 'merchandising'],
            ['name' => 'merchandising.attributes.edit', 'display_name' => 'Edit Product Attributes', 'module' => 'merchandising'],

            // Pricing
            ['name' => 'merchandising.pricing.view', 'display_name' => 'View Pricing', 'module' => 'merchandising'],
            ['name' => 'merchandising.pricing.edit', 'display_name' => 'Edit Pricing', 'module' => 'merchandising'],

            // Reports
            ['name' => 'merchandising.reports.view', 'display_name' => 'View Sales Reports', 'module' => 'merchandising'],
        ];

        foreach ($permissions as $permission) {
            $permission['is_active'] = true;
            $permission['created_at'] = $now;
            $permission['updated_at'] = $now;
            DB::table('permissions')->insert($permission);
        }
    }
}