<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('stores', 'settings')) {
            Schema::table('stores', function (Blueprint $table): void {
                $table->json('settings')->nullable()->after('subscription_ends_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('stores', 'settings')) {
            Schema::table('stores', function (Blueprint $table): void {
                $table->dropColumn('settings');
            });
        }
    }
};
