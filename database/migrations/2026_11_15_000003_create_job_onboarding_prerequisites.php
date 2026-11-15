<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE job_applications MODIFY status ENUM('Applied','Screening','Rejected','Interview','Offer','Accepted','Declined','Onboarding Pending','Hired') NOT NULL DEFAULT 'Applied'");
        Schema::table('job_postings', function (Blueprint $table) {
            $table->json('hiring_documents')->nullable();
            $table->boolean('requires_signed_contract')->default(true);
        });
        Schema::create('job_onboarding_prerequisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('job_applications')->cascadeOnDelete();
            $table->json('documents')->nullable();
            $table->string('contract_path')->nullable();
            $table->timestamp('contract_signed_at')->nullable();
            $table->foreignId('contract_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique('application_id');
        });
    }

    public function down(): void
    {
        DB::table('job_applications')->where('status', 'Onboarding Pending')->update(['status' => 'Offer']);
        DB::statement("ALTER TABLE job_applications MODIFY status ENUM('Applied','Screening','Rejected','Interview','Offer','Accepted','Declined','Hired') NOT NULL DEFAULT 'Applied'");
        Schema::dropIfExists('job_onboarding_prerequisites');
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropColumn(['hiring_documents', 'requires_signed_contract']);
        });
    }
};
