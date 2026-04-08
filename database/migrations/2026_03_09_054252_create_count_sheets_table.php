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
        Schema::create('count_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_count_id')->constrained('stock_counts')->onDelete('cascade');
            $table->foreignId('branch_inventory_id')->constrained('branch_inventory')->onDelete('restrict');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->onDelete('restrict');
            
            // Expected quantities
            $table->integer('system_quantity')->default(0); // Quantity in system
            $table->decimal('system_unit_cost', 10, 2)->default(0);
            $table->decimal('system_total_value', 12, 2)->default(0);
            
            // Counted quantities
            $table->integer('counted_quantity')->nullable();
            $table->decimal('counted_unit_cost', 10, 2)->nullable();
            $table->decimal('counted_total_value', 12, 2)->nullable();
            
            // Discrepancy
            $table->integer('discrepancy')->nullable(); // counted - system
            $table->decimal('discrepancy_value', 12, 2)->nullable();
            
            // Location details
            $table->string('warehouse_section')->nullable();
            $table->string('aisle')->nullable();
            $table->string('rack')->nullable();
            $table->string('shelf')->nullable();
            $table->string('bin_code')->nullable();
            
            // Count details
            $table->dateTime('counted_at')->nullable();
            $table->foreignId('counted_by')->nullable()->constrained('employees');
            $table->enum('count_status', [
                'pending',
                'counted',
                'verified',
                'discrepancy_found'
            ])->default('pending');
            
            $table->text('notes')->nullable();
            $table->text('discrepancy_reason')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['stock_count_id', 'count_status'], 'idx_sheets_count_status');
            $table->index(['branch_inventory_id', 'stock_count_id'], 'idx_sheets_inventory_count');
            $table->index('counted_by', 'idx_sheets_counted_by');
            
            // Unique constraint to prevent duplicate entries
            $table->unique(['stock_count_id', 'branch_inventory_id'], 'unique_count_sheet');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('count_sheets');
    }
};
