<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create supplier_ratings table
        if (!Schema::hasTable('supplier_ratings')) {
            Schema::create('supplier_ratings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
                $table->unsignedBigInteger('rated_by_user_id')->nullable();
                $table->foreign('rated_by_user_id')->references('id')->on('users')->onDelete('set null');
                $table->integer('rating');
                $table->enum('category', ['delivery', 'quality', 'communication', 'price']);
                $table->text('comment')->nullable();
                $table->timestamps();
                
                $table->index('supplier_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_ratings');
    }
};
