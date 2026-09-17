<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_return_investigation_tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('return_id')->unique()->constrained('ecommerce_order_returns')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('expected_investigation_date');
            $table->text('notes')->nullable();
            $table->enum('status', ['open', 'in_progress', 'completed', 'cancelled'])->default('open');
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index('expected_investigation_date');
        });

        Schema::create('crm_return_investigation_assignees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained('crm_return_investigation_tickets')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['ticket_id', 'employee_id'], 'crm_return_investigation_assignee_unique');
            $table->index(['user_id', 'ticket_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_return_investigation_assignees');
        Schema::dropIfExists('crm_return_investigation_tickets');
    }
};
