<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE interviews MODIFY interview_type VARCHAR(100) NOT NULL');
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE interviews MODIFY interview_type ENUM('Screening','Technical/Skills Test','Final Interview') NOT NULL");
    }
};
