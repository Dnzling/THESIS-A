<?php
// database/migrations/2024_01_01_000007_create_payroll_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['earning', 'deduction', 'tax', 'allowance', 'bonus']);
            $table->string('name', 100);
            $table->decimal('amount', 12, 2);
            $table->string('calculation_type', 50)->nullable(); // fixed, percentage, hourly
            $table->decimal('rate', 10, 2)->nullable();
            $table->decimal('quantity', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['payroll_id', 'type']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};