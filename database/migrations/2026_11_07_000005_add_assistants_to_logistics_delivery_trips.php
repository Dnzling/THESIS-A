<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('logistics_delivery_trips', 'assistant_user_ids')) {
            Schema::table('logistics_delivery_trips', function (Blueprint $table) {
                $table->json('assistant_user_ids')->nullable()->after('driver_user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('logistics_delivery_trips', 'assistant_user_ids')) {
            Schema::table('logistics_delivery_trips', function (Blueprint $table) {
                $table->dropColumn('assistant_user_ids');
            });
        }
    }
};
