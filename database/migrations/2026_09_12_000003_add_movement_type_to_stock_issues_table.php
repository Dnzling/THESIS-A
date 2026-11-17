<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_issues', function (Blueprint $table) {
            $table->string('issue_type', 50)->default('other')->change();
            $table->string('movement_type', 20)->default('deduct')->after('issue_type');
        });
    }

    public function down(): void
    {
        Schema::table('stock_issues', function (Blueprint $table) {
            $table->dropColumn('movement_type');
            $table->enum('issue_type', ['damaged', 'lost', 'expired', 'theft', 'other'])->default('other')->change();
        });
    }
};
