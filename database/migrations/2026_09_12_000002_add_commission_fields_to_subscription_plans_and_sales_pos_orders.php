<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->nullable()->after('yearly_price');
        });

        DB::table('subscription_plans')->where('plan_key', 'free')->update(['commission_rate' => 5]);

        Schema::table('sales_pos_orders', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->nullable()->after('shipping_fee');
            $table->decimal('commission_amount', 12, 2)->default(0)->after('commission_rate');
        });
    }

    public function down(): void
    {
        Schema::table('sales_pos_orders', function (Blueprint $table) {
            $table->dropColumn(['commission_rate', 'commission_amount']);
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('commission_rate');
        });
    }
};
