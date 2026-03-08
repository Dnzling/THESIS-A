<?php
// database/migrations/2024_01_01_000002_create_categories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('category_code');
            $table->string('category_name');
            $table->text('description')->nullable();
            $table->foreignId('parent_category_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->integer('level')->default(1);
            $table->string('icon_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['store_id', 'category_code']);
            $table->index(['store_id', 'is_active']);
            $table->index(['store_id', 'parent_category_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('categories');
    }
};