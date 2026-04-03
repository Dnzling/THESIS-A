<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('supplier_quotations')) {
            Schema::create('supplier_quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->unique();
            $table->unsignedBigInteger('rfq_id');
            $table->unsignedBigInteger('supplier_id');
            $table->decimal('total_price', 14, 2);
            $table->integer('delivery_days');
            $table->string('payment_terms')->nullable();
            $table->date('validity_date')->nullable();
            $table->decimal('price_per_unit', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['submitted', 'under_review', 'accepted', 'rejected', 'awarded'])->default('submitted');
            $table->date('submitted_date');
            $table->decimal('score', 5, 2)->nullable()->comment('Evaluation score');
            $table->integer('rank')->nullable()->comment('Rank among other quotations');
            $table->timestamps();

            $table->foreign('rfq_id')->references('id')->on('request_for_quotations')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->unique(['rfq_id', 'supplier_id']);
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_quotations');
    }
};
