<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure permissions exist.
        $now = now();
        DB::table('permissions')->updateOrInsert(
            ['name' => 'sales.reviews.view'],
            [
                'display_name' => 'View Sales Reviews',
                'module' => 'sales',
                'description' => 'Allows viewing sales reviews',
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        DB::table('permissions')->updateOrInsert(
            ['name' => 'sales.reviews.manage'],
            [
                'display_name' => 'Manage Sales Reviews',
                'module' => 'sales',
                'description' => 'Allows replying and managing sales reviews',
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        // Ensure Sales navigation item exists with requested label.
        DB::table('navigation_items')->updateOrInsert(
            ['route_name' => 'sales.reviews'],
            [
                'name' => 'sales.reviews',
                'display_name' => 'Reviews Index',
                'module' => 'sales',
                'route_path' => '/sales/reviews',
                'icon' => 'pi pi-star',
                'section' => 'customer_feedback',
                'display_order' => 9,
                'is_active' => true,
                'meta' => json_encode(['subtitle' => 'Customer reviews and ratings']),
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $navId = DB::table('navigation_items')->where('route_name', 'sales.reviews')->value('id');
        $viewPermissionId = DB::table('permissions')->where('name', 'sales.reviews.view')->value('id');
        $managePermissionId = DB::table('permissions')->where('name', 'sales.reviews.manage')->value('id');

        if ($navId && $viewPermissionId) {
            DB::table('navigation_permissions')->updateOrInsert([
                'navigation_item_id' => $navId,
                'permission_id' => $viewPermissionId,
            ], [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if ($navId && $managePermissionId) {
            DB::table('navigation_permissions')->updateOrInsert([
                'navigation_item_id' => $navId,
                'permission_id' => $managePermissionId,
            ], [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $navId = DB::table('navigation_items')->where('route_name', 'sales.reviews')->value('id');
        if ($navId) {
            DB::table('navigation_permissions')->where('navigation_item_id', $navId)->delete();
        }

        DB::table('navigation_items')->where('route_name', 'sales.reviews')->delete();
    }
};

