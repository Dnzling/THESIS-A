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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            
            $table->string('warehouse_code', 20)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            
            $table->enum('type', [
                'main',
                'satellite',
                'cold_storage',
                'hazardous',
                'quarantine'
            ])->default('main');
            
            $table->enum('status', [
                'active',
                'inactive',
                'maintenance',
                'closed'
            ])->default('active');
            
            // Location details
            $table->string('address_line_1', 255);
            $table->string('address_line_2', 255)->nullable();
            $table->string('city', 100);
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->default('Philippines');
            
            // Contact information
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('manager_name', 100)->nullable();
            $table->string('manager_phone', 20)->nullable();
            
            // Capacity and dimensions
            $table->decimal('total_area_sqm', 10, 2)->nullable();
            $table->decimal('usable_area_sqm', 10, 2)->nullable();
            $table->integer('total_racks')->default(0);
            $table->integer('total_shelves')->default(0);
            $table->integer('max_capacity_units')->nullable();
            
            // Operating hours
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            $table->json('operating_days')->nullable(); // Array of days (0-6, 0=Sunday)
            
            // Security and access
            $table->boolean('requires_access_card')->default(false);
            $table->boolean('has_security_system')->default(false);
            $table->boolean('has_fire_system')->default(false);
            $table->text('access_instructions')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['store_id', 'status'], 'idx_warehouses_store_status');
            $table->index(['branch_id', 'type'], 'idx_warehouses_branch_type');
            $table->index('warehouse_code', 'idx_warehouses_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
