<?php
// backend/database/migrations/2026_03_04_100015_create_supplier_quotation_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('supplier_quotations')->onDelete('cascade');
            $table->foreignId('rfq_item_id')->constrained('rfq_items')->onDelete('restrict');
            
            $table->decimal('unit_price', 10, 2);
            $table->integer('quantity');
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('line_total', 12, 2);
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index('quotation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_quotation_items');
    }
};