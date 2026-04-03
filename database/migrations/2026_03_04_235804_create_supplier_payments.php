<?php
// backend/database/migrations/2026_03_04_100027_create_supplier_payments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 50)->unique();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_order_id')->constrained()->onDelete('restrict');
            $table->foreignId('supplier_id')->constrained()->onDelete('restrict');
            
            $table->decimal('payment_amount', 12, 2);
            $table->enum('payment_method', [
                'cash',
                'check',
                'bank_transfer',
                'credit_card',
                'debit_card',
                'online_payment'
            ]);
            
            $table->enum('status', [
                'pending_approval',
                'approved',
                'processing',
                'completed',
                'failed',
                'cancelled'
            ])->default('pending_approval');
            
            $table->date('payment_date');
            $table->string('reference_number', 100)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('account_number', 100)->nullable();
            
            // Finance Approval
            $table->foreignId('approved_by')->nullable()->constrained('employees');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('employees');
            $table->timestamp('processed_at')->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // ✅ FIXED: Custom index names
            $table->index(['purchase_order_id', 'status'], 'idx_spay_po_status');
            $table->index(['supplier_id', 'payment_date'], 'idx_spay_supplier_date');
            $table->index('payment_number', 'idx_spay_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};