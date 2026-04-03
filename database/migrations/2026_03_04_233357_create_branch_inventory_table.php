<?php
// backend/database/migrations/2026_03_04_100005_create_branch_inventory_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->onDelete('restrict');
            
            // ✅ Stock Quantity Tracking
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->integer('quantity_available')->default(0);
            $table->integer('quantity_damaged')->default(0);
            $table->integer('quantity_incoming')->default(0);
            
            // ✅ Warehouse Location Mapping
            $table->string('warehouse_section', 50)->nullable();
            $table->string('aisle', 50)->nullable();
            $table->string('rack', 50)->nullable();
            $table->string('shelf', 50)->nullable();
            $table->string('bin_code', 100)->nullable();
            
            // ✅ Low-stock & Reorder Alerts
            $table->integer('reorder_point')->default(10);
            $table->integer('reorder_quantity')->default(50);
            $table->integer('maximum_stock')->nullable();
            $table->integer('safety_stock')->default(5);
            
            $table->enum('stock_status', [
                'in_stock',
                'low_stock',
                'out_of_stock',
                'discontinued',
                'on_order'
            ])->default('in_stock');
            
            // Costing & Valuation
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('average_cost', 10, 2)->nullable();
            $table->decimal('total_value', 12, 2)->nullable();
            
            // Last Stock Count
            $table->date('last_stock_count_date')->nullable();
            $table->integer('last_counted_quantity')->nullable();
            $table->foreignId('last_counted_by')->nullable()->constrained('employees');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Unique constraint
            $table->unique(['branch_id', 'product_id', 'variation_id'], 'unq_branch_product_stock');
            
            // ✅ FIXED: Custom index names (under 64 chars)
            $table->index(['branch_id', 'stock_status'], 'idx_binv_branch_status');
            $table->index(['product_id', 'quantity_available'], 'idx_binv_product_qty');
            $table->index(['branch_id', 'warehouse_section', 'aisle'], 'idx_binv_location');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_inventory');
    }
};