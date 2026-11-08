<?php

namespace Tests\Unit;

use App\Services\Logistics\DistanceService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReturnPickupDistanceEstimateTest extends TestCase
{
    public function test_it_calculates_driving_distance_between_the_customer_pin_and_branch_pin(): void
    {
        config()->set('services.mapbox.access_token', 'test-token');
        Http::fake([
            'api.mapbox.com/directions/v5/mapbox/driving/*' => Http::response([
                'routes' => [['distance' => 12345]],
            ]),
        ]);

        $distance = app(DistanceService::class)->getDistanceKmBetweenCoordinates(
            14.3,
            120.9,
            14.4,
            121.0,
        );

        $this->assertSame(12.35, $distance);
        Http::assertSent(fn ($request) => str_contains($request->url(), '120.9,14.3;121,14.4'));
    }
}
