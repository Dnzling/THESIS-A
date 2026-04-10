<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('permissions') || !DB::getSchemaBuilder()->hasTable('navigation_items')) {
            return;
        }

        $permissionIds = [];
        foreach ([
            'sales.vouchers.view' => 'View Sales Vouchers',
            'sales.vouchers.manage' => 'Manage Sales Vouchers',
        ] as $name => $displayName) {
            $id = DB::table('permissions')->where('name', $name)->value('id');
            if (!$id) {
                $id = DB::table('permissions')->insertGetId([
                    'name' => $name,
                    'display_name' => $displayName,
                    'module' => 'sales',
                    'description' => $displayName,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $permissionIds[$name] = (int) $id;
        }

        $navId = DB::table('navigation_items')->where('route_name', 'sales.vouchers')->value('id');
        if (!$navId) {
            $navId = DB::table('navigation_items')->insertGetId([
                'name' => 'sales.vouchers',
                'display_name' => 'Vouchers',
                'module' => 'sales',
                'route_name' => 'sales.vouchers',
                'route_path' => '/sales/vouchers',
                'icon' => 'pi pi-ticket',
                'parent_id' => null,
                'display_order' => 195,
                'is_active' => true,
                'meta' => json_encode(['section' => 'Sales'], JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (DB::getSchemaBuilder()->hasTable('navigation_permissions')) {
            foreach ($permissionIds as $permissionId) {
                $exists = DB::table('navigation_permissions')
                    ->where('navigation_item_id', $navId)
                    ->where('permission_id', $permissionId)
                    ->exists();
                if (!$exists) {
                    DB::table('navigation_permissions')->insert([
                        'navigation_item_id' => $navId,
                        'permission_id' => $permissionId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        if (DB::getSchemaBuilder()->hasTable('role_permissions')) {
            $sourcePermIds = DB::table('permissions')
                ->whereIn('name', ['sales.pos.view', 'sales.pos.manage'])
                ->pluck('id')
                ->all();

            if (!empty($sourcePermIds)) {
                $roleIds = DB::table('role_permissions')
                    ->whereIn('permission_id', $sourcePermIds)
                    ->pluck('role_id')
                    ->unique()
                    ->values()
                    ->all();

                foreach ($roleIds as $roleId) {
                    foreach ($permissionIds as $permissionId) {
                        $exists = DB::table('role_permissions')
                            ->where('role_id', $roleId)
                            ->where('permission_id', $permissionId)
                            ->exists();
                        if (!$exists) {
                            DB::table('role_permissions')->insert([
                                'role_id' => $roleId,
                                'permission_id' => $permissionId,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
        }
    }

    public function down(): void
    {
        $permIds = DB::table('permissions')
            ->whereIn('name', ['sales.vouchers.view', 'sales.vouchers.manage'])
            ->pluck('id')
            ->all();

        $navId = DB::table('navigation_items')->where('route_name', 'sales.vouchers')->value('id');

        if ($navId && DB::getSchemaBuilder()->hasTable('navigation_permissions')) {
            DB::table('navigation_permissions')->where('navigation_item_id', $navId)->delete();
        }

        if (!empty($permIds) && DB::getSchemaBuilder()->hasTable('role_permissions')) {
            DB::table('role_permissions')->whereIn('permission_id', $permIds)->delete();
        }

        if (!empty($permIds)) {
            DB::table('permissions')->whereIn('id', $permIds)->delete();
        }

        if ($navId) {
            DB::table('navigation_items')->where('id', $navId)->delete();
        }
    }
};

