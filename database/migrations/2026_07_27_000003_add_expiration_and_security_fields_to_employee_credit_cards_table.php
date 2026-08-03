<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_credit_cards', function (Blueprint $table) {
            $table->string('expiration_month', 2)->nullable()->after('card_type');
            $table->string('expiration_year', 4)->nullable()->after('expiration_month');
            $table->string('security_code', 10)->nullable()->after('expiration_year');
        });
    }

    public function down(): void
    {
        Schema::table('employee_credit_cards', function (Blueprint $table) {
            $table->dropColumn(['expiration_month', 'expiration_year', 'security_code']);
        });
    }
};
