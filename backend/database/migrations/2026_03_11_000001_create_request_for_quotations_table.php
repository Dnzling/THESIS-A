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
        if (!Schema::hasTable('request_for_quotations')) {
            Schema::create('request_for_quotations', function (Blueprint $table) {
            $table->id();
            $table->string('rfq_number')->unique()->comment('RFQ-YYYY-XXXX format');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('issue_date');
            $table->date('deadline_date');
            $table->date('expected_delivery_date')->nullable();
            $table->enum('rfq_type', ['purchase', 'service', 'both'])->default('purchase');
            $table->string('currency', 3)->default('PHP');
            $table->enum('payment_terms', ['net_7', 'net_15', 'net_30', 'net_45', 'net_60', 'cash_on_delivery'])->default('net_30');
            $table->enum('shipping_terms', ['FOB', 'CIF', 'EXW', 'DDP'])->nullable();
            $table->enum('status', ['draft', 'sent', 'receiving', 'awarded', 'completed', 'cancelled'])->default('draft');
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->text('instructions')->nullable();
            $table->text('qualification_requirements')->nullable();
            $table->timestamp('sent_date')->nullable();
            $table->timestamp('awarded_date')->nullable();
            $table->unsignedBigInteger('awarded_supplier_id')->nullable();
            $table->unsignedBigInteger('awarded_quotation_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('awarded_supplier_id')->references('id')->on('suppliers')->onDelete('set null');
        });        }    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_for_quotations');
    }
};
