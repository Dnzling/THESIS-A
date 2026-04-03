<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->onDelete('restrict');
            $table->string('department')->nullable();
            $table->string('category');
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->enum('status', [
                'draft',
                'pending_approval',
                'approved',
                'paid',
                'rejected',
                'cancelled',
            ])->default('pending_approval');

            $table->string('payment_method')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('reference_number')->nullable();

            $table->longText('description')->nullable();
            $table->longText('notes')->nullable();

            $table->foreignId('requested_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_expenses');
    }
};
