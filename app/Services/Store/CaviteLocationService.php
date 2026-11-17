<?php

namespace App\Services\Store;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class CaviteLocationService
{
    public function contains(float $latitude, float $longitude): bool
    {
        $token = (string) config('services.mapbox.access_token');
        if ($token === '') {
            throw new RuntimeException('Store location verification is not configured. Please contact support.');
        }

        try {
            $response = Http::timeout(10)->get('https://api.mapbox.com/search/geocode/v6/reverse', [
                'longitude' => $longitude,
                'latitude' => $latitude,
                'types' => 'region,district',
                'country' => 'PH',
                'language' => 'en',
                'access_token' => $token,
            ]);
        } catch (\Throwable $exception) {
            throw new RuntimeException('Store location could not be verified right now. Please try again.', 0, $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException('Store location could not be verified right now. Please try again.');
        }

        foreach ((array) $response->json('features', []) as $feature) {
            $type = data_get($feature, 'properties.feature_type');
            $name = trim((string) data_get($feature, 'properties.name', ''));
            if (in_array($type, ['region', 'district'], true) && preg_match('/^(province of )?cavite( province)?$/i', $name)) {
                return true;
            }
        }

        return false;
    }
}
