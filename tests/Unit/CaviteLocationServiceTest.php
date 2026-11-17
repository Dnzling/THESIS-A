<?php

namespace Tests\Unit;

use App\Services\Store\CaviteLocationService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CaviteLocationServiceTest extends TestCase
{
    public function test_it_accepts_a_pin_in_cavite(): void
    {
        config()->set('services.mapbox.access_token', 'test-token');
        Http::fake(['api.mapbox.com/*' => Http::response([
            'features' => [
                ['properties' => ['feature_type' => 'district', 'name' => 'Cavite']],
                ['properties' => ['feature_type' => 'region', 'name' => 'Calabarzon']],
            ],
        ])]);

        $this->assertTrue(app(CaviteLocationService::class)->contains(14.3294, 120.9367));
    }

    public function test_it_rejects_a_pin_outside_cavite_even_if_a_place_name_mentions_cavite(): void
    {
        config()->set('services.mapbox.access_token', 'test-token');
        Http::fake(['api.mapbox.com/*' => Http::response([
            'features' => [
                ['properties' => ['feature_type' => 'place', 'name' => 'Cavite']],
                ['properties' => ['feature_type' => 'district', 'name' => 'Laguna']],
            ],
        ])]);

        $this->assertFalse(app(CaviteLocationService::class)->contains(14.2, 121.2));
    }
}
