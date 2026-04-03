<?php
// backend/database/migrations/2026_03_04_100016_create_purchase_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 50)->unique();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained()->onDelete('restrict');
            $table->foreignId('purchase_requisition_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('rfq_id')->nullable()->constrained('request_for_quotations')->onDelete('set null');
            $table->foreignId('supplier_quotation_id')->nullable()->constrained('supplier_quotations')->onDelete('set null');
            
            $table->enum('status', [
                'draft',
                'pending_approval',
                'partially_approved',
                'fully_approved',
                'finance_review',
                'finance_approved',
                'ordered',
                'partially_received',
                'received',
                'cancelled',
                'rejected'
            ])->default('draft');
            
            // Financial details
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            
            $table->integer('approval_tier_level')->nullable()
                ->comment('Which tier this PO falls into (1, 2, 3, 4...)');
            
            $table->json('required_approvers')->nullable()
                ->comment('Roles that must approve based on amount');
            
            $table->json('approvals_received')->nullable()
                ->comment('Track each approval');
            
            $table->json('rejection_details')->nullable()
                ->comment('Who rejected and why');
            
            $table->boolean('rfq_required')->default(false)
                ->comment('Based on procurement_settings.rfq_policy');
            
            // Payment
            $table->enum('payment_status', [
                'not_required',
                'pending',
                'finance_approved',
                'processing',
                'paid',
                'partially_paid',
                'overdue'
            ])->default('pending');
            
            $table->enum('payment_terms', [
                'cash_on_delivery',
                'net_7',
                'net_15',
                'net_30',
                'net_60',
                'advance_payment'
            ])->default('net_30');
            
            // Dates
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->date('payment_due_date')->nullable();
            
            $table->foreignId('created_by')->constrained('employees');
            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // ✅ FIXED: Custom index names
            $table->index(['branch_id', 'status'], 'idx_po_branch_status');
            $table->index(['supplier_id', 'status'], 'idx_po_supplier_status');
            $table->index(['total_amount', 'approval_tier_level'], 'idx_po_amount_tier');
            $table->index('po_number', 'idx_po_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};