<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_returns', function (Blueprint $table) {
            $table->string('supplier_resolution', 40)->nullable()->after('rejection_reason');
            $table->text('supplier_resolution_notes')->nullable()->after('supplier_resolution');
        });
    }

    public function down(): void
    {
        Schema::table('stock_returns', function (Blueprint $table) {
            $table->dropColumn(['supplier_resolution', 'supplier_resolution_notes']);
        });
    }
};
