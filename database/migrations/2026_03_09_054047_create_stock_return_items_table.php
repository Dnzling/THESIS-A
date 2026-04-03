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
        Schema::create('stock_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_return_id')->constrained('stock_returns')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->onDelete('restrict');
            $table->foreignId('branch_inventory_id')->constrained('branch_inventory')->onDelete('restrict');
            
            $table->integer('quantity_returned');
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('total_cost', 12, 2);
            $table->decimal('unit_value', 10, 2)->nullable();
            $table->decimal('total_value', 12, 2)->nullable();
            
            $table->enum('condition', [
                'new',
                'good',
                'fair',
                'poor',
                'damaged',
                'expired'
            ])->default('good');
            
            $table->text('return_reason')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['stock_return_id', 'product_id'], 'idx_return_items_return_product');
            $table->index('branch_inventory_id', 'idx_return_items_inventory');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_return_items');
    }
};
