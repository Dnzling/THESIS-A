<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_resolutions', function (Blueprint $table) {
            $table->id();
            $table->string('resolution_number', 60)->unique();
            $table->foreignId('original_goods_receipt_id')->constrained('goods_receipts')->restrictOnDelete();
            $table->foreignId('follow_up_goods_receipt_id')->nullable()->constrained('goods_receipts')->nullOnDelete();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->string('resolution_type', 40);
            $table->json('items');
            $table->text('procurement_notes')->nullable();
            $table->string('status', 40)->default('pending_supplier');
            $table->text('supplier_rejection_reason')->nullable();
            $table->date('promised_delivery_date')->nullable();
            $table->string('delivery_note_number', 100)->nullable();
            $table->text('supplier_delivery_notes')->nullable();
            $table->string('proof_path')->nullable();
            $table->foreignId('flagged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('supplier_responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('supplier_responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['purchase_order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_resolutions');
    }
};
