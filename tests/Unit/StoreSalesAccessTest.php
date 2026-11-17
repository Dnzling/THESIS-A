<?php

namespace Tests\Unit;

use App\Models\Store\Store;
use App\Services\Store\StoreSalesAccess;
use Carbon\Carbon;
use Tests\TestCase;

class StoreSalesAccessTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_trial_can_sell_only_before_expiration(): void
    {
        Carbon::setTestNow('2026-10-15 12:00:00');
        $service = new StoreSalesAccess();
        $store = new Store(['status' => 'active', 'subscription_status' => 'trial', 'trial_ends_at' => '2026-10-15 12:01:00']);

        $this->assertTrue($service->canAcceptNewSales($store));
        $store->trial_ends_at = '2026-10-15 12:00:00';
        $this->assertFalse($service->canAcceptNewSales($store));
    }

    public function test_paid_store_can_sell_in_grace_period_but_not_after(): void
    {
        Carbon::setTestNow('2026-10-15 12:00:00');
        $service = new StoreSalesAccess();
        $store = new Store(['status' => 'verified', 'subscription_status' => 'paid', 'subscription_ends_at' => '2026-10-08']);

        $this->assertTrue($service->canAcceptNewSales($store));
        $store->subscription_ends_at = '2026-10-07';
        $this->assertFalse($service->canAcceptNewSales($store));
    }

    public function test_inactive_and_expired_stores_cannot_start_sales(): void
    {
        Carbon::setTestNow('2026-10-15 12:00:00');
        $service = new StoreSalesAccess();
        $inactive = new Store(['status' => 'inactive', 'subscription_status' => 'paid', 'subscription_ends_at' => '2026-11-01']);
        $expired = new Store(['status' => 'active', 'subscription_status' => 'expired']);

        $this->assertFalse($service->canAcceptNewSales($inactive));
        $this->assertFalse($service->canAcceptNewSales($expired));
    }
}
