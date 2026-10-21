<?php

namespace App\Services\Logistics;

use Illuminate\Support\Facades\Http;

class DistanceService
{
    public function getDistanceKm(string $origin, string $destination): float
    {
        $originCoords = $this->geocode($origin);
        $destCoords = $this->geocode($destination);

        return $this->routeDistanceKm($originCoords['lat'], $originCoords['lon'], $destCoords['lat'], $destCoords['lon']);
    }

    public function getDistanceKmToCoordinates(string $origin, float $destinationLatitude, float $destinationLongitude): float
    {
        $originCoords = $this->geocode($origin);

        return $this->routeDistanceKm(
            $originCoords['lat'],
            $originCoords['lon'],
            $destinationLatitude,
            $destinationLongitude,
        );
    }

    protected function geocode(string $query): array
    {
        $token = $this->accessToken();
        $response = Http::get(
            'https://api.mapbox.com/search/geocode/v6/forward',
            ['q' => $query, 'limit' => 1, 'country' => 'PH', 'access_token' => $token],
        );

        if (!$response->ok()) {
            throw new \RuntimeException('Failed to geocode address.');
        }

        $coordinates = $response->json('features.0.geometry.coordinates');
        if (! is_array($coordinates) || count($coordinates) < 2) {
            throw new \RuntimeException('Unable to resolve address to coordinates.');
        }

        return [
            'lat' => (float) $coordinates[1],
            'lon' => (float) $coordinates[0],
        ];
    }

    protected function routeDistanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $url = sprintf(
            'https://api.mapbox.com/directions/v5/mapbox/driving/%s,%s;%s,%s',
            $lon1,
            $lat1,
            $lon2,
            $lat2
        );

        $response = Http::get($url, [
            'overview' => 'false',
            'access_token' => $this->accessToken(),
        ]);

        if (!$response->ok()) {
            throw new \RuntimeException('Failed to fetch route distance.');
        }

        $data = $response->json();
        if (!isset($data['routes'][0]['distance'])) {
            throw new \RuntimeException('Distance not available for route.');
        }

        return round(((float) $data['routes'][0]['distance']) / 1000, 2);
    }

    protected function accessToken(): string
    {
        $token = (string) config('services.mapbox.access_token');

        if ($token === '') {
            throw new \RuntimeException('Mapbox is not configured. Set MAPBOX_ACCESS_TOKEN.');
        }

        return $token;
    }
}
