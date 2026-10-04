<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE logistics_return_pickups MODIFY status ENUM('scheduled', 'ready_for_dispatch', 'assigned', 'picked_up', 'cancelled') NOT NULL DEFAULT 'ready_for_dispatch'");

        DB::table('logistics_return_pickups')
            ->where('status', 'scheduled')
            ->update(['status' => 'ready_for_dispatch']);
    }

    public function down(): void
    {
        DB::table('logistics_return_pickups')
            ->where('status', 'ready_for_dispatch')
            ->update(['status' => 'scheduled']);

        DB::statement("ALTER TABLE logistics_return_pickups MODIFY status ENUM('scheduled', 'assigned', 'picked_up', 'cancelled') NOT NULL DEFAULT 'scheduled'");
    }
};
