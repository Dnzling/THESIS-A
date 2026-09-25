<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_contracts', function (Blueprint $table) {
            $table->string('submitted_by_type', 20)->default('store')->after('status');
        });

        DB::table('supplier_contracts')
            ->whereIn('id', DB::table('system_notifications')
                ->select('entity_id')
                ->where('entity_type', 'supplier_contract')
                ->where('action', 'contract_submitted'))
            ->update(['submitted_by_type' => 'supplier']);
    }

    public function down(): void
    {
        Schema::table('supplier_contracts', function (Blueprint $table) {
            $table->dropColumn('submitted_by_type');
        });
    }
};
