<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('ecommerce_delivery_vehicles', 'cost_per_km')) {
            Schema::table('ecommerce_delivery_vehicles', function (Blueprint $table) {
                $table->decimal('cost_per_km', 10, 2)->default(0)->after('capacity_kg');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ecommerce_delivery_vehicles', 'cost_per_km')) {
            Schema::table('ecommerce_delivery_vehicles', function (Blueprint $table) {
                $table->dropColumn('cost_per_km');
            });
        }
    }
};
