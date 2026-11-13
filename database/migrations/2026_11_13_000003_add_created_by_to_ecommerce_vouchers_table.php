<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ecommerce_vouchers') || Schema::hasColumn('ecommerce_vouchers', 'created_by')) {
            return;
        }

        Schema::table('ecommerce_vouchers', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('store_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('ecommerce_vouchers') && Schema::hasColumn('ecommerce_vouchers', 'created_by')) {
            Schema::table('ecommerce_vouchers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('created_by');
            });
        }
    }
};
