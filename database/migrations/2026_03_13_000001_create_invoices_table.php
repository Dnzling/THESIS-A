<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('invoice_number', 100)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('restrict');
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('restrict');
            $table->foreignId('goods_receipt_id')->nullable()->constrained('goods_receipts')->onDelete('set null');

            $table->date('invoice_date');
            $table->date('due_date');

            $table->decimal('invoice_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->nullable()->default(0);
            $table->decimal('shipping_cost', 12, 2)->nullable()->default(0);
            $table->decimal('discount_amount', 12, 2)->nullable()->default(0);
            $table->decimal('net_amount', 12, 2)->nullable()->default(0);
            $table->string('currency', 3)->default('PHP');
            $table->string('invoice_file_path')->nullable();

            $table->enum('status', ['draft', 'approved', 'paid'])->default('draft');
            $table->enum('match_status', ['pending', 'matched', 'exception'])->default('pending');
            $table->text('match_notes')->nullable();

            $table->enum('payment_status', ['pending', 'paid'])->default('pending');
            $table->date('payment_date')->nullable();
            $table->decimal('payment_amount', 12, 2)->nullable();
            $table->string('payment_method')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'due_date'], 'idx_invoices_store_due');
            $table->index(['supplier_id', 'status'], 'idx_invoices_supplier_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
