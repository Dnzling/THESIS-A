<?php
// database/migrations/2024_01_01_000008_create_tags_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('tag_name');
            $table->enum('tag_type', ['Style', 'Room', 'Promotion', 'Feature'])->default('Style');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['store_id', 'tag_name']);
        });

        Schema::create('product_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['store_id', 'product_id', 'tag_id'], 'unique_product_tag');
            $table->index(['store_id', 'product_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_tags');
        Schema::dropIfExists('tags');
    }
};