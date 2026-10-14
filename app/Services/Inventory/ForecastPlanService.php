<?php

namespace App\Services\Inventory;

use Illuminate\Support\Facades\DB;

class ForecastPlanService
{
    public function key(int $storeId): string
    {
        return (string) (DB::table('stores as s')
            ->leftJoin('subscription_plans as p', 'p.id', '=', 's.subscription_tier')
            ->where('s.store_id', $storeId)
            ->value('p.plan_key') ?? 'free');
    }

    public function allowsForecast(int $storeId): bool
    {
        return in_array($this->key($storeId), ['growth', 'enterprise'], true);
    }

    public function allowsConsolidated(int $storeId): bool
    {
        return $this->key($storeId) === 'enterprise';
    }
}
