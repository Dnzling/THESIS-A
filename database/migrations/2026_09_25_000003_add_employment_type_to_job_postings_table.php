<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('job_postings', 'employment_type')) {
            Schema::table('job_postings', function (Blueprint $table) {
                $table->string('employment_type', 30)->default('full_time')->after('department');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('job_postings', 'employment_type')) {
            Schema::table('job_postings', function (Blueprint $table) {
                $table->dropColumn('employment_type');
            });
        }
    }
};
