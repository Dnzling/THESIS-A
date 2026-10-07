<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales_crm_leads', function (Blueprint $table) {
            $table->string('customer_type', 20)->default('individual')->after('full_name');
            $table->string('company_name', 150)->nullable()->after('customer_type');
            $table->string('address', 255)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('sales_crm_leads', fn (Blueprint $table) => $table->dropColumn(['customer_type', 'company_name', 'address']));
    }
};
