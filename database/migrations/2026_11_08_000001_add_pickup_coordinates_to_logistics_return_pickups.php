<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_return_pickups', function (Blueprint $table): void {
            $table->decimal('pickup_latitude', 10, 7)->nullable()->after('pickup_address');
            $table->decimal('pickup_longitude', 10, 7)->nullable()->after('pickup_latitude');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_return_pickups', function (Blueprint $table): void {
            $table->dropColumn(['pickup_latitude', 'pickup_longitude']);
        });
    }
};
