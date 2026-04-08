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
        Schema::create('stock_counts', function (Blueprint $table) {
            $table->id();
            $table->string('count_number', 50)->unique();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            
            $table->enum('status', [
                'scheduled',
                'in_progress', 
                'completed',
                'cancelled',
                'approved'
            ])->default('scheduled');
            
            $table->enum('count_type', [
                'full_inventory',
                'partial_count',
                'cycle_count',
                'spot_check'
            ])->default('full_inventory');
            
            $table->date('scheduled_date');
            $table->date('started_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->date('approved_date')->nullable();
            
            // Assignment
            $table->foreignId('assigned_by')->constrained('employees');
            $table->foreignId('assigned_to')->constrained('employees');
            $table->foreignId('supervised_by')->nullable()->constrained('employees');
            $table->foreignId('approved_by')->nullable()->constrained('employees');
            
            // Count details
            $table->integer('total_items_expected')->default(0);
            $table->integer('total_items_counted')->default(0);
            $table->integer('items_with_discrepancy')->default(0);
            $table->decimal('total_value_counted', 12, 2)->default(0);
            $table->decimal('total_discrepancy_value', 12, 2)->default(0);
            
            // Location/Section filtering
            $table->string('warehouse_section')->nullable();
            $table->string('aisle')->nullable();
            $table->string('rack')->nullable();
            $table->string('shelf')->nullable();
            
            // Product filtering
            $table->json('category_ids')->nullable(); // Array of category IDs to count
            $table->json('product_ids')->nullable(); // Array of specific product IDs
            
            $table->text('instructions')->nullable();
            $table->text('notes')->nullable();
            $table->text('approval_notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['branch_id', 'status'], 'idx_counts_branch_status');
            $table->index(['scheduled_date', 'status'], 'idx_counts_date_status');
            $table->index('count_type', 'idx_counts_type');
            $table->index('count_number', 'idx_counts_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_counts');
    }
};
