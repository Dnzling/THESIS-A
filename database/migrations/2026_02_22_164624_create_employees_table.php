<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('employee_number')->nullable(); // Store-specific employee ID

            // Personal Information
            $table->string('fname');
            $table->string('lname');
            $table->string('phone')->nullable();
            $table->text('province')->nullable();
            $table->text('city')->nullable();
            $table->text('address')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();

            // Employment Details
            $table->date('hire_date');
            $table->foreignId('role_id')->constrained('roles')->cascadeOnUpdate()->restrictOnDelete();

            $table->string('department')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern'])->default('full_time');
            $table->decimal('salary', 10, 2)->nullable(); // Monthly salary
            $table->string('bank_account')->nullable();
            $table->string('tax_id')->nullable();

            // Emergency Contact
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();

            // Documents
            $table->string('id_document_path')->nullable(); // ID scan
            $table->string('contract_path')->nullable(); // Employment contract

            // Status
            $table->enum('status', ['active', 'on_leave', 'suspended', 'terminated'])->default('active');
            $table->date('termination_date')->nullable();
            $table->text('termination_reason')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users');

            // Indexes
            $table->unique(['store_id', 'employee_number']);
            $table->index(['store_id', 'status', 'branch_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('employees');
    }
};
