<?php
// database/migrations/2024_01_01_000005_create_pay_periods_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pay_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // e.g., "February 2024 - First Half"
            $table->date('start_date');
            $table->date('end_date');
            $table->date('cutoff_date'); // Last date for attendance calculation
            $table->enum('status', ['draft', 'processing', 'locked', 'completed'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['start_date', 'end_date']);
            $table->index(['status', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pay_periods');
    }
};
