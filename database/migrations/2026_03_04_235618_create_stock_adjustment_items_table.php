<?php
// backend/database/migrations/2026_03_04_100022_create_stock_adjustment_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adjustment_id')->constrained('stock_adjustments')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->onDelete('restrict');
            
            $table->integer('system_quantity'); // What system says
            $table->integer('actual_quantity');  // What was counted/found
            $table->integer('difference'); // actual - system (can be negative)
            
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('value_difference', 12, 2)->nullable(); // Financial impact
            
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('adjustment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
    }
};