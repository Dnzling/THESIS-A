<?php

namespace Database\Seeders\Permission\Sales;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $permissions = [
            ['name' => 'sales.dashboard.view', 'display_name' => 'View Sales Dashboard', 'module' => 'sales'],
            ['name' => 'sales.analytics.view', 'display_name' => 'View Sales Analytics', 'module' => 'sales'],
            ['name' => 'sales.crm.view', 'display_name' => 'View Sales CRM', 'module' => 'sales'],
            ['name' => 'sales.crm.manage', 'display_name' => 'Manage Sales CRM', 'module' => 'sales'],
            ['name' => 'sales.chats.view', 'display_name' => 'View Sales Chats', 'module' => 'sales'],
            ['name' => 'sales.chats.manage', 'display_name' => 'Manage Sales Chats', 'module' => 'sales'],
            ['name' => 'sales.pos.view', 'display_name' => 'View Sales POS', 'module' => 'sales'],
            ['name' => 'sales.pos.manage', 'display_name' => 'Manage Sales POS', 'module' => 'sales'],
            ['name' => 'sales.deliveries.view', 'display_name' => 'View Sales Deliveries', 'module' => 'sales'],
            ['name' => 'sales.deliveries.manage', 'display_name' => 'Manage Sales Deliveries', 'module' => 'sales'],
            ['name' => 'sales.ecommerce-orders.view', 'display_name' => 'View Ecommerce Orders (Sales)', 'module' => 'sales'],
            ['name' => 'sales.ecommerce-orders.manage', 'display_name' => 'Manage Ecommerce Orders (Sales)', 'module' => 'sales'],
            ['name' => 'sales.reviews.view', 'display_name' => 'View Sales Reviews', 'module' => 'sales'],
            ['name' => 'sales.reviews.manage', 'display_name' => 'Manage Sales Reviews', 'module' => 'sales'],
            ['name' => 'sales.reports.view', 'display_name' => 'View Sales Reports', 'module' => 'sales'],
            ['name' => 'sales.refunds.view', 'display_name' => 'View Sales Refunds', 'module' => 'sales'],
            ['name' => 'sales.refunds.manage', 'display_name' => 'Manage Sales Refunds', 'module' => 'sales'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                array_merge($permission, [
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        $this->command?->info('Sales permissions seeded successfully.');
    }
}
