<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('modules')->updateOrInsert(['key' => 'merchandising'], [
            'name' => 'E-Commerce',
            'description' => 'Manage product listings, collections, promotions, 3D models, and storefront settings.',
            'updated_at' => now(),
        ]);

        foreach ([
            ['name' => 'merchandising.storefront.view', 'display_name' => 'View Storefront Settings'],
            ['name' => 'merchandising.tags.view', 'display_name' => 'View Collections'],
        ] as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                $permission + ['module' => 'merchandising', 'description' => $permission['display_name'], 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }
        $roleIds = DB::table('roles')->whereIn('name', ['owner', 'super_admin', 'store_manager'])->pluck('id');
        foreach (['merchandising.storefront.view', 'merchandising.tags.view'] as $permissionName) {
            $permissionId = DB::table('permissions')->where('name', $permissionName)->value('id');
            foreach ($roleIds as $roleId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        $items = [
            ['name' => 'merchandising.dashboard', 'display_name' => 'Dashboard', 'route_name' => 'merchandising.dashboard', 'route_path' => '/merchandising/dashboard', 'icon' => 'pi pi-home', 'display_order' => 1, 'permission' => 'merchandising.dashboard.view'],
            ['name' => 'merchandising.products', 'display_name' => 'Product Listings', 'route_name' => 'merchandising.products', 'route_path' => '/merchandising/products', 'icon' => 'pi pi-box', 'display_order' => 2, 'permission' => 'merchandising.products.view'],
            ['name' => 'merchandising.tags', 'display_name' => 'Collections', 'route_name' => 'merchandising.tags', 'route_path' => '/merchandising/tags', 'icon' => 'pi pi-th-large', 'display_order' => 3, 'permission' => 'merchandising.tags.view'],
            ['name' => 'merchandising.pricing', 'display_name' => 'Promotions & Discounts', 'route_name' => 'merchandising.pricing', 'route_path' => '/merchandising/pricing', 'icon' => 'pi pi-percentage', 'display_order' => 4, 'permission' => 'merchandising.pricing.view'],
            ['name' => 'merchandising.assets', 'display_name' => '3D Product Models', 'route_name' => 'merchandising.3d-gallery', 'route_path' => '/merchandising/3d-gallery', 'icon' => 'pi pi-box', 'display_order' => 5, 'permission' => 'merchandising.assets.view'],
            ['name' => 'merchandising.storefront', 'display_name' => 'Storefront Settings', 'route_name' => 'store.settings', 'route_path' => '/store/settings', 'icon' => 'pi pi-cog', 'display_order' => 6, 'permission' => 'merchandising.storefront.view'],
        ];

        $names = array_column($items, 'name');
        DB::table('navigation_items')->where('module', 'merchandising')->whereNotIn('name', $names)->update([
            'is_active' => false,
            'parent_id' => null,
            'updated_at' => now(),
        ]);

        foreach ($items as $item) {
            $permissionName = $item['permission'];
            unset($item['permission']);
            DB::table('navigation_items')->updateOrInsert(
                ['name' => $item['name']],
                $item + [
                    'module' => 'merchandising',
                    'section' => null,
                    'parent_id' => null,
                    'is_active' => true,
                    'meta' => json_encode(['subtitle' => $item['display_name']]),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                ]
            );

            $navigationId = DB::table('navigation_items')->where('name', $item['name'])->value('id');
            $linkedPermissionId = DB::table('permissions')->where('name', $permissionName)->value('id');
            if ($navigationId && $linkedPermissionId) {
                DB::table('navigation_permissions')->updateOrInsert(
                    ['navigation_item_id' => $navigationId, 'permission_id' => $linkedPermissionId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        // The navigation and module labels are data migrations; retain the new
        // permissions on rollback so access assignments are not lost.
    }
};
