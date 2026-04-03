<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure the enum matches the new interview types.
        DB::statement("ALTER TABLE interviews MODIFY interview_type ENUM('Screening','Technical/Skills Test','Final Interview') NOT NULL");

        Schema::table('interviews', function (Blueprint $table) {
            if (Schema::hasColumn('interviews', 'duration_minutes')) {
                $table->dropColumn('duration_minutes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            if (!Schema::hasColumn('interviews', 'duration_minutes')) {
                $table->unsignedBigInteger('duration_minutes')->nullable();
            }
        });

        DB::statement("ALTER TABLE interviews MODIFY interview_type ENUM('Phone','Video','In-person') NOT NULL");
    }
};
