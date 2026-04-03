<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->string('location_code', 20)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->enum('type', ['rack', 'shelf', 'bin', 'floor', 'cold_storage', 'secure', 'bulk']);
            $table->enum('status', ['active', 'inactive', 'maintenance', 'full'])->default('active');
            $table->string('aisle', 10)->nullable();
            $table->string('rack', 10)->nullable();
            $table->string('shelf', 10)->nullable();
            $table->string('bin', 10)->nullable();
            $table->decimal('max_capacity_units', 10, 2)->nullable();
            $table->decimal('current_stock_units', 10, 2)->default(0);
            $table->decimal('max_weight_kg', 8, 2)->nullable();
            $table->decimal('current_weight_kg', 8, 2)->default(0);
            $table->json('dimensions')->nullable(); // width, height, depth in cm
            $table->boolean('is_temperature_controlled')->default(false);
            $table->decimal('min_temperature_c', 5, 2)->nullable();
            $table->decimal('max_temperature_c', 5, 2)->nullable();
            $table->boolean('requires_special_handling')->default(false);
            $table->text('special_handling_instructions')->nullable();
            $table->timestamp('last_inventory_check')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'type']);
            $table->index(['warehouse_id', 'status']);
            $table->index('location_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_locations');
    }
};
