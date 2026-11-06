<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_return_investigation_tickets', function (Blueprint $table): void {
            $table->string('findings_attachment_path')->nullable()->after('findings');
        });
    }

    public function down(): void
    {
        Schema::table('crm_return_investigation_tickets', function (Blueprint $table): void {
            $table->dropColumn('findings_attachment_path');
        });
    }
};
