<?php
// backend/database/migrations/2026_03_04_100011_create_request_for_quotations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_for_quotations', function (Blueprint $table) {
            $table->id();
            $table->string('rfq_number', 50)->unique(); // e.g., "RFQ-2026-00001"
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_requisition_id')->nullable()->constrained()->onDelete('set null');
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('issue_date');
            $table->date('deadline_date'); // Quote submission deadline
            
            $table->enum('status', [
                'draft',
                'sent',
                'quotes_received',
                'evaluation',
                'awarded',
                'cancelled'
            ])->default('draft');
            
            $table->foreignId('created_by')->constrained('employees');
            $table->foreignId('awarded_to_supplier_id')->nullable()->constrained('suppliers');
            $table->timestamp('awarded_at')->nullable();
            
            $table->text('evaluation_notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['store_id', 'status']);
            $table->index('rfq_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_for_quotations');
    }
};