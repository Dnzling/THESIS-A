<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_government_ids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('deduction_type_id')->nullable()->constrained('deduction_types')->nullOnDelete();
            $table->string('id_type', 100);
            $table->string('id_number', 150)->nullable();
            $table->string('id_file_path')->nullable();
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['employee_id', 'deduction_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_government_ids');
    }
};
