<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_delivery_fee_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->decimal('base_fee', 12, 2)->default(100);
            $table->decimal('per_km_fee', 12, 2)->default(10);
            $table->decimal('min_delivery_fee', 12, 2)->default(80);
            $table->decimal('free_shipping_min_order', 12, 2)->nullable();
            $table->decimal('bulky_item_surcharge', 12, 2)->default(0);
            $table->decimal('remote_area_surcharge', 12, 2)->default(0);
            $table->decimal('max_delivery_distance_km', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('store_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_delivery_fee_settings');
    }
};

