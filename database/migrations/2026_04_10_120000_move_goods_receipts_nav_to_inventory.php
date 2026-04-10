<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('navigation_items')
            ->where('route_name', 'procurement.goods-receipts')
            ->orWhere('route_path', '/procurement/goods-receipts')
            ->update([
                'module' => 'inventory',
                'route_name' => 'inventory.goods-receipts',
                'route_path' => '/inventory/goods-receipts',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('navigation_items')
            ->where('route_name', 'inventory.goods-receipts')
            ->orWhere('route_path', '/inventory/goods-receipts')
            ->update([
                'module' => 'procurement',
                'route_name' => 'procurement.goods-receipts',
                'route_path' => '/procurement/goods-receipts',
                'updated_at' => now(),
            ]);
    }
};

