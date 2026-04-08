<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Temporarily widen column to allow new labels, then normalize.
        DB::statement("ALTER TABLE interviews MODIFY interview_type VARCHAR(100) NOT NULL");

        // Map legacy values to new enum values before altering the column.
        DB::table('interviews')
            ->whereIn('interview_type', ['Phone', 'Phone Screen', 'Video', 'Technical', 'HR Round', 'Practical Test', 'Final Round', 'In-person'])
            ->update([
                'interview_type' => DB::raw("
                    CASE
                        WHEN interview_type IN ('Phone', 'Phone Screen') THEN 'Screening'
                        WHEN interview_type IN ('Video', 'Technical', 'Practical Test') THEN 'Technical/Skills Test'
                        WHEN interview_type IN ('HR Round', 'Final Round', 'In-person') THEN 'Final Interview'
                        ELSE 'Screening'
                    END
                "),
            ]);
    }

    public function down(): void
    {
        // No-op: legacy values are not recoverable once normalized.
    }
};
