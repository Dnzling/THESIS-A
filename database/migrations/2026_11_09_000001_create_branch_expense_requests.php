<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_expense_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 40)->unique();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('category', 60);
            $table->string('title', 180);
            $table->text('description');
            $table->date('requested_date');
            $table->decimal('estimated_amount', 12, 2);
            $table->decimal('actual_amount', 12, 2)->nullable();
            $table->date('actual_expense_date')->nullable();
            $table->string('status', 40)->default('pending_approval');
            $table->json('attachments')->nullable();
            $table->string('receipt_path')->nullable();
            $table->text('completion_notes')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('finance_expense_id')->nullable()->constrained('finance_expenses')->nullOnDelete();
            $table->timestamps();
            $table->index(['store_id', 'branch_id', 'status']);
            $table->index(['branch_id', 'requested_date']);
        });

        Schema::table('finance_expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('finance_expenses', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('store_id')->constrained('branches')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_expense_requests');

        if (Schema::hasColumn('finance_expenses', 'branch_id')) {
            Schema::table('finance_expenses', function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
            });
        }
    }
};
