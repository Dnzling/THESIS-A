<?php

namespace App\Http\Controllers\Api\ProductCatalog;

use App\Http\Controllers\Controller;
use App\Models\Store\StoreDeliveryFeeSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryFeeSettingController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $setting = $this->resolveSetting($request, true);

        return response()->json([
            'success' => true,
            'data' => $this->formatSetting($setting),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
            'base_fee' => 'required|numeric|min:0',
            'per_km_fee' => 'required|numeric|min:0',
            'min_delivery_fee' => 'required|numeric|min:0',
            'free_shipping_min_order' => 'nullable|numeric|min:0',
            'bulky_item_surcharge' => 'required|numeric|min:0',
            'remote_area_surcharge' => 'required|numeric|min:0',
            'max_delivery_distance_km' => 'nullable|numeric|min:0',
            'bulk_discount_rate' => 'nullable|numeric|min:5|max:25',
            'notes' => 'nullable|string|max:2000',
        ]);

        $setting = $this->resolveSetting($request, true);
        $setting->fill($validated);
        $setting->updated_by = $request->user()->id;
        if (!$setting->exists) {
            $setting->created_by = $request->user()->id;
        }
        $setting->save();

        return response()->json([
            'success' => true,
            'message' => 'Delivery fee settings updated.',
            'data' => $this->formatSetting($setting),
        ]);
    }

    public function estimate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subtotal' => 'required|numeric|min:0',
            'distance_km' => 'nullable|numeric|min:0',
            'has_bulky_items' => 'nullable|boolean',
            'is_remote_area' => 'nullable|boolean',
        ]);

        $setting = $this->resolveSetting($request, true);
        $result = $this->computeFee(
            $setting,
            (float) $validated['subtotal'],
            (float) ($validated['distance_km'] ?? 0),
            (bool) ($validated['has_bulky_items'] ?? false),
            (bool) ($validated['is_remote_area'] ?? false)
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    private function resolveSetting(Request $request, bool $createIfMissing = false): StoreDeliveryFeeSetting
    {
        $user = $request->user();
        $storeId = (int) ($user->store_id ?? 0);
        if (!$storeId && !$user->hasRole('super_admin')) {
            abort(response()->json(['success' => false, 'message' => 'No store assigned.'], 422));
        }

        if ($user->hasRole('super_admin') && $request->filled('store_id')) {
            $storeId = (int) $request->input('store_id');
        }

        $setting = StoreDeliveryFeeSetting::query()->where('store_id', $storeId)->first();
        if ($setting) {
            return $setting;
        }

        if (!$createIfMissing) {
            abort(404);
        }

        return new StoreDeliveryFeeSetting([
            'store_id' => $storeId,
            'is_active' => true,
            'base_fee' => 100,
            'per_km_fee' => 10,
            'min_delivery_fee' => 80,
            'free_shipping_min_order' => null,
            'bulky_item_surcharge' => 0,
            'remote_area_surcharge' => 0,
            'max_delivery_distance_km' => null,
            'bulk_discount_rate' => 10,
        ]);
    }

    private function formatSetting(StoreDeliveryFeeSetting $setting): array
    {
        return [
            'store_id' => (int) $setting->store_id,
            'is_active' => (bool) $setting->is_active,
            'base_fee' => (float) $setting->base_fee,
            'per_km_fee' => (float) $setting->per_km_fee,
            'min_delivery_fee' => (float) $setting->min_delivery_fee,
            'free_shipping_min_order' => is_null($setting->free_shipping_min_order) ? null : (float) $setting->free_shipping_min_order,
            'bulky_item_surcharge' => (float) $setting->bulky_item_surcharge,
            'remote_area_surcharge' => (float) $setting->remote_area_surcharge,
            'max_delivery_distance_km' => is_null($setting->max_delivery_distance_km) ? null : (float) $setting->max_delivery_distance_km,
            'bulk_discount_rate' => is_null($setting->bulk_discount_rate) ? null : (float) $setting->bulk_discount_rate,
            'notes' => $setting->notes,
        ];
    }

    private function computeFee(StoreDeliveryFeeSetting $setting, float $subtotal, float $distanceKm, bool $hasBulkyItems, bool $isRemoteArea): array
    {
        if (!(bool) $setting->is_active) {
            return [
                'shipping_fee' => 0.0,
                'breakdown' => [
                    'base_fee' => 0.0,
                    'distance_fee' => 0.0,
                    'bulky_item_surcharge' => 0.0,
                    'remote_area_surcharge' => 0.0,
                    'minimum_applied' => false,
                    'free_shipping_applied' => false,
                    'distance_km' => $distanceKm,
                ],
            ];
        }

        $freeThreshold = $setting->free_shipping_min_order;
        if (!is_null($freeThreshold) && $subtotal >= (float) $freeThreshold) {
            return [
                'shipping_fee' => 0.0,
                'breakdown' => [
                    'base_fee' => (float) $setting->base_fee,
                    'distance_fee' => $distanceKm * (float) $setting->per_km_fee,
                    'bulky_item_surcharge' => $hasBulkyItems ? (float) $setting->bulky_item_surcharge : 0.0,
                    'remote_area_surcharge' => $isRemoteArea ? (float) $setting->remote_area_surcharge : 0.0,
                    'minimum_applied' => false,
                    'free_shipping_applied' => true,
                    'distance_km' => $distanceKm,
                ],
            ];
        }

        $base = (float) $setting->base_fee;
        $distanceFee = $distanceKm * (float) $setting->per_km_fee;
        $bulky = $hasBulkyItems ? (float) $setting->bulky_item_surcharge : 0.0;
        $remote = $isRemoteArea ? (float) $setting->remote_area_surcharge : 0.0;
        $raw = $base + $distanceFee + $bulky + $remote;
        $min = (float) $setting->min_delivery_fee;
        $applied = max($raw, $min);

        return [
            'shipping_fee' => round($applied, 2),
            'breakdown' => [
                'base_fee' => round($base, 2),
                'distance_fee' => round($distanceFee, 2),
                'bulky_item_surcharge' => round($bulky, 2),
                'remote_area_surcharge' => round($remote, 2),
                'minimum_applied' => $applied > $raw,
                'free_shipping_applied' => false,
                'distance_km' => round($distanceKm, 2),
            ],
        ];
    }
}

