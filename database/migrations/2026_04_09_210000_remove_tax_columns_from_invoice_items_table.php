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
        Schema::table('invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_items', 'tax_rate')) {
                $table->dropColumn('tax_rate');
            }

            if (Schema::hasColumn('invoice_items', 'tax_amount')) {
                $table->dropColumn('tax_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_items', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->nullable()->after('line_amount');
            }

            if (!Schema::hasColumn('invoice_items', 'tax_amount')) {
                $table->decimal('tax_amount', 12, 2)->nullable()->after('tax_rate');
            }
        });
    }
};

