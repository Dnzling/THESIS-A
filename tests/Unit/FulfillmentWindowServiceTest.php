<?php

namespace Tests\Unit;

use App\Models\Store\Store;
use App\Services\Ecommerce\FulfillmentWindowService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class FulfillmentWindowServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_store_days_and_lead_time_control_checkout_dates(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-19 10:00:00', 'Asia/Manila'));
        $store = new Store();
        $store->settings = [
            'fulfillment_windows' => [
                'pickup' => ['days' => ['wednesday'], 'start' => '10:00', 'end' => '17:00', 'lead_days' => 2],
            ],
        ];

        $service = new FulfillmentWindowService();
        $availability = $service->availability($store, 'pickup');

        $this->assertSame('2026-10-21', $availability['dates'][0]);
        $this->assertSame('10:00', $availability['window']['start']);
        $this->assertTrue($service->isAvailable($store, 'pickup', '2026-10-21'));
        $this->assertFalse($service->isAvailable($store, 'pickup', '2026-10-20'));
    }

    public function test_same_day_closes_when_window_has_ended(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-19 18:30:00', 'Asia/Manila'));
        $store = new Store();
        $store->settings = ['fulfillment_windows' => [
            'pickup' => ['days' => ['monday', 'tuesday'], 'start' => '09:00', 'end' => '18:00', 'lead_days' => 0],
        ]];

        $this->assertFalse((new FulfillmentWindowService())->isAvailable($store, 'pickup', '2026-10-19'));
    }
}
