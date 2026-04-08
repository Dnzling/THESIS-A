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
        Schema::create('supplier_verification_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_portal_id');
            $table->enum('document_type', [
                'business_license',
                'tax_id',
                'company_registration',
                'bank_details'
            ]);
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('file_mime_type');
            $table->unsignedBigInteger('file_size');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('supplier_portal_id')->references('id')->on('supplier_portals')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_verification_documents');
    }
};
