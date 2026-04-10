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
                'supply',
                'service'
            ])->default('supply');
            
            $table->decimal('minimum_order_value', 12, 2)->nullable();
            $table->decimal('discount_percentage', 5, 2)->default(0);
            
            $table->text('terms_conditions')->nullable();
            $table->string('contract_file_path')->nullable();
            
            $table->enum('status', ['draft', 'pending', 'active', 'completed', 'terminated', 'rejected'])->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('rejected_by_user_id')->nullable();
            $table->timestamp('rejected_at')->nullable();
            
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
