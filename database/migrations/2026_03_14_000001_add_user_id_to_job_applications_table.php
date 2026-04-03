<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('job_applications', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('job_posting_id');
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->index(['user_id', 'job_posting_id'], 'job_applications_user_posting_index');
            }
        });

        Schema::table('job_applications', function (Blueprint $table) {
            try {
                $table->dropUnique('job_applications_email_unique');
            } catch (\Throwable $e) {
            }

            $table->unique(['job_posting_id', 'email'], 'job_applications_posting_email_unique');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            try {
                $table->dropUnique('job_applications_posting_email_unique');
            } catch (\Throwable $e) {
            }

            $table->unique('email', 'job_applications_email_unique');

            if (Schema::hasColumn('job_applications', 'user_id')) {
                try {
                    $table->dropForeign(['user_id']);
                } catch (\Throwable $e) {
                }
                $table->dropIndex('job_applications_user_posting_index');
                $table->dropColumn('user_id');
            }
        });
    }
};
