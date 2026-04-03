<?php
// backend/database/migrations/2026_03_04_100021_create_stock_adjustments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_number', 50)->unique();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            
            $table->enum('type', [
                'physical_count',
                'cycle_count',
                'spot_check',
                'damage',
                'loss',
                'found',
                'correction',
                'writeoff'
            ]);
            
            $table->enum('status', [
                'draft',
                'pending_approval',
                'approved',
                'rejected',
                'applied'
            ])->default('draft');
            
            $table->text('reason');
            $table->date('adjustment_date');
            
            $table->foreignId('created_by')->constrained('employees');
            $table->foreignId('approved_by')->nullable()->constrained('employees');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            $table->timestamps();
            
            // ✅ FIXED: Custom index names
            $table->index(['branch_id', 'status', 'adjustment_date'], 'idx_sadj_branch_status_date');
            $table->index('adjustment_number', 'idx_sadj_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};