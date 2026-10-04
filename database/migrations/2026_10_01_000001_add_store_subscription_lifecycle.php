<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('subscription_status', 20)->nullable()->after('subscription_tier');
            $table->timestamp('position_setup_completed_at')->nullable()->after('trial_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['subscription_status', 'position_setup_completed_at']);
        });
    }
};
