<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 50)->unique();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('restrict');
            $table->foreignId('to_branch_id')->nullable()->constrained('branches')->onDelete('restrict');
            
            $table->enum('return_type', [
                'supplier_return',
                'branch_return',
                'damaged_return',
                'expired_return',
                'quality_return'
            ])->default('supplier_return');
            
            $table->enum('status', [
                'draft',
                'requested',
                'approved',
                'in_transit',
                'received',
                'cancelled',
                'rejected'
            ])->default('draft');
            
            $table->enum('approval_policy_used', [
                'manager_only',
                'both_branches',
                'supplier_required',
                'auto_approve'
            ])->nullable()->comment('Which policy was applied');
            
            $table->decimal('total_value', 12, 2)->default(0);
            $table->decimal('return_cost', 10, 2)->default(0);
            $table->text('cost_calculation_notes')->nullable();
            
            // Dates
            $table->date('requested_date')->nullable();
            $table->date('approved_date')->nullable();
            $table->date('shipped_date')->nullable();
            $table->date('received_date')->nullable();
            $table->date('expected_return_date')->nullable();
            
            // People
            $table->foreignId('requested_by')->constrained('employees');
            $table->foreignId('approved_by')->nullable()->constrained('employees');
            $table->foreignId('shipped_by')->nullable()->constrained('employees');
            $table->foreignId('received_by')->nullable()->constrained('employees');
            
            // Logistics
            $table->string('vehicle_type', 100)->nullable();
            $table->string('driver_name', 100)->nullable();
            $table->string('driver_contact', 50)->nullable();
            $table->string('tracking_number', 100)->nullable();
            
            $table->enum('return_reason', [
                'damaged',
                'expired',
                'quality_issue',
                'wrong_item',
                'overstock',
                'customer_return',
                'supplier_policy',
                'other'
            ])->nullable();
            
            $table->text('reason_details')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['from_branch_id', 'status'], 'idx_returns_from_status');
            $table->index(['supplier_id', 'status'], 'idx_returns_supplier_status');
            $table->index(['to_branch_id', 'status'], 'idx_returns_to_status');
            $table->index('return_type', 'idx_returns_type');
            $table->index('return_number', 'idx_returns_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_returns');
    }
};
