<?php
// backend/database/migrations/2026_03_04_100014_create_supplier_quotations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number', 50)->unique();
            $table->foreignId('rfq_id')->constrained('request_for_quotations')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            
            $table->date('quotation_date');
            $table->date('valid_until');
            
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            
            $table->enum('payment_terms', [
                'cash_on_delivery',
                'net_7',
                'net_15',
                'net_30',
                'net_60'
            ])->default('net_30');
            
            $table->integer('delivery_days'); // Lead time
            
            $table->text('notes')->nullable();
            $table->string('attachment_path')->nullable(); // PDF quote
            
            $table->enum('status', [
                'submitted',
                'under_review',
                'accepted',
                'rejected'
            ])->default('submitted');
            
            $table->decimal('evaluation_score', 5, 2)->nullable()->comment('0-100 score');
            $table->text('evaluation_notes')->nullable();
            
            $table->timestamps();
            
            $table->index(['rfq_id', 'supplier_id']);
            $table->index('quotation_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_quotations');
    }
};