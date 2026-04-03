<?php
// database/migrations/2024_01_01_000004_create_product_attributes_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('attribute_name');
            $table->enum('attribute_type', ['Text', 'Number', 'Select', 'Color', 'Multi-select'])->default('Text');
            $table->boolean('is_filterable')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['store_id', 'attribute_name']);
        });

        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('attribute_id')->constrained('product_attributes')->onDelete('cascade');
            $table->string('attribute_value');
            $table->string('color_hex_code', 7)->nullable();
            $table->string('texture_map_url')->nullable();
            $table->timestamps();
            
            $table->index(['store_id', 'product_id']);
            $table->index(['store_id', 'attribute_id']);
            $table->unique(['store_id', 'product_id', 'attribute_id'], 'unique_product_attribute');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_attributes');
    }
};