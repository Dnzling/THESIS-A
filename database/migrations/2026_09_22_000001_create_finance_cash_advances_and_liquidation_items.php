<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_cash_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('advance_number', 40)->unique();
            $table->string('purpose', 255);
            $table->decimal('advance_amount', 14, 2);
            $table->decimal('liquidated_amount', 14, 2)->default(0);
            $table->decimal('cash_returned', 14, 2)->default(0);
            $table->decimal('reimbursement_amount', 14, 2)->default(0);
            $table->date('needed_date');
            $table->string('payment_method', 50)->nullable();
            $table->string('status', 40)->default('pending_approval')->index();
            $table->text('notes')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('settled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['store_id', 'branch_id']);
        });

        Schema::create('finance_liquidation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_advance_id')->constrained('finance_cash_advances')->cascadeOnDelete();
            $table->date('expense_date');
            $table->string('category', 100);
            $table->string('description', 500);
            $table->decimal('amount', 14, 2);
            $table->string('receipt_path')->nullable();
            $table->string('receipt_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_liquidation_items');
        Schema::dropIfExists('finance_cash_advances');
    }
};
