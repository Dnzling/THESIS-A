<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_wholesale_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('crm_lead_id')->constrained('sales_crm_leads')->restrictOnDelete();
            $table->string('quote_number', 50)->unique();
            $table->string('status', 20)->default('draft');
            $table->json('items');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->date('valid_until')->nullable();
            $table->string('payment_terms', 150)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_pos_orders')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['store_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_wholesale_quotes');
    }
};
