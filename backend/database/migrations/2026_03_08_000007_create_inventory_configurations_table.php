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
        Schema::create('inventory_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            
            // Business Model Type
            $table->enum('model_type', ['single_store', 'centralized', 'distributed', 'multi_store'])->default('centralized');
            
            // Feature Flags
            $table->boolean('enable_transfer_approvals')->default(true);
            $table->boolean('enable_finance_approval')->default(true);
            $table->boolean('enable_auto_alerts')->default(true);
            $table->boolean('enable_cost_tracking')->default(true);
            $table->boolean('enable_physical_counts')->default(true);
            
            // Warehouse Configuration
            $table->foreignId('main_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->json('warehouse_branch_ids')->nullable();
            
            // Default Thresholds
            $table->integer('default_reorder_point')->default(10);
            $table->integer('default_reorder_quantity')->default(50);
            $table->integer('default_safety_stock')->default(5);
            $table->integer('default_maximum_stock')->nullable();
            
            // Transfer Settings
            $table->decimal('require_finance_approval_above', 12, 2)->default(50000);
            $table->boolean('allow_auto_transfer')->default(false);
            $table->integer('auto_transfer_threshold')->nullable();
            
            // Cost Allocation
            $table->enum('transfer_cost_model', ['fixed', 'distance_based', 'weighted', 'none'])->default('distance_based');
            $table->decimal('fixed_transfer_cost', 10, 2)->nullable();
            $table->decimal('cost_per_km', 8, 2)->default(10.00);
            
            // Reporting
            $table->enum('reporting_frequency', ['daily', 'weekly', 'monthly'])->default('weekly');
            $table->boolean('include_sub_branches')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('store_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_configurations');
    }
};
