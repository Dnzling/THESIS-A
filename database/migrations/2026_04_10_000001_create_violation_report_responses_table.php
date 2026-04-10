<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('violation_report_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('violation_report_id');
            $table->unsignedBigInteger('responder_user_id')->nullable();
            $table->string('responder_type')->default('party');
            $table->text('message')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->index(['violation_report_id', 'created_at']);
            $table->foreign('violation_report_id')->references('id')->on('violation_reports')->onDelete('cascade');
            $table->foreign('responder_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('violation_report_responses');
    }
};

