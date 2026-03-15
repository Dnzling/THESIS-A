<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'store_id')) {
                $table->foreignId('store_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('stores')
                    ->nullOnDelete();
            }

            // Ensure unique index matches store scope
            if (Schema::hasColumn('roles', 'store_id')) {
                $table->dropUnique(['name']);
                $table->unique(['store_id', 'name']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['store_id', 'name']);
            $table->unique(['name']);
            $table->dropConstrainedForeignId('store_id');
        });
    }
};
