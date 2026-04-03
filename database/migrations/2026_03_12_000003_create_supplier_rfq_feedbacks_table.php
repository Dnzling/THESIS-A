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
        Schema::create('supplier_rfq_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_portal_id');
            $table->unsignedBigInteger('rfq_id');
            $table->unsignedBigInteger('rfq_item_id');
            $table->decimal('quoted_price', 12, 2);
            $table->text('description')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->foreign('supplier_portal_id')->references('id')->on('supplier_portals')->onDelete('cascade');
            $table->foreign('rfq_id')->references('id')->on('request_for_quotations')->onDelete('cascade');
            $table->foreign('rfq_item_id')->references('id')->on('rfq_items')->onDelete('cascade');
            
            // Ensure one response per supplier per RFQ item
            $table->unique(['supplier_portal_id', 'rfq_item_id'], 'uq_rfq_feedback_portal_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_rfq_feedbacks');
    }
};
