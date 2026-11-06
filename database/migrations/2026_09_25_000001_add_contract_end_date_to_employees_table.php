<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('employees', 'contract_end_date')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->date('contract_end_date')->nullable()->after('employment_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('employees', 'contract_end_date')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('contract_end_date');
            });
        }
    }
};
