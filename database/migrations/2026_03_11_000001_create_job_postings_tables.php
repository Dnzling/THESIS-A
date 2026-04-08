<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Job Postings Table
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->string('title');
            $table->string('department');
            $table->longText('description');
            $table->longText('requirements');
            $table->decimal('salary_min', 12, 2)->nullable();
            $table->decimal('salary_max', 12, 2)->nullable();
            $table->enum('status', ['Active', 'Closed', 'Draft'])->default('Draft');
            $table->unsignedBigInteger('created_by');
            $table->text('benefits')->nullable()->comment('JSON format');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        // Job Posting Screening Stages (Configurable per posting)
        Schema::create('job_posting_screening_stages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_posting_id');
            $table->string('stage_name');
            $table->integer('order');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('job_posting_id')->references('id')->on('job_postings')->onDelete('cascade');
            $table->unique(['job_posting_id', 'order']);
        });

        // Job Applications
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_posting_id');
            $table->unsignedBigInteger('employee_id')->nullable()->comment('For internal applicants');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('current_position')->nullable();
            $table->string('current_company')->nullable();
            $table->enum('status', ['Applied', 'Screening', 'Rejected', 'Interview', 'Offer', 'Accepted', 'Declined', 'Hired'])->default('Applied');
            $table->dateTime('application_date');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('job_posting_id')->references('id')->on('job_postings')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete();
        });

        // Application Timeline/History
        Schema::create('application_timeline', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('stage_id')->nullable();
            $table->string('status');
            $table->unsignedBigInteger('changed_by');
            $table->dateTime('changed_at');
            $table->text('notes')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();

            $table->foreign('application_id')->references('id')->on('job_applications')->onDelete('cascade');
            $table->foreign('stage_id')->references('id')->on('job_posting_screening_stages')->nullOnDelete();
            $table->foreign('changed_by')->references('id')->on('users')->onDelete('cascade');
        });

        // Application Documents
        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->enum('document_type', ['Resume', 'CoverLetter', 'ID', 'Certificate', 'Portfolio', 'Other']);
            $table->string('file_name');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->comment('In bytes');
            $table->string('mime_type');
            $table->timestamps();

            $table->foreign('application_id')->references('id')->on('job_applications')->onDelete('cascade');
        });

        // Interviews
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('interviewer_id');
            $table->dateTime('interview_date');
            $table->enum('interview_type', ['Phone', 'Video', 'In-person']);
            $table->text('feedback')->nullable();
            $table->decimal('score', 5, 2)->nullable()->comment('Out of 100');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('duration_minutes')->nullable();
            $table->timestamps();

            $table->foreign('application_id')->references('id')->on('job_applications')->onDelete('cascade');
            $table->foreign('interviewer_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Job Offers
        Schema::create('job_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id')->unique();
            $table->decimal('salary', 12, 2);
            $table->string('position');
            $table->string('department');
            $table->date('start_date');
            $table->text('benefits')->nullable()->comment('JSON format');
            $table->enum('status', ['Pending', 'Accepted', 'Declined', 'Expired'])->default('Pending');
            $table->dateTime('offer_date');
            $table->dateTime('expiry_date')->nullable();
            $table->dateTime('accepted_date')->nullable();
            $table->text('acceptance_notes')->nullable();
            $table->timestamps();

            $table->foreign('application_id')->references('id')->on('job_applications')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_offers');
        Schema::dropIfExists('interviews');
        Schema::dropIfExists('application_documents');
        Schema::dropIfExists('application_timeline');
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_posting_screening_stages');
        Schema::dropIfExists('job_postings');
    }
};
