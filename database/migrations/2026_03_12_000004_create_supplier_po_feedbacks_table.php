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
        Schema::create('supplier_po_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_portal_id');
            $table->unsignedBigInteger('purchase_order_id');
            $table->enum('response', ['accepted', 'rejected'])->default('accepted');
            $table->text('rejection_reason')->nullable();
            $table->enum('receipt_status', ['pending', 'confirmed'])->default('pending');
            $table->date('expected_delivery_date')->nullable();
            $table->integer('delivery_quantity')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->timestamp('receipt_confirmed_at')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->foreign('supplier_portal_id')->references('id')->on('supplier_portals')->onDelete('cascade');
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');

            // One feedback per supplier per PO
            $table->unique(['supplier_portal_id', 'purchase_order_id'], 'uq_po_feedback_portal_po');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_po_feedbacks');
    }
};
