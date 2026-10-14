<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_return_pickups', function (Blueprint $table) {
            $table->foreignId('trip_id')->nullable()->after('return_id')
                ->constrained('logistics_delivery_trips')->nullOnDelete();
            $table->index(['trip_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('logistics_return_pickups', function (Blueprint $table) {
            $table->dropIndex(['trip_id', 'status']);
            $table->dropConstrainedForeignId('trip_id');
        });
    }
};
