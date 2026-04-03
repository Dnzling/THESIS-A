<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rfq_suppliers')) {
            Schema::create('rfq_suppliers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rfq_id');
            $table->unsignedBigInteger('supplier_id');
            $table->enum('status', ['pending', 'submitted', 'declined', 'no_interest'])->default('pending');
            $table->timestamp('invited_at');
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->text('decline_reason')->nullable();
            $table->timestamps();

            $table->foreign('rfq_id')->references('id')->on('request_for_quotations')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->unique(['rfq_id', 'supplier_id']);
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_suppliers');
    }
};
