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
        Schema::create('inventory_approval_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->enum('workflow_type', ['stock_transfer', 'stock_adjustment', 'purchase_order']);
            
            // JSON array of approval stages
            // [{order: 1, role: 'warehouse_manager', required: true}, ...]
            $table->json('approval_stages');
            
            // Approval thresholds
            $table->decimal('minimum_amount_trigger', 12, 2)->nullable();
            $table->decimal('auto_approve_below_amount', 12, 2)->nullable();
            
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['store_id', 'workflow_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_approval_workflows');
    }
};
