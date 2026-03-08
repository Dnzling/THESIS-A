<?php
// database/migrations/2024_01_01_000006_create_payrolls_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pay_period_id')->constrained()->restrictOnDelete();
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('overtime_amount', 12, 2)->default(0);
            $table->decimal('deductions_total', 12, 2)->default(0);
            $table->decimal('bonuses_total', 12, 2)->default(0);
            $table->decimal('allowances_total', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2)->default(0);
            $table->integer('late_minutes')->default(0);        // Total minutes late
            $table->decimal('late_deduction', 12, 2)->default(0); // Amount deducted for lateness
            $table->integer('late_occurrences')->default(0);    // Number of late instances
            $table->enum('status', ['draft', 'calculated', 'processing', 'approved', 'paid', 'cancelled'])->default('draft');
            $table->date('payment_date')->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'pay_period_id']);
            $table->index(['pay_period_id', 'status']);
            $table->index(['employee_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
