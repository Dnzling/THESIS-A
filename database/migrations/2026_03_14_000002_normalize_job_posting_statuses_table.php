<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE job_postings SET status = 'Open' WHERE status = 'Active'");
        DB::statement("UPDATE job_postings SET status = 'On Hold' WHERE status = 'Draft'");
        DB::statement("ALTER TABLE job_postings MODIFY COLUMN status ENUM('Open','Closed','On Hold') NOT NULL DEFAULT 'Open'");
    }

    public function down(): void
    {
        DB::statement("UPDATE job_postings SET status = 'Active' WHERE status = 'Open'");
        DB::statement("UPDATE job_postings SET status = 'Draft' WHERE status = 'On Hold'");
        DB::statement("ALTER TABLE job_postings MODIFY COLUMN status ENUM('Active','Closed','Draft') NOT NULL DEFAULT 'Draft'");
    }
};
