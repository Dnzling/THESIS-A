<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('navigation_items')
            ->where('name', 'store.expenses')
            ->update(['module' => 'admin', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('navigation_items')
            ->where('name', 'store.expenses')
            ->update(['module' => 'store', 'updated_at' => now()]);
    }
};
