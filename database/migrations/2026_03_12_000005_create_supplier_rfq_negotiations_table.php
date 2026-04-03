<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_rfq_negotiations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_rfq_feedback_id');
            $table->unsignedBigInteger('supplier_portal_id');
            $table->unsignedBigInteger('rfq_id');
            $table->unsignedBigInteger('rfq_item_id');
            $table->decimal('counter_price', 12, 2);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamps();

            $table->foreign('supplier_rfq_feedback_id')->references('id')->on('supplier_rfq_feedbacks')->onDelete('cascade');
            $table->foreign('supplier_portal_id')->references('id')->on('supplier_portals')->onDelete('cascade');
            $table->foreign('rfq_id')->references('id')->on('request_for_quotations')->onDelete('cascade');
            $table->foreign('rfq_item_id')->references('id')->on('rfq_items')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_rfq_negotiations');
    }
};
