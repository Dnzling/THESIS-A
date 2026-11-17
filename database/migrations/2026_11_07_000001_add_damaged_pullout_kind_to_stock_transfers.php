<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->string('transfer_kind', 40)->default('standard')->after('to_branch_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropIndex(['transfer_kind']);
            $table->dropColumn('transfer_kind');
        });
    }
};
