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
        Schema::create('reorder_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->enum('rule_type', ['manual', 'automatic', 'demand_based'])->default('manual');
            $table->enum('trigger_type', ['reorder_point', 'safety_stock', 'forecast', 'seasonal'])->default('reorder_point');
            $table->decimal('reorder_point', 10, 2)->nullable(); // Minimum stock level to trigger reorder
            $table->decimal('reorder_quantity', 10, 2)->nullable(); // Quantity to reorder
            $table->integer('lead_time_days')->nullable(); // Supplier lead time in days
            $table->decimal('safety_stock', 10, 2)->nullable(); // Safety stock level
            $table->decimal('maximum_stock', 10, 2)->nullable(); // Maximum stock level
            $table->decimal('economic_order_quantity', 10, 2)->nullable(); // EOQ calculation
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->boolean('auto_generate_po')->default(false); // Auto-generate purchase order
            $table->json('supplier_preferences')->nullable(); // Preferred suppliers
            $table->json('seasonal_adjustments')->nullable(); // Seasonal demand adjustments
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamp('next_review_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'branch_id']);
            $table->index(['branch_id', 'is_active']);
            $table->index(['rule_type', 'trigger_type']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reorder_rules');
    }
};
