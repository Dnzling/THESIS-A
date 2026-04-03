<?php
// database/migrations/2024_01_01_000011_create_deduction_types_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deduction_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();

            $table->enum('category', [
                'government',
                'company',
                'loan',
                'benefit',
                'other'
            ])->default('company');

            $table->enum('frequency', [
                'one-time',
                'monthly',
                'bi-monthly',
                'quarterly',
                'annual'
            ])->default('monthly');

            // Remove the AFTER clauses - they're not needed and cause syntax errors
            $table->enum('calculation_type', ['fixed', 'percentage', 'formula'])
                ->default('fixed');

            // For percentage-based deductions
            $table->decimal('percentage_value', 5, 2)
                ->nullable()
                ->comment('Percentage of salary (e.g., 3.00 for 3%)');

            // For formula-based deductions (like SSS with brackets)
            $table->json('formula_data')
                ->nullable()
                ->comment('JSON data for complex calculations');

            // Minimum and maximum amounts (for percentage with cap)
            $table->decimal('min_amount', 10, 2)
                ->nullable();

            $table->decimal('max_amount', 10, 2)
                ->nullable();

            // Whether percentage is applied to basic salary only or gross
            $table->enum('percentage_basis', ['basic', 'gross', 'taxable'])
                ->default('basic')
                ->nullable();

            // Display settings
            $table->boolean('show_on_payslip')->default(true);
            $table->integer('sort_order')->default(0);

            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_active')->default(true);

            $table->decimal('default_amount', 10, 2)->nullable();
            $table->decimal('percentage_rate', 5, 2)->nullable(); // For percentage-based deductions

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'is_active']);
            
            // Add index for store_id for better performance
            $table->index('store_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deduction_types');
    }
};