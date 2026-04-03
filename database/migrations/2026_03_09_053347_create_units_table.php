<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->string('unit_name', 100);
            $table->string('unit_code', 20)->unique();
            $table->string('unit_symbol', 10)->nullable();
            $table->text('description')->nullable();
            $table->enum('unit_type', ['weight', 'volume', 'length', 'area', 'quantity', 'time', 'other'])->default('quantity');
            $table->decimal('conversion_factor', 15, 6)->default(1); // Conversion to base unit
            $table->unsignedBigInteger('base_unit_id')->nullable(); // Reference to base unit
            $table->boolean('is_base_unit')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->foreign('base_unit_id')->references('id')->on('units');
            $table->index(['store_id', 'is_active']);
            $table->index(['store_id', 'unit_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
