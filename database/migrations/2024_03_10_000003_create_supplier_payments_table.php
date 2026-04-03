<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create supplier_payments table
        if (!Schema::hasTable('supplier_payments')) {
            Schema::create('supplier_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
                $table->decimal('payment_amount', 15, 2);
                $table->enum('payment_method', ['cash', 'check', 'bank_transfer', 'credit_card', 'online_payment']);
                $table->date('payment_date');
                $table->date('due_date');
                $table->enum('status', ['pending', 'partial', 'paid'])->default('pending');
                $table->string('invoice_number')->nullable();
                $table->string('po_number')->nullable();
                $table->integer('days_overdue')->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
                
                $table->index('supplier_id');
                $table->index('status');
                $table->index('due_date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
