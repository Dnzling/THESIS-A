<?php

namespace App\Services\Inventory;

use Illuminate\Support\Facades\DB;

class ForecastPlanService
{
    public function key(int $storeId): string
    {
        return strtolower(trim((string) (DB::table('stores as s')
            ->leftJoin('subscription_plans as p', 'p.id', '=', 's.subscription_tier')
            ->where('s.id', $storeId)
            ->value('p.plan_key') ?? 'free')));
    }

    public function allowsForecast(int $storeId): bool
    {
        // `unlimited` was the legacy key for the highest tier before plans were renamed.
        return in_array($this->key($storeId), ['growth', 'enterprise', 'unlimited'], true);
    }

    public function allowsConsolidated(int $storeId): bool
    {
        return in_array($this->key($storeId), ['enterprise', 'unlimited'], true);
    }
}
