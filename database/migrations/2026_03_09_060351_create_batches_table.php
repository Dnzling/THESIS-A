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
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number')->unique();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->onDelete('set null');
            $table->integer('quantity_produced');
            $table->integer('quantity_available')->default(0);
            $table->integer('quantity_sold')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->integer('quantity_damaged')->default(0);
            $table->integer('quantity_returned')->default(0);
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->date('production_date');
            $table->date('expiry_date')->nullable();
            $table->date('best_before_date')->nullable();
            $table->enum('status', ['active', 'expired', 'depleted', 'discontinued'])->default('active');
            $table->enum('quality_status', ['pending', 'approved', 'rejected', 'quarantined'])->default('pending');
            $table->string('supplier_name')->nullable();
            $table->string('supplier_batch_number')->nullable();
            $table->text('notes')->nullable();
            $table->json('specifications')->nullable();
            $table->json('quality_test_results')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['product_id', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index(['warehouse_location_id']);
            $table->index(['expiry_date']);
            $table->index(['best_before_date']);
            $table->index(['production_date']);
            $table->index(['status', 'quality_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
