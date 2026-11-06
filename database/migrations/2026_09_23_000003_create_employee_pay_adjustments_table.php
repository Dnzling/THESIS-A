<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_pay_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('effective_date');
            $table->enum('type', ['allowance', 'incentive']);
            $table->string('name', 100);
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['draft', 'approved'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'effective_date', 'type', 'name'], 'employee_pay_adjustments_unique');
            $table->index(['store_id', 'effective_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_pay_adjustments');
    }
};
