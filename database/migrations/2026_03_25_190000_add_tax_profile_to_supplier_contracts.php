<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('supplier_contracts', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->nullable()->after('discount_percentage');
            }
            if (!Schema::hasColumn('supplier_contracts', 'is_tax_exempt')) {
                $table->boolean('is_tax_exempt')->default(false)->after('tax_rate');
            }
            if (!Schema::hasColumn('supplier_contracts', 'tax_note')) {
                $table->text('tax_note')->nullable()->after('is_tax_exempt');
            }
        });
    }

    public function down(): void
    {
        Schema::table('supplier_contracts', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_contracts', 'tax_note')) {
                $table->dropColumn('tax_note');
            }
            if (Schema::hasColumn('supplier_contracts', 'is_tax_exempt')) {
                $table->dropColumn('is_tax_exempt');
            }
            if (Schema::hasColumn('supplier_contracts', 'tax_rate')) {
                $table->dropColumn('tax_rate');
            }
        });
    }
};
