<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('stores', 'verified_at')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->timestamp('verified_at')->nullable();
            });
        }

        if (!Schema::hasColumn('stores', 'verified_by')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stores', 'verified_by')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->dropConstrainedForeignId('verified_by');
            });
        }

        if (Schema::hasColumn('stores', 'verified_at')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->dropColumn('verified_at');
            });
        }
    }
};
