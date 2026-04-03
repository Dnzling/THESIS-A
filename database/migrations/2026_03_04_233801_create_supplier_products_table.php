<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            
            $table->string('supplier_sku', 100)->nullable(); // Supplier's product code
            $table->decimal('supplier_price', 10, 2); // Cost from this supplier
            $table->integer('minimum_order_quantity')->default(1);
            $table->integer('lead_time_days')->default(7); // Delivery time
            $table->boolean('is_preferred_supplier')->default(false);
            
            $table->timestamps();
            
            $table->unique(['supplier_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_products');
    }
};