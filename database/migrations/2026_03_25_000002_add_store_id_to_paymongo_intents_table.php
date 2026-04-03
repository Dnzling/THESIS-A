<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paymongo_intents', function (Blueprint $table) {
            if (!Schema::hasColumn('paymongo_intents', 'store_id')) {
                $table->foreignId('store_id')->nullable()->after('id')->constrained('stores')->nullOnDelete();
                $table->index('store_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('paymongo_intents', function (Blueprint $table) {
            if (Schema::hasColumn('paymongo_intents', 'store_id')) {
                $table->dropConstrainedForeignId('store_id');
            }
        });
    }
};
