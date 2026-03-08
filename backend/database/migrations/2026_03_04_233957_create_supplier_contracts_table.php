<?php
// backend/database/migrations/2026_03_04_100008_create_supplier_contracts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number', 50)->unique();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained()->onDelete('restrict');
            
            $table->string('contract_title');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('contract_type', [
                'fixed_price',
                'volume_discount',
                'consignment',
                'exclusive'
            ])->default('fixed_price');
            
            $table->decimal('minimum_order_value', 12, 2)->nullable();
            $table->integer('payment_terms_days')->default(30);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            
            $table->text('terms_conditions')->nullable();
            $table->string('contract_file_path')->nullable();
            
            $table->enum('status', ['draft', 'active', 'expired', 'terminated'])->default('draft');
            
            $table->foreignId('created_by')->constrained('employees');
            $table->timestamps();
            
            $table->index(['store_id', 'supplier_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_contracts');
    }
};