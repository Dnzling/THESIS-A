<?php
// backend/database/migrations/2026_03_04_100019_create_goods_receipt_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_order_item_id')->constrained()->onDelete('restrict');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->onDelete('restrict');
            
            $table->integer('quantity_expected');
            $table->integer('quantity_received');
            $table->integer('quantity_damaged')->default(0);
            
            $table->enum('condition', [
                'good',
                'damaged',
                'defective'
            ])->default('good');
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index('goods_receipt_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
    }
};