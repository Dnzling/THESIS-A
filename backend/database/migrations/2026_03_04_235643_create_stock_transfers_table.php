<?php
// backend/database/migrations/2026_03_04_100023_create_stock_transfers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 50)->unique();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignId('to_branch_id')->constrained('branches')->onDelete('restrict');
            
            $table->enum('status', [
                'draft',
                'requested',
                'sender_approved',
                'receiver_acknowledged',
                'pending_finance',
                'finance_approved',
                'approved',
                'in_transit',
                'received',
                'partially_received',
                'cancelled',
                'rejected'
            ])->default('draft');
            
            $table->enum('approval_policy_used', [
                'sender_only',
                'both_branches',
                'finance_required',
                'auto_approve'
            ])->nullable()->comment('Which policy was applied');
            
            $table->enum('cost_method', [
                'none',
                'distance_based',
                'manual_entry',
                'fixed_fee',
                'value_percentage'
            ])->nullable();
            
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->decimal('transfer_cost', 10, 2)->default(0);
            $table->decimal('goods_value', 12, 2)->default(0);
            $table->text('cost_calculation_notes')->nullable();
            
            // Dates
            $table->date('requested_date')->nullable();
            $table->date('sender_approved_date')->nullable();
            $table->date('receiver_acknowledged_date')->nullable();
            $table->date('finance_approved_date')->nullable();
            $table->date('shipped_date')->nullable();
            $table->date('received_date')->nullable();
            $table->date('expected_delivery_date')->nullable();
            
            // People
            $table->foreignId('requested_by')->constrained('employees');
            $table->foreignId('sender_approved_by')->nullable()->constrained('employees');
            $table->foreignId('receiver_acknowledged_by')->nullable()->constrained('employees');
            $table->foreignId('finance_approved_by')->nullable()->constrained('employees');
            $table->foreignId('shipped_by')->nullable()->constrained('employees');
            $table->foreignId('received_by')->nullable()->constrained('employees');
            
            // Logistics
            $table->string('vehicle_type', 100)->nullable();
            $table->string('driver_name', 100)->nullable();
            $table->string('driver_contact', 50)->nullable();
            $table->string('tracking_number', 100)->nullable();
            
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // ✅ FIXED: Custom index names
            $table->index(['from_branch_id', 'status'], 'idx_strf_from_status');
            $table->index(['to_branch_id', 'status'], 'idx_strf_to_status');
            $table->index('approval_policy_used', 'idx_strf_policy');
            $table->index('transfer_number', 'idx_strf_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};