<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OBSOLETE_PERMISSION = 'logistics.driver.deliveries';
    private const HISTORY_PERMISSION = 'logistics.driver.history';

    public function up(): void
    {
        $now = now();
        $obsoletePermissionId = DB::table('permissions')->where('name', self::OBSOLETE_PERMISSION)->value('id');

        $historyPermissionId = DB::table('permissions')->where('name', self::HISTORY_PERMISSION)->value('id');
        if (!$historyPermissionId) {
            $historyPermissionId = DB::table('permissions')->insertGetId([
                'name' => self::HISTORY_PERMISSION,
                'display_name' => 'Driver Delivery History View',
                'module' => 'logistics',
                'description' => 'View delivery history assigned to the signed-in driver.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('permissions')->where('id', $historyPermissionId)->update([
                'is_active' => true,
                'deleted_at' => null,
                'updated_at' => $now,
            ]);
        }

        if ($obsoletePermissionId) {
            foreach (DB::table('role_permissions')->where('permission_id', $obsoletePermissionId)->get(['role_id', 'created_at', 'updated_at']) as $grant) {
                DB::table('role_permissions')->insertOrIgnore([
                    'role_id' => $grant->role_id,
                    'permission_id' => $historyPermissionId,
                    'created_at' => $grant->created_at ?: $now,
                    'updated_at' => $now,
                ]);
            }

            foreach (DB::table('user_permissions')->where('permission_id', $obsoletePermissionId)->get(['user_id', 'type', 'created_at', 'updated_at']) as $override) {
                DB::table('user_permissions')->insertOrIgnore([
                    'user_id' => $override->user_id,
                    'permission_id' => $historyPermissionId,
                    'type' => $override->type,
                    'created_at' => $override->created_at ?: $now,
                    'updated_at' => $now,
                ]);
            }

            foreach (DB::table('plan_permissions')->where('permission_id', $obsoletePermissionId)->get(['plan_id', 'included', 'created_at', 'updated_at']) as $grant) {
                DB::table('plan_permissions')->insertOrIgnore([
                    'plan_id' => $grant->plan_id,
                    'permission_id' => $historyPermissionId,
                    'included' => $grant->included,
                    'created_at' => $grant->created_at ?: $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $historyNavigationIds = DB::table('navigation_items')
            ->where(function ($query): void {
                $query->where('name', 'Delivery History')
                    ->orWhere('route_name', 'driver.delivery-history');
            })->pluck('id');
        foreach ($historyNavigationIds as $navigationId) {
            DB::table('navigation_permissions')->insertOrIgnore([
                'navigation_item_id' => $navigationId,
                'permission_id' => $historyPermissionId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('navigation_items')
            ->where(function ($query): void {
                $query->where('name', 'My Deliveries')
                    ->orWhere('route_name', 'driver.deliveries');
            })
            ->update(['is_active' => false, 'deleted_at' => $now, 'updated_at' => $now]);

        if ($obsoletePermissionId) {
            DB::table('permissions')->where('id', $obsoletePermissionId)->update([
                'is_active' => false,
                'deleted_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('module_permissions')->where('permission_key', self::OBSOLETE_PERMISSION)->update([
                'is_active' => false,
                'updated_at' => $now,
            ]);
        }

        $this->clearPermissionCaches();
    }

    public function down(): void
    {
        $now = now();
        DB::table('permissions')->where('name', self::OBSOLETE_PERMISSION)->update([
            'is_active' => true,
            'deleted_at' => null,
            'updated_at' => $now,
        ]);
        DB::table('navigation_items')
            ->where(function ($query): void {
                $query->where('name', 'My Deliveries')
                    ->orWhere('route_name', 'driver.deliveries');
            })
            ->update(['is_active' => true, 'deleted_at' => null, 'updated_at' => $now]);
        DB::table('module_permissions')->where('permission_key', self::OBSOLETE_PERMISSION)->update([
            'is_active' => true,
            'updated_at' => $now,
        ]);

        $this->clearPermissionCaches();
    }

    private function clearPermissionCaches(): void
    {
        DB::table('users')->select(['id', 'store_id'])->orderBy('id')->chunkById(200, function ($users): void {
            foreach ($users as $user) {
                Cache::forget("permissions:user:{$user->id}:store:global");
                if ($user->store_id) {
                    Cache::forget("permissions:user:{$user->id}:store:{$user->store_id}");
                }
            }
        });
    }
};
