<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ecommerce_order_deliveries')) {
            return;
        }

        Schema::table('ecommerce_order_deliveries', function (Blueprint $table): void {
            if (! Schema::hasColumn('ecommerce_order_deliveries', 'current_latitude')) {
                $table->decimal('current_latitude', 10, 7)->nullable();
            }
            if (! Schema::hasColumn('ecommerce_order_deliveries', 'current_longitude')) {
                $table->decimal('current_longitude', 10, 7)->nullable();
            }
            if (! Schema::hasColumn('ecommerce_order_deliveries', 'current_address')) {
                $table->text('current_address')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ecommerce_order_deliveries')) {
            return;
        }

        $columns = array_values(array_filter([
            Schema::hasColumn('ecommerce_order_deliveries', 'current_latitude') ? 'current_latitude' : null,
            Schema::hasColumn('ecommerce_order_deliveries', 'current_longitude') ? 'current_longitude' : null,
            Schema::hasColumn('ecommerce_order_deliveries', 'current_address') ? 'current_address' : null,
        ]));

        if ($columns) {
            Schema::table('ecommerce_order_deliveries', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
