<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('branches', 'logo_dimensions')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->json('logo_dimensions')->nullable()->after('logo_path');
            });
        }

        if (Schema::hasColumn('branches', 'geofence_enabled')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->boolean('geofence_enabled')->default(false)->change();
            });
        }

        $hasBranchLogo = Schema::hasColumn('branches', 'logo_path');
        $hasStoreSettings = Schema::hasColumn('stores', 'settings');
        $hasStoreLatitude = Schema::hasColumn('stores', 'latitude');
        $hasStoreLongitude = Schema::hasColumn('stores', 'longitude');

        if (!$hasStoreSettings && !$hasStoreLatitude && !$hasStoreLongitude) {
            return;
        }

        $storeColumns = ['id'];
        if ($hasStoreSettings) $storeColumns[] = 'settings';
        if ($hasStoreLatitude) $storeColumns[] = 'latitude';
        if ($hasStoreLongitude) $storeColumns[] = 'longitude';

        foreach (DB::table('stores')->select($storeColumns)->orderBy('id')->cursor() as $store) {
            $branch = DB::table('branches')
                ->where('store_id', $store->id)
                ->orderByDesc('is_main_branch')
                ->orderBy('id')
                ->first();

            if (!$branch) continue;

            $settings = $hasStoreSettings
                ? (is_array($store->settings) ? $store->settings : (json_decode((string) $store->settings, true) ?: []))
                : [];
            $branchUpdates = [];

            if ($hasBranchLogo) {
                $legacyLogo = $settings['logo_path'] ?? $settings['logo'] ?? null;
                if (empty($branch->logo_path) && $legacyLogo) {
                    $branchUpdates['logo_path'] = $legacyLogo;
                }
                if (Schema::hasColumn('branches', 'logo_dimensions') && empty($branch->logo_dimensions) && !empty($settings['logo_dimensions'])) {
                    $branchUpdates['logo_dimensions'] = json_encode($settings['logo_dimensions']);
                }
            }

            if ($hasStoreLatitude && $hasStoreLongitude) {
                if ($branch->latitude === null && $store->latitude !== null) $branchUpdates['latitude'] = $store->latitude;
                if ($branch->longitude === null && $store->longitude !== null) $branchUpdates['longitude'] = $store->longitude;
            }

            if (array_key_exists('attendance_geofence_enabled', $settings) && Schema::hasColumn('branches', 'geofence_enabled')) {
                $branchUpdates['geofence_enabled'] = (bool) $settings['attendance_geofence_enabled'];
                DB::table('branches')
                    ->where('store_id', $store->id)
                    ->update(['geofence_enabled' => (bool) $settings['attendance_geofence_enabled']]);
            }

            if ($branchUpdates) {
                DB::table('branches')->where('id', $branch->id)->update($branchUpdates);
            }

            if ($hasStoreSettings) {
                if ($hasBranchLogo) {
                    unset($settings['logo'], $settings['logo_path'], $settings['logo_dimensions']);
                }
                if (Schema::hasColumn('branches', 'geofence_enabled')) {
                    unset($settings['attendance_geofence_enabled']);
                }
                DB::table('stores')->where('id', $store->id)->update(['settings' => json_encode($settings)]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('branches', 'logo_dimensions') || Schema::hasColumn('branches', 'logo_path')) {
            $mainBranches = DB::table('branches')
                ->orderByDesc('is_main_branch')
                ->orderBy('id')
                ->get()
                ->groupBy('store_id')
                ->map(fn ($branches) => $branches->first());

            foreach ($mainBranches as $branch) {
                $settings = DB::table('stores')->where('id', $branch->store_id)->value('settings');
                $settings = is_array($settings) ? $settings : (json_decode((string) $settings, true) ?: []);
                if (Schema::hasColumn('branches', 'logo_path') && $branch->logo_path) $settings['logo_path'] = $branch->logo_path;
                if (Schema::hasColumn('branches', 'logo_dimensions') && $branch->logo_dimensions) {
                    $settings['logo_dimensions'] = is_array($branch->logo_dimensions)
                        ? $branch->logo_dimensions
                        : (json_decode((string) $branch->logo_dimensions, true) ?: []);
                }
                if (Schema::hasColumn('branches', 'geofence_enabled')) {
                    $settings['attendance_geofence_enabled'] = (bool) $branch->geofence_enabled;
                }
                DB::table('stores')->where('id', $branch->store_id)->update(['settings' => json_encode($settings)]);
            }
        }

        if (Schema::hasColumn('branches', 'logo_dimensions')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropColumn('logo_dimensions');
            });
        }

        if (Schema::hasColumn('branches', 'geofence_enabled')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->boolean('geofence_enabled')->default(true)->change();
            });
        }
    }
};
