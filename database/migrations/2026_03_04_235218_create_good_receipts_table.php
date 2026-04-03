<?php
// backend/database/migrations/2026_03_04_100018_create_goods_receipts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number', 50)->unique();
            $table->foreignId('purchase_order_id')->constrained()->onDelete('restrict');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            
            $table->date('receipt_date');
            $table->time('receipt_time');
            
            $table->enum('receipt_status', [
                'full',
                'partial',
                'damaged',
                'rejected'
            ])->default('full');
            
            $table->foreignId('received_by')->constrained('employees');
            $table->foreignId('verified_by')->nullable()->constrained('employees');
            
            $table->string('delivery_note_number', 100)->nullable();
            $table->string('vehicle_number', 50)->nullable();
            $table->string('driver_name', 100)->nullable();
            
            $table->text('discrepancy_notes')->nullable();
            $table->text('quality_notes')->nullable();
            
            $table->timestamps();
            
            // ✅ FIXED: Custom index names
            $table->index(['branch_id', 'receipt_date'], 'idx_grn_branch_date');
            $table->index('grn_number', 'idx_grn_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};