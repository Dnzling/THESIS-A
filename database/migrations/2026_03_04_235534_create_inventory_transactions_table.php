<?php
// backend/database/migrations/2026_03_04_100020_create_inventory_transactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 50)->unique();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->onDelete('restrict');
            
            // Transaction Type
            $table->enum('transaction_type', [
                'purchase',
                'sale',
                'return_to_supplier',
                'customer_return',
                'transfer_out',
                'transfer_in',
                'adjustment',
                'damage',
                'expired',
                'lost',
                'assembly',
                'sample',
                'writeoff'
            ]);
            
            // Quantities
            $table->integer('quantity_before')->default(0);
            $table->integer('quantity_change');
            $table->integer('quantity_after')->default(0);
            
            // Reference Documents
            $table->foreignId('related_branch_id')->nullable()->constrained('branches');
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            
            // Details
            $table->text('notes')->nullable();
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('total_value', 12, 2)->nullable();
            
            // Audit
            $table->foreignId('created_by')->constrained('employees');
            $table->timestamp('transaction_date');
            $table->timestamps();
            
            // ✅ FIXED: Custom index names (shortened)
            $table->index(['branch_id', 'transaction_type', 'transaction_date'], 'idx_invtx_branch_type_date');
            $table->index(['product_id', 'transaction_date'], 'idx_invtx_product_date');
            $table->index('transaction_number', 'idx_invtx_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};