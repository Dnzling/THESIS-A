<?php

namespace App\Console\Commands;

use App\Services\Modules\ModuleAccessService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillStoreModules extends Command
{
    protected $signature = 'modules:backfill {--store=} {--legacy-json : Import legacy settings.enabled_modules as manual enables}';
    protected $description = 'Sync store_modules from pricing plans and optional legacy enabled_modules JSON';

    public function handle(ModuleAccessService $modules): int
    {
        $storeId = $this->option('store');
        $legacy = $this->option('legacy-json');

        $stores = DB::table('stores')
            ->when($storeId, fn($q) => $q->where('id', $storeId))
            ->select('id', 'settings')
            ->get();

        $count = 0;

        foreach ($stores as $store) {
            $modules->syncStoreModulesFromPlan((int) $store->id);

            if ($legacy && is_array($store->settings)) {
                $enabledModules = $store->settings['enabled_modules'] ?? [];
                if (is_array($enabledModules)) {
                    foreach ($enabledModules as $moduleKey) {
                        $moduleId = DB::table('modules')->where('key', $moduleKey)->value('id');
                        if (!$moduleId) {
                            continue;
                        }
                        DB::table('store_modules')->updateOrInsert(
                            ['store_id' => $store->id, 'module_id' => $moduleId],
                            [
                                'status' => 'enabled',
                                'source' => 'manual',
                                'enabled_at' => now(),
                            ]
                        );
                    }
                }
            }

            $count++;
        }

        $this->info("Backfilled modules for {$count} store(s).");
        return self::SUCCESS;
    }
}
