<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            DB::table('products')
                ->where('price_approval_status', 'pending')
                ->update([
                    'base_price' => DB::raw('COALESCE(pending_base_price, base_price)'),
                    'discounted_price' => DB::raw('pending_discounted_price'),
                    'price_approval_status' => 'approved',
                    'pending_base_price' => null,
                    'pending_discounted_price' => null,
                    'price_approved_by' => null,
                    'price_approved_at' => null,
                    'updated_at' => now(),
                ]);
        }

        if (Schema::hasTable('navigation_items')) {
            DB::table('navigation_items')->where('name', 'finance.price-approvals')->update(['is_active' => 0]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('navigation_items')) {
            DB::table('navigation_items')->where('name', 'finance.price-approvals')->update(['is_active' => 1]);
        }
    }
};
