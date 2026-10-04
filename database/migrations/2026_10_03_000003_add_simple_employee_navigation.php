<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('navigation_items')->where('name', 'store.employees')->exists()) {
            return;
        }

        DB::table('navigation_items')->insert([
            'name' => 'store.employees',
            'display_name' => 'Employees',
            'module' => 'store',
            'section' => 'settings',
            'route_name' => 'store.employees',
            'route_path' => '/store/employees',
            'icon' => 'pi pi-users',
            'display_order' => 19,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Keep navigation records during rollback; a store may have customized them.
    }
};
