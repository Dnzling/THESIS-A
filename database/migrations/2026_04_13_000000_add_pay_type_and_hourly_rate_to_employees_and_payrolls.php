<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->enum('pay_type', ['monthly', 'hourly', 'hybrid'])->default('monthly')->after('status');
            $table->decimal('hourly_rate', 12, 4)->nullable()->after('pay_type');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('hourly_rate_at_generation', 12, 4)->nullable()->after('net_salary');
            $table->json('payslip_snapshot')->nullable()->after('hourly_rate_at_generation');
        });
    }

    public function down()
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['payslip_snapshot', 'hourly_rate_at_generation']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['hourly_rate', 'pay_type']);
        });
    }
};
