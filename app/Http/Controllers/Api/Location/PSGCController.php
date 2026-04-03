<?php

namespace App\Http\Controllers\Api\Location;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PSGCController extends Controller
{
    private function fetchJson(string $url, array $query = []): array
    {
        $response = Http::get($url, $query);
        $data = $response->json();
        return is_array($data) ? $data : [];
    }

    private function prefixFromCode(?string $code): string
    {
        if (!$code) return '';
        return rtrim($code, '0');
    }

    public function provinces(Request $request): JsonResponse
    {
        try {
            $provinces = $this->fetchJson('https://psgc.rootscratch.com/province');

            return response()->json([
                'success' => true,
                'data' => $provinces,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load provinces.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function cities(Request $request): JsonResponse
    {
        $provinceId = $request->query('province_id');
        if (!$provinceId) {
            return response()->json([
                'success' => false,
                'message' => 'province_id is required.',
            ], 422);
        }

        try {
            $cities = $this->fetchJson('https://psgc.rootscratch.com/municipal-city');
            $provinceData = $this->fetchJson('https://psgc.rootscratch.com/province', [
                'id' => $provinceId,
            ]);

            $province = $provinceData[0] ?? null;
            $codePrefix = $this->prefixFromCode($province['correspondence_code'] ?? null);

            $filtered = array_values(array_filter($cities, function ($city) use ($codePrefix) {
                if (!isset($city['correspondence_code'])) return false;
                return $codePrefix === '' || str_starts_with((string) $city['correspondence_code'], $codePrefix);
            }));

            return response()->json([
                'success' => true,
                'data' => $filtered,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load cities/municipalities.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function barangays(Request $request): JsonResponse
    {
        $cityId = $request->query('city_id');
        if (!$cityId) {
            return response()->json([
                'success' => false,
                'message' => 'city_id is required.',
            ], 422);
        }

        try {
            $barangays = $this->fetchJson('https://psgc.rootscratch.com/barangay');
            $cityData = $this->fetchJson('https://psgc.rootscratch.com/municipal-city', [
                'id' => $cityId,
            ]);

            $city = $cityData[0] ?? null;
            $codePrefix = $this->prefixFromCode($city['correspondence_code'] ?? null);

            $filtered = array_values(array_filter($barangays, function ($barangay) use ($codePrefix) {
                if (!isset($barangay['correspondence_code'])) return false;
                return $codePrefix === '' || str_starts_with((string) $barangay['correspondence_code'], $codePrefix);
            }));

            return response()->json([
                'success' => true,
                'data' => $filtered,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load barangays.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
