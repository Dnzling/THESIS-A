<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        DB::transaction(function (): void {
            $legacyRoles = DB::table('roles')->where('name', 'store_admin')->get();

            foreach ($legacyRoles as $legacyRole) {
                $ownerRole = DB::table('roles')
                    ->where('name', 'owner')
                    ->when(Schema::hasColumn('roles', 'store_id'), fn ($query) => $query->where('store_id', $legacyRole->store_id))
                    ->first();

                if (!$ownerRole) {
                    DB::table('roles')->where('id', $legacyRole->id)->update([
                        'name' => 'owner',
                        'display_name' => 'Store Owner',
                        'description' => 'Owns and manages store configuration and operations',
                        'code' => 'OWN',
                        'updated_at' => now(),
                    ]);
                    continue;
                }

                $ownerRoleId = (int) $ownerRole->id;
                $legacyRoleId = (int) $legacyRole->id;

                if (Schema::hasTable('role_permissions')) {
                    $permissionIds = DB::table('role_permissions')
                        ->where('role_id', $legacyRoleId)
                        ->pluck('permission_id');
                    $existingPermissionIds = DB::table('role_permissions')
                        ->where('role_id', $ownerRoleId)
                        ->whereIn('permission_id', $permissionIds)
                        ->pluck('permission_id')
                        ->all();
                    $now = now();
                    $permissions = $permissionIds
                        ->diff($existingPermissionIds)
                        ->map(fn ($permissionId) => [
                            'role_id' => $ownerRoleId,
                            'permission_id' => $permissionId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])
                        ->all();
                    foreach (array_chunk($permissions, 500) as $permissionChunk) {
                        DB::table('role_permissions')->insert($permissionChunk);
                    }
                }

                if (Schema::hasTable('users') && Schema::hasColumn('users', 'role_id')) {
                    DB::table('users')->where('role_id', $legacyRoleId)->update(['role_id' => $ownerRoleId]);
                }
                if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'role_id')) {
                    DB::table('employees')->where('role_id', $legacyRoleId)->update(['role_id' => $ownerRoleId]);
                }
                if (Schema::hasTable('job_postings') && Schema::hasColumn('job_postings', 'role_id')) {
                    DB::table('job_postings')->where('role_id', $legacyRoleId)->update(['role_id' => $ownerRoleId]);
                }

                if (Schema::hasTable('department_roles')) {
                    $departmentIds = DB::table('department_roles')
                        ->where('role_id', $legacyRoleId)
                        ->pluck('department_id');
                    foreach ($departmentIds as $departmentId) {
                        $alreadyLinked = DB::table('department_roles')
                            ->where('department_id', $departmentId)
                            ->where('role_id', $ownerRoleId)
                            ->exists();
                        if (!$alreadyLinked) {
                            DB::table('department_roles')
                                ->where('department_id', $departmentId)
                                ->where('role_id', $legacyRoleId)
                                ->update(['role_id' => $ownerRoleId]);
                        }
                    }
                }

                if (Schema::hasTable('role_approval_limits')) {
                    $legacyLimits = DB::table('role_approval_limits')->where('role_id', $legacyRoleId)->get();
                    foreach ($legacyLimits as $limit) {
                        $existingLimit = DB::table('role_approval_limits')
                            ->where('store_id', $limit->store_id)
                            ->where('role_id', $ownerRoleId)
                            ->exists();
                        if (!$existingLimit) {
                            DB::table('role_approval_limits')->where('id', $limit->id)->update(['role_id' => $ownerRoleId]);
                        }
                    }
                }

                DB::table('roles')->where('id', $legacyRoleId)->delete();
            }
        });
    }

    public function down(): void
    {
        // Role merges are intentionally irreversible; restoring the legacy name
        // could split permissions and users from the canonical owner role.
    }
};
