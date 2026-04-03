<?php
// database/migrations/2024_01_01_000007_create_pricing_history_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pricing_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->onDelete('cascade');
            $table->decimal('old_price', 10, 2);
            $table->decimal('new_price', 10, 2);
            $table->enum('price_type', ['Base', 'Sale', 'Promotional'])->default('Base');
            $table->string('reason')->nullable();
            $table->timestamp('effective_date');
            $table->foreignId('created_by')->nullable()->constrained('employees'); // From HR module
            $table->timestamps();
            
            $table->index(['store_id', 'product_id', 'effective_date']);
            $table->index(['store_id', 'effective_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('pricing_history');
    }
};