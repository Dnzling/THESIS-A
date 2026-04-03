<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('name', 120);
            $table->text('service_areas')->nullable(); // Free-form: cities/barangays/notes
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['branch_id', 'name'], 'unq_logistics_zone_branch_name');
            $table->index(['store_id', 'branch_id', 'is_active'], 'idx_logistics_zone_scope');
        });

        Schema::create('logistics_delivery_zone_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('logistics_delivery_zones')->cascadeOnDelete();
            $table->decimal('min_distance_km', 10, 2)->default(0);
            $table->decimal('max_distance_km', 10, 2)->nullable();
            $table->decimal('min_weight_kg', 10, 2)->default(0);
            $table->decimal('max_weight_kg', 10, 2)->nullable();
            $table->decimal('base_fee', 12, 2)->default(0);
            $table->decimal('per_km_fee', 12, 2)->default(0);
            $table->decimal('per_kg_fee', 12, 2)->default(0);
            $table->string('currency', 3)->default('PHP');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['zone_id', 'is_active'], 'idx_logistics_zone_rate_zone_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_delivery_zone_rates');
        Schema::dropIfExists('logistics_delivery_zones');
    }
};

