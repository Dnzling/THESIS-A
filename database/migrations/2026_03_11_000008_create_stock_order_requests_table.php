<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create Stock Order Requests table
        Schema::create('stock_order_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            
            // Store & location
            $table->foreignId('store_id')->constrained('stores');
            $table->foreignId('branch_inventory_id')->constrained('branch_inventory');
            
            // Request details
            $table->integer('requested_quantity');
            $table->longText('notes')->nullable();
            
            // Status workflow
            $table->enum('status', [
                'pending',              // Awaiting approval
                'approved',             // Approved, ready for PO conversion
                'converted_to_po',      // Converted to purchase order
                'partially_ordered',    // Partially ordered (multiple POs)
                'rejected',             // Rejected by approver
                'cancelled',            // Cancelled
            ])->default('pending');
            
            // Approval tracking
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->timestamp('approved_date')->nullable();
            $table->timestamp('conversion_date')->nullable();
            
            // Audit
            $table->timestamps();
            $table->softDeletes();
        });

        // Add stock_order_request_id to purchase_orders
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('stock_order_request_id')
                ->nullable()
                ->after('supplier_quotation_id')
                ->constrained('stock_order_requests')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        // Drop foreign key from purchase_orders first
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['stock_order_request_id']);
            $table->dropColumn('stock_order_request_id');
        });

        // Drop stock order requests table
        Schema::dropIfExists('stock_order_requests');
    }
};
