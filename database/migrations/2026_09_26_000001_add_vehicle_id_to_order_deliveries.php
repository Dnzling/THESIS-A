<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('order_deliveries', 'vehicle_id')) {
            Schema::table('order_deliveries', function (Blueprint $table): void {
                $table->foreignId('vehicle_id')->nullable()->after('driver_user_id')
                    ->constrained('ecommerce_delivery_vehicles')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('order_deliveries', 'vehicle_id')) {
            Schema::table('order_deliveries', fn (Blueprint $table) => $table->dropConstrainedForeignId('vehicle_id'));
        }
    }
};
