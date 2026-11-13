<?php

namespace Database\Seeders\Permission\Merchandise;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NavigationItemsSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'merchandising.dashboard', 'display_name' => 'Dashboard', 'route_name' => 'merchandising.dashboard', 'route_path' => '/merchandising/dashboard', 'icon' => 'pi pi-home', 'display_order' => 1, 'permission' => 'merchandising.dashboard.view'],
            ['name' => 'merchandising.products', 'display_name' => 'Product Listings', 'route_name' => 'merchandising.products', 'route_path' => '/merchandising/products', 'icon' => 'pi pi-box', 'display_order' => 2, 'permission' => 'merchandising.products.view'],
            ['name' => 'merchandising.tags', 'display_name' => 'Collections', 'route_name' => 'merchandising.tags', 'route_path' => '/merchandising/tags', 'icon' => 'pi pi-th-large', 'display_order' => 3, 'permission' => 'merchandising.tags.view'],
            ['name' => 'merchandising.pricing', 'display_name' => 'Promotions & Discounts', 'route_name' => 'merchandising.pricing', 'route_path' => '/merchandising/pricing', 'icon' => 'pi pi-percentage', 'display_order' => 4, 'permission' => 'merchandising.pricing.view'],
            ['name' => 'merchandising.discount-requests', 'display_name' => 'Discount Requests', 'route_name' => 'merchandising.discount-requests', 'route_path' => '/merchandising/discount-requests', 'icon' => 'pi pi-inbox', 'display_order' => 5, 'permission' => 'merchandising.pricing.view'],
            ['name' => 'merchandising.vouchers', 'display_name' => 'Vouchers', 'route_name' => 'merchandising.vouchers', 'route_path' => '/merchandising/vouchers', 'icon' => 'pi pi-ticket', 'display_order' => 6, 'permission' => 'merchandising.vouchers.view'],
            ['name' => 'merchandising.assets', 'display_name' => '3D Product Models', 'route_name' => 'merchandising.3d-gallery', 'route_path' => '/merchandising/3d-gallery', 'icon' => 'pi pi-box', 'display_order' => 7, 'permission' => 'merchandising.assets.view'],
            ['name' => 'merchandising.reports', 'display_name' => 'Reports', 'route_name' => 'merchandising.reports', 'route_path' => '/merchandising/reports', 'icon' => 'pi pi-chart-bar', 'display_order' => 8, 'permission' => 'merchandising.reports.view'],
        ];

        $activeNames = array_column($items, 'name');
        DB::table('navigation_items')->where('module', 'merchandising')->whereNotIn('name', $activeNames)->update(['is_active' => false, 'parent_id' => null, 'updated_at' => now()]);

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
                    'updated_at' => now(),
                    'created_at' => now(),
                    'deleted_at' => null,
                ]
            );

            $navigationId = DB::table('navigation_items')->where('name', $item['name'])->value('id');
            $permissionId = DB::table('permissions')->where('name', $permissionName)->value('id');
            if ($navigationId && $permissionId) {
                DB::table('navigation_permissions')->updateOrInsert(
                    ['navigation_item_id' => $navigationId, 'permission_id' => $permissionId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        $modulePermissionIds = DB::table('permissions')
            ->whereIn('name', [...array_column($items, 'permission'), 'merchandising.pricing.edit', 'merchandising.vouchers.manage', 'merchandising.reports.manage'])
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->pluck('id');
        $moduleId = DB::table('modules')->where('key', 'merchandising')->value('id');

        foreach (DB::table('subscription_plans')->pluck('id') as $planId) {
            if ($moduleId) {
                DB::table('plan_modules')->updateOrInsert(
                    ['plan_id' => $planId, 'module_id' => $moduleId],
                    ['included' => true, 'updated_at' => now()]
                );
            }

            foreach ($modulePermissionIds as $permissionId) {
                DB::table('plan_permissions')->updateOrInsert(
                    ['plan_id' => $planId, 'permission_id' => $permissionId],
                    ['included' => true, 'updated_at' => now()]
                );
            }
        }

        $ownerRoleIds = DB::table('roles')->where('name', 'owner')->whereNull('store_id')->pluck('id');
        foreach ($ownerRoleIds as $roleId) {
            foreach ($modulePermissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['updated_at' => now()]
                );
            }
        }
    }
}
