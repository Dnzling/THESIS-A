<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->unsignedTinyInteger('minimum_age')->default(18)->after('employment_type');
        });
    }

    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropColumn('minimum_age');
        });
    }
};
