<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('finance_expenses', 'reference_type')) {
                $table->string('reference_type')->nullable()->after('reference_number');
            }
            if (!Schema::hasColumn('finance_expenses', 'reference_id')) {
                $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');
            }
            if (!Schema::hasColumn('finance_expenses', 'currency')) {
                $table->string('currency', 3)->nullable()->after('reference_id');
            }

            $table->index(['reference_type', 'reference_id'], 'idx_finexp_reference');
        });
    }

    public function down(): void
    {
        Schema::table('finance_expenses', function (Blueprint $table) {
            if (Schema::hasColumn('finance_expenses', 'currency')) {
                $table->dropColumn('currency');
            }
            if (Schema::hasColumn('finance_expenses', 'reference_id')) {
                $table->dropColumn('reference_id');
            }
            if (Schema::hasColumn('finance_expenses', 'reference_type')) {
                $table->dropColumn('reference_type');
            }

            $table->dropIndex('idx_finexp_reference');
        });
    }
};
