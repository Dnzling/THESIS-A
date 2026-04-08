<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('violation_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('reporter_user_id')->nullable();
            $table->string('reporter_type')->default('customer');
            $table->string('report_reason');
            $table->text('report_details')->nullable();
            $table->json('evidence_urls')->nullable();
            $table->string('status')->default('pending');
            $table->string('action_type')->nullable();
            $table->text('action_reason')->nullable();
            $table->unsignedBigInteger('action_by')->nullable();
            $table->timestamp('actioned_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index(['action_type', 'status']);

            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
            $table->foreign('reporter_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('action_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('violation_reports');
    }
};
