<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_return_pickups', function (Blueprint $table): void {
            $table->foreignId('vehicle_id')->nullable()->after('driver_user_id')->constrained('ecommerce_delivery_vehicles')->nullOnDelete();
            $table->json('assistant_user_ids')->nullable()->after('vehicle_id');
            $table->decimal('distance_km', 10, 2)->nullable()->after('assistant_user_ids');
            $table->decimal('estimated_fee', 12, 2)->nullable()->after('distance_km');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_return_pickups', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('vehicle_id');
            $table->dropColumn(['assistant_user_ids', 'distance_km', 'estimated_fee']);
        });
    }
};
