<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ModulesFromPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = DB::table('permissions')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereNotNull('module')
            ->select('id', 'name', 'module', 'description')
            ->get();

        if ($permissions->isEmpty()) {
            return;
        }

        // Seed modules table from distinct module values
        $modules = $permissions->pluck('module')->unique()->filter()->values();
        foreach ($modules as $moduleKey) {
            DB::table('modules')->updateOrInsert(
                ['key' => $moduleKey],
                [
                    'name' => Str::headline($moduleKey),
                    'description' => "Module auto-seeded from permissions: {$moduleKey}",
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        // Map permission -> module_permissions
        foreach ($permissions as $perm) {
            $moduleId = DB::table('modules')->where('key', $perm->module)->value('id');
            if (!$moduleId) {
                continue;
            }

            DB::table('module_permissions')->updateOrInsert(
                [
                    'module_id' => $moduleId,
                    'permission_key' => $perm->name,
                ],
                [
                    'description' => $perm->description,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
