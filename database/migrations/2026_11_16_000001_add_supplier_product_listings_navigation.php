<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const NAVIGATION_NAME = 'supplier-portal.products';
    private const ROUTE_PATH = '/supplier-portal/products';

    public function up(): void
    {
        $now = now();

        DB::table('navigation_items')->updateOrInsert(
            ['name' => self::NAVIGATION_NAME],
            [
                'display_name' => 'Product Listings',
                'module' => 'supplier',
                'section' => 'portal',
                'route_name' => 'supplier.products',
                'route_path' => self::ROUTE_PATH,
                'icon' => 'pi pi-box',
                'parent_id' => null,
                'display_order' => 3,
                'is_active' => true,
                'meta' => null,
                'deleted_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        $navigationId = DB::table('navigation_items')->where('name', self::NAVIGATION_NAME)->value('id');
        $permissionId = DB::table('permissions')->where('name', 'supplier.dashboard.view')->value('id');

        if ($navigationId && $permissionId) {
            DB::table('navigation_permissions')->updateOrInsert(
                [
                    'navigation_item_id' => $navigationId,
                    'permission_id' => $permissionId,
                ],
                ['created_at' => $now, 'updated_at' => $now],
            );
        }

        DB::table('navigation_items')
            ->where('route_name', 'supplier-portal.rfqs')
            ->update(['display_order' => 4, 'updated_at' => $now]);

        foreach (DB::table('users')->select('id', 'store_id')->get() as $user) {
            Cache::forget("permissions:user:{$user->id}:store:global");
            if ($user->store_id) {
                Cache::forget("permissions:user:{$user->id}:store:{$user->store_id}");
            }
        }
    }

    public function down(): void
    {
        $navigationId = DB::table('navigation_items')->where('name', self::NAVIGATION_NAME)->value('id');

        if ($navigationId) {
            DB::table('navigation_permissions')->where('navigation_item_id', $navigationId)->delete();
            DB::table('navigation_items')->where('id', $navigationId)->delete();
        }

        foreach (DB::table('users')->select('id', 'store_id')->get() as $user) {
            Cache::forget("permissions:user:{$user->id}:store:global");
            if ($user->store_id) {
                Cache::forget("permissions:user:{$user->id}:store:{$user->store_id}");
            }
        }
    }
};
