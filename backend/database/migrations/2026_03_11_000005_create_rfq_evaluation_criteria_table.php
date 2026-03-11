<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rfq_evaluation_criteria')) {
            Schema::create('rfq_evaluation_criteria', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rfq_id');
            $table->string('criterion_name');
            $table->integer('weight_percentage');
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('rfq_id')->references('id')->on('request_for_quotations')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_evaluation_criteria');
    }
};
