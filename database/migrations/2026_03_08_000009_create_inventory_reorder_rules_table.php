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
        Schema::create('inventory_reorder_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            
            // Reorder thresholds
            $table->integer('reorder_point')->comment('Quantity level that triggers reorder');
            $table->integer('reorder_quantity')->comment('Quantity to reorder when triggered');
            $table->integer('maximum_stock')->nullable();
            $table->integer('safety_stock')->default(5);
            
            // Auto-reorder configuration
            $table->boolean('auto_reorder_enabled')->default(false);
            $table->integer('auto_reorder_days')->nullable()->comment('Reorder every N days');
            
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['store_id', 'product_id', 'branch_id']);
            $table->index(['store_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_reorder_rules');
    }
};
