<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_return_investigation_tickets', function (Blueprint $table): void {
            $table->text('findings')->nullable()->after('notes');
            $table->enum('recommended_resolution', ['refund', 'replacement', 'reject'])->nullable()->after('findings');
            $table->foreignId('completed_by')->nullable()->after('recommended_resolution')->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable()->after('completed_by');
        });
    }

    public function down(): void
    {
        Schema::table('crm_return_investigation_tickets', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('completed_by');
            $table->dropColumn(['findings', 'recommended_resolution', 'completed_at']);
        });
    }
};
