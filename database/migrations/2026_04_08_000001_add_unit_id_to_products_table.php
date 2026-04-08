<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (!Schema::hasColumn('products', 'unit_id')) {
                $table->foreignId('unit_id')
                    ->nullable()
                    ->constrained('units')
                    ->nullOnDelete()
                    ->after('subcategory_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'unit_id')) {
                $table->dropConstrainedForeignId('unit_id');
            }
        });
    }
};
