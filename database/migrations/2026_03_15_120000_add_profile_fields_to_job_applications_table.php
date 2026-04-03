<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('job_applications', 'birthday')) {
                $table->date('birthday')->nullable()->after('current_company');
            }
            if (!Schema::hasColumn('job_applications', 'city')) {
                $table->string('city')->nullable()->after('birthday');
            }
            if (!Schema::hasColumn('job_applications', 'barangay')) {
                $table->string('barangay')->nullable()->after('city');
            }
            if (!Schema::hasColumn('job_applications', 'address')) {
                $table->string('address')->nullable()->after('barangay');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            if (Schema::hasColumn('job_applications', 'birthday')) {
                $table->dropColumn('birthday');
            }
            if (Schema::hasColumn('job_applications', 'city')) {
                $table->dropColumn('city');
            }
            if (Schema::hasColumn('job_applications', 'barangay')) {
                $table->dropColumn('barangay');
            }
            if (Schema::hasColumn('job_applications', 'address')) {
                $table->dropColumn('address');
            }
        });
    }
};
