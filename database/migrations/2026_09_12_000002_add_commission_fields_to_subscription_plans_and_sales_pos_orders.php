<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('subscription_plans', 'commission_rate')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->decimal('commission_rate', 5, 2)->nullable()->after('yearly_price');
            });
        }

        DB::table('subscription_plans')->where('plan_key', 'free')->update(['commission_rate' => 5]);

        if (! Schema::hasColumn('sales_pos_orders', 'commission_rate')) {
            Schema::table('sales_pos_orders', function (Blueprint $table) {
                $table->decimal('commission_rate', 5, 2)->nullable()->after('shipping_fee');
            });
        }

        if (! Schema::hasColumn('sales_pos_orders', 'commission_amount')) {
            Schema::table('sales_pos_orders', function (Blueprint $table) {
                $table->decimal('commission_amount', 12, 2)->default(0)->after('commission_rate');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales_pos_orders', 'commission_amount')) {
            Schema::table('sales_pos_orders', function (Blueprint $table) {
                $table->dropColumn('commission_amount');
            });
        }

        if (Schema::hasColumn('sales_pos_orders', 'commission_rate')) {
            Schema::table('sales_pos_orders', function (Blueprint $table) {
                $table->dropColumn('commission_rate');
            });
        }

        if (Schema::hasColumn('subscription_plans', 'commission_rate')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->dropColumn('commission_rate');
            });
        }
    }
};
