<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('job_applications', 'middle_name')) {
            Schema::table('job_applications', fn (Blueprint $table) => $table->string('middle_name', 100)->nullable()->after('first_name'));
        }

        Schema::table('application_timeline', fn (Blueprint $table) => $table->unsignedBigInteger('changed_by')->nullable()->change());
    }

    public function down(): void
    {
        if (Schema::hasColumn('job_applications', 'middle_name')) {
            Schema::table('job_applications', fn (Blueprint $table) => $table->dropColumn('middle_name'));
        }
    }
};
