<?php
// backend/database/migrations/2026_03_04_100024_create_stock_transfer_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained('stock_transfers')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->onDelete('restrict');
            
            $table->integer('requested_quantity');
            $table->integer('approved_quantity')->nullable();
            $table->integer('shipped_quantity')->nullable();
            $table->integer('received_quantity')->nullable();
            $table->integer('damaged_quantity')->default(0);
            
            $table->decimal('unit_value', 10, 2)->nullable(); // For value calculation
            
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('transfer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
    }
};