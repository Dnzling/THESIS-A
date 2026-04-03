<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'default_tax_rate')) {
                $table->decimal('default_tax_rate', 5, 2)->nullable()->after('website');
            }
            if (!Schema::hasColumn('suppliers', 'is_tax_exempt')) {
                $table->boolean('is_tax_exempt')->default(false)->after('default_tax_rate');
            }
            if (!Schema::hasColumn('suppliers', 'tax_note')) {
                $table->text('tax_note')->nullable()->after('is_tax_exempt');
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'tax_note')) {
                $table->dropColumn('tax_note');
            }
            if (Schema::hasColumn('suppliers', 'is_tax_exempt')) {
                $table->dropColumn('is_tax_exempt');
            }
            if (Schema::hasColumn('suppliers', 'default_tax_rate')) {
                $table->dropColumn('default_tax_rate');
            }
        });
    }
};
