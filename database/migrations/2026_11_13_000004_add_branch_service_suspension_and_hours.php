<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'opening_hours')) {
                $table->json('opening_hours')->nullable();
            }
        });

        Schema::create('branch_service_suspensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 500)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->index(['branch_id', 'start_date', 'end_date'], 'branch_service_suspensions_window_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_service_suspensions');
        if (Schema::hasColumn('branches', 'opening_hours')) {
            Schema::table('branches', fn (Blueprint $table) => $table->dropColumn('opening_hours'));
        }
    }
};
