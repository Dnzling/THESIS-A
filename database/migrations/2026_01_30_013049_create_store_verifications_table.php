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
        Schema::create('store_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');

            // Business documents
            $table->string('business_registration_number')->nullable();
            $table->string('business_registration_file')->nullable(); // File path
            $table->date('business_registration_date')->nullable();

            // Government ID
            $table->enum('gov_id_type', ['sss', 'tin', 'passport', 'driver_license', 'umid', 'national_id'])->nullable();
            $table->string('gov_id_number')->nullable();
            $table->string('gov_id_front_file')->nullable(); // Front photo
            $table->string('gov_id_back_file')->nullable(); // Back photo

            // Selfie with ID
            $table->string('selfie_with_id_file')->nullable();

            // Additional documents
            $table->string('business_permit_file')->nullable();
            $table->string('tax_certificate_file')->nullable();
            $table->string('other_documents')->nullable(); // JSON for multiple files

            // Verification details
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            $table->index(['store_id', 'submitted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_verifications');
    }
};
