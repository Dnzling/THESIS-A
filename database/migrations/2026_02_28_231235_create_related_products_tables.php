<?php
// database/migrations/2024_01_01_000009_create_related_products_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('related_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('related_product_id')->constrained('products')->onDelete('cascade');
            $table->enum('relation_type', ['Upsell', 'Cross-sell', 'Accessory', 'Similar', 'Frequently Bought Together'])->default('Similar');
            $table->decimal('strength_score', 3, 2)->default(1.00);
            $table->timestamps();
            
            $table->unique(['store_id', 'product_id', 'related_product_id', 'relation_type'], 'unique_relation');
            $table->index(['store_id', 'product_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('related_products');
    }
};