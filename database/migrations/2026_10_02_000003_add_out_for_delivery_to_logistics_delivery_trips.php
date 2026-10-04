<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_delivery_trips', function (Blueprint $table) {
            $table->enum('status', ['planned', 'in_transit', 'out_for_delivery', 'completed', 'cancelled'])
                ->default('planned')->change();
        });
    }

    public function down(): void
    {
        DB::table('logistics_delivery_trips')->where('status', 'out_for_delivery')->update(['status' => 'in_transit']);
        Schema::table('logistics_delivery_trips', function (Blueprint $table) {
            $table->enum('status', ['planned', 'in_transit', 'completed', 'cancelled'])
                ->default('planned')->change();
        });
    }
};
