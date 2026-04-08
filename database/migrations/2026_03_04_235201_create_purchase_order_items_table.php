<?php
// backend/database/migrations/2026_03_04_100017_create_purchase_order_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->onDelete('restrict');
            
            $table->integer('quantity_ordered');
            $table->integer('quantity_received')->default(0);
            $table->integer('quantity_rejected')->default(0);
            
            $table->decimal('unit_cost', 10, 2); // Purchase price
            $table->decimal('tax_rate', 5, 2)->default(12.00);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('line_total', 12, 2);
            
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('purchase_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};