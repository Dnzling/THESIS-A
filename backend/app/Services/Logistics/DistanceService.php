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

    protected function geocode(string $query): array
    {
        $response = Http::withHeaders([
            'User-Agent' => config('app.name', 'IMS') . ' DistanceService',
        ])->get('https://nominatim.openstreetmap.org/search', [
            'q' => $query,
            'format' => 'json',
            'limit' => 1,
        ]);

        if (!$response->ok()) {
            throw new \RuntimeException('Failed to geocode address.');
        }

        $data = $response->json();
        if (!$data || !isset($data[0]['lat'], $data[0]['lon'])) {
            throw new \RuntimeException('Unable to resolve address to coordinates.');
        }

        return [
            'lat' => (float) $data[0]['lat'],
            'lon' => (float) $data[0]['lon'],
        ];
    }

    protected function routeDistanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $url = sprintf(
            'https://router.project-osrm.org/route/v1/driving/%s,%s;%s,%s',
            $lon1,
            $lat1,
            $lon2,
            $lat2
        );

        $response = Http::get($url, [
            'overview' => 'false',
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
}
