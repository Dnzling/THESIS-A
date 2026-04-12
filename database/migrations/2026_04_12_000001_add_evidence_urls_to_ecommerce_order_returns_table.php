<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_order_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('ecommerce_order_returns', 'evidence_urls')) {
                $table->json('evidence_urls')->nullable()->after('details');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_order_returns', function (Blueprint $table) {
            if (Schema::hasColumn('ecommerce_order_returns', 'evidence_urls')) {
                $table->dropColumn('evidence_urls');
            }
        });
    }
};

