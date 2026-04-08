<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->string('description')->nullable();
            $table->integer('quantity_invoiced')->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('line_amount', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->nullable()->default(0);
            $table->decimal('tax_amount', 12, 2)->nullable()->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'product_id'], 'idx_invoice_items_invoice_product');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
