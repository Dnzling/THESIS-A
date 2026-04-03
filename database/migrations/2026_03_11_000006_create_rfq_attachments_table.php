<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rfq_attachments')) {
            Schema::create('rfq_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rfq_id');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type');
            $table->integer('file_size');
            $table->enum('attachment_type', ['specification', 'drawing', 'terms', 'other'])->default('other');
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamps();

            $table->foreign('rfq_id')->references('id')->on('request_for_quotations')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('restrict');
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_attachments');
    }
};
