<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_wholesale_quotes', function (Blueprint $table) {
            $table->foreignId('crm_lead_id')->nullable()->change();
            $table->string('customer_name', 255)->nullable()->after('crm_lead_id');
            $table->string('customer_email', 255)->nullable()->after('customer_name');
            $table->string('customer_phone', 30)->nullable()->after('customer_email');
        });
    }

    public function down(): void
    {
        Schema::table('sales_wholesale_quotes', function (Blueprint $table) {
            $table->dropColumn(['customer_name', 'customer_email', 'customer_phone']);
        });
    }
};
