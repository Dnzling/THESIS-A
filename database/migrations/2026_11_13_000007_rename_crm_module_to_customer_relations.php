<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('modules')
            ->where('key', 'crm')
            ->update(['name' => 'Customer Relations', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('modules')
            ->where('key', 'crm')
            ->update(['name' => 'Crm', 'updated_at' => now()]);
    }
};
