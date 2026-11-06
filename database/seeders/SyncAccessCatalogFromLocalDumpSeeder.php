<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SyncAccessCatalogFromLocalDumpSeeder extends Seeder
{
    private const SNAPSHOT_PATH = __DIR__ . '/data/access_catalog_local_20261028.json';

    /**
     * Align permissions, navigation, and plan entitlements to the local dump.
     * Does not modify users, roles, subscriptions, or plan details.
     */
    public function run(): void
    {
        $snapshot = json_decode(file_get_contents(self::SNAPSHOT_PATH), true, flags: JSON_THROW_ON_ERROR);
        $now = Carbon::now();

        DB::transaction(function () use ($snapshot, $now): void {
            $permissionIds = $this->syncPermissions($snapshot['permissions'], $now);
            $navigationIds = $this->syncNavigation($snapshot['navigation'], $now);

            $this->syncNavigationPermissions(
                $snapshot['navigation_permissions'],
                $navigationIds,
                $permissionIds,
                $now,
            );

            $this->retirePermissions($snapshot['retired_permissions'], $now);
            $this->retireNavigationItems($snapshot['retired_navigation_items'], $now);
            $this->syncPlanPermissions($snapshot['plan_permissions'], $permissionIds, $now);
        });

        $this->command?->info(sprintf(
            'Access catalog synced: %d permissions, %d navigation items, %d navigation links, and %d plan-permission settings.',
            count($snapshot['permissions']),
            count($snapshot['navigation']),
            count($snapshot['navigation_permissions']),
            count($snapshot['plan_permissions']),
        ));
    }

    private function syncPermissions(array $permissions, Carbon $now): array
    {
        $ids = [];

        foreach ($permissions as $permission) {
            $values = [
                'display_name' => $permission['display_name'],
                'module' => $permission['module'],
                'description' => $permission['description'],
                'is_active' => (bool) $permission['is_active'],
                'deleted_at' => $permission['deleted_at'],
                'updated_at' => $now,
            ];

            $this->upsertPreservingCreatedAt('permissions', ['name' => $permission['name']], $values, $now);

            $ids[$permission['name']] = (int) DB::table('permissions')
                ->where('name', $permission['name'])
                ->value('id');
        }

        return $ids;
    }

    private function syncNavigation(array $items, Carbon $now): array
    {
        $ids = [];

        foreach ($items as $item) {
            $values = [
                'display_name' => $item['display_name'],
                'module' => $item['module'],
                'section' => $item['section'],
                'route_name' => $item['route_name'],
                'route_path' => $item['route_path'],
                'icon' => $item['icon'],
                'parent_id' => null,
                'display_order' => $item['display_order'],
                'is_active' => (bool) $item['is_active'],
                'meta' => $item['meta'],
                'deleted_at' => $item['deleted_at'],
                'updated_at' => $now,
            ];

            $this->upsertPreservingCreatedAt('navigation_items', ['name' => $item['name']], $values, $now);

            $ids[$item['name']] = (int) DB::table('navigation_items')
                ->where('name', $item['name'])
                ->value('id');
        }

        foreach ($items as $item) {
            $parentName = $item['parent_name'];
            if ($parentName !== null && !isset($ids[$parentName])) {
                throw new RuntimeException("Navigation parent '{$parentName}' is missing from the snapshot.");
            }

            DB::table('navigation_items')
                ->where('id', $ids[$item['name']])
                ->update(['parent_id' => $parentName === null ? null : $ids[$parentName]]);
        }

        return $ids;
    }

    private function syncNavigationPermissions(array $links, array $navigationIds, array $permissionIds, Carbon $now): void
    {
        DB::table('navigation_permissions')
            ->whereIn('navigation_item_id', array_values($navigationIds))
            ->delete();

        foreach ($links as $link) {
            $navigationId = $navigationIds[$link['navigation_name']] ?? null;
            $permissionId = $permissionIds[$link['permission_name']] ?? null;
            if ($navigationId === null || $permissionId === null) {
                throw new RuntimeException('A navigation permission references a missing catalog record.');
            }

            DB::table('navigation_permissions')->insert([
                'navigation_item_id' => $navigationId,
                'permission_id' => $permissionId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function retirePermissions(array $names, Carbon $now): void
    {
        if ($names === []) {
            return;
        }

        DB::table('permissions')
            ->whereIn('name', $names)
            ->update(['is_active' => false, 'deleted_at' => $now, 'updated_at' => $now]);
    }

    private function retireNavigationItems(array $names, Carbon $now): void
    {
        if ($names === []) {
            return;
        }

        $ids = DB::table('navigation_items')->whereIn('name', $names)->pluck('id')->all();
        if ($ids !== []) {
            DB::table('navigation_permissions')->whereIn('navigation_item_id', $ids)->delete();
            DB::table('navigation_items')->whereIn('id', $ids)->update([
                'is_active' => false,
                'deleted_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function syncPlanPermissions(array $settings, array $permissionIds, Carbon $now): void
    {
        $planAliases = ['growth' => 'business'];
        $planIds = [];

        foreach (array_unique(array_column($settings, 'plan_key')) as $snapshotKey) {
            $databaseKey = $planAliases[$snapshotKey] ?? $snapshotKey;
            $planId = DB::table('subscription_plans')->where('plan_key', $databaseKey)->value('id');
            if ($planId === null) {
                throw new RuntimeException("Subscription plan '{$databaseKey}' is missing; no plan entitlements were synced.");
            }

            $planIds[$snapshotKey] = (int) $planId;
            DB::table('plan_permissions')->where('plan_id', $planId)->update([
                'included' => false,
                'updated_at' => $now,
            ]);
        }

        foreach ($settings as $setting) {
            $planId = $planIds[$setting['plan_key']] ?? null;
            $permissionId = $permissionIds[$setting['permission_name']] ?? null;
            if ($planId === null || $permissionId === null) {
                throw new RuntimeException('A plan permission references a missing plan or permission.');
            }

            $this->upsertPreservingCreatedAt(
                'plan_permissions',
                ['plan_id' => $planId, 'permission_id' => $permissionId],
                ['included' => (bool) $setting['included'], 'updated_at' => $now],
                $now,
            );
        }
    }

    private function upsertPreservingCreatedAt(string $table, array $keys, array $values, Carbon $now): void
    {
        $query = DB::table($table);
        foreach ($keys as $column => $value) {
            $query->where($column, $value);
        }

        if ($query->exists()) {
            $query->update($values);
            return;
        }

        DB::table($table)->insert($keys + $values + ['created_at' => $now]);
    }
}
