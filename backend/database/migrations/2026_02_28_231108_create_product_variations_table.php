<?php
// database/migrations/2024_01_01_000006_create_product_variations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('variation_sku');
            $table->string('variation_name');
            
            // Variation attributes
            $table->string('color')->nullable();
            $table->string('color_hex', 7)->nullable();
            $table->string('size')->nullable();
            $table->string('material')->nullable();
            
            // Pricing and stock
            $table->decimal('price_adjustment', 10, 2)->default(0);
            
            // Link to specific 3D model
            $table->foreignId('custom_3d_model_id')->nullable()->constrained('product_assets');
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['store_id', 'variation_sku']);
            $table->index(['store_id', 'product_id']);
            $table->index(['store_id', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_variations');
    }
};