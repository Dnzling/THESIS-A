<?php
// backend/database/migrations/2026_03_04_100009_create_purchase_requisitions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('pr_number', 50)->unique();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            
            $table->enum('requisition_type', [
                'regular',
                'urgent',
                'new_product',
                'seasonal',
                'emergency'
            ])->default('regular');
            
            $table->enum('status', [
                'draft',
                'submitted',
                'warehouse_approved',
                'branch_manager_approved',
                'pending_central_review',
                'procurement_processing',
                'rfq_sent',
                'quotes_received',
                'supplier_selected',
                'po_created',
                'rejected',
                'cancelled'
            ])->default('draft');
            
            $table->decimal('estimated_amount', 12, 2)->nullable();
            
            $table->enum('procurement_route', [
                'branch_direct',
                'centralized',
                'rfq_required'
            ])->nullable()->comment('Auto-determined based on procurement_settings');
            
            $table->json('required_approvals')->nullable()
                ->comment('JSON array of roles that must approve');
            
            $table->json('approval_chain')->nullable()
                ->comment('Track who approved and when');
            
            $table->date('required_date');
            $table->text('reason')->nullable();
            $table->integer('priority')->default(3)->comment('1=Urgent, 5=Low');
            
            $table->foreignId('requested_by')->constrained('employees');
            $table->timestamp('submitted_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // ✅ FIXED: Custom index names
            $table->index(['branch_id', 'status'], 'idx_pr_branch_status');
            $table->index(['procurement_route', 'status'], 'idx_pr_route_status');
            $table->index('pr_number', 'idx_pr_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requisitions');
    }
};