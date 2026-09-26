<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employee_benefit_requests', function (Blueprint $table) {
            $table->decimal('company_payment_amount', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('employee_benefit_requests', fn (Blueprint $table) => $table->dropColumn('company_payment_amount'));
    }
};
