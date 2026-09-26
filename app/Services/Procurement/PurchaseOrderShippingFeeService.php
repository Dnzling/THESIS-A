<?php

namespace App\Services\Procurement;

use App\Models\Procurement\Supplier\Supplier;
use App\Models\ProductCatalog\Product;
use App\Models\Store\Branch;
use App\Models\Store\StoreDeliveryFeeSetting;
use App\Services\Logistics\DistanceService;
use Illuminate\Validation\ValidationException;

class PurchaseOrderShippingFeeService
{
    public function estimate(int $storeId, Supplier $supplier, Branch $branch, array $items, float $subtotal): array
    {
        $supplierProvince = $supplier->province ?: ($supplier->state ?? null);
        $supplierAddress = $this->address([$supplier->address, $supplier->barangay ?? null, $supplier->city, $supplierProvince, $supplier->postal_code, $supplier->country ?: 'Philippines']);
        $branchAddress = $this->address([$branch->address, $branch->barangay, $branch->city, $branch->province]);
        if (!$supplierAddress || !$branchAddress) {
            throw ValidationException::withMessages(['shipping_fee' => 'Supplier and destination branch addresses are required to calculate the shipping fee.']);
        }

        try {
            $distanceService = app(DistanceService::class);
            $hasBranchCoordinates = is_numeric($branch->latitude) && is_numeric($branch->longitude);
            $supplierAddressCandidates = array_values(array_unique(array_filter([
                $supplierAddress,
                $this->address([$supplier->address, $supplier->city, $supplierProvince, 'Philippines']),
                $this->address([$supplier->city, $supplierProvince, 'Philippines']),
            ])));

            $distanceKm = $this->resolveDistance(
                $distanceService,
                $supplierAddressCandidates,
                $branchAddress,
                $hasBranchCoordinates ? (float) $branch->latitude : null,
                $hasBranchCoordinates ? (float) $branch->longitude : null,
            );
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages(['shipping_fee' => 'Unable to locate the supplier address for road-distance calculation. Update the supplier address with a complete street, city, and province.']);
        }

        $products = Product::query()->whereIn('id', collect($items)->pluck('product_id')->filter()->unique())->get()->keyBy('id');
        $totalWeight = 0.0;
        $hasBulkyItems = false;
        foreach ($items as $item) {
            $product = $products->get((int) ($item['product_id'] ?? 0));
            $quantity = max(0, (float) ($item['quantity_ordered'] ?? 0));
            if (!$product) continue;
            $weight = (float) ($product->weight_kg ?? 0);
            $totalWeight += $weight * $quantity;
            $largestDimension = max((float) ($product->length_cm ?? 0), (float) ($product->width_cm ?? 0), (float) ($product->height_cm ?? 0));
            $hasBulkyItems = $hasBulkyItems || $weight >= 25 || $largestDimension >= 150;
        }

        $setting = StoreDeliveryFeeSetting::query()->where('store_id', $storeId)->first()
            ?? new StoreDeliveryFeeSetting(['is_active' => true, 'base_fee' => 100, 'per_km_fee' => 10, 'per_kg_fee' => 0, 'min_delivery_fee' => 80, 'bulky_item_surcharge' => 0, 'remote_area_surcharge' => 0]);
        if (!$setting->is_active) return ['shipping_fee' => 0.0, 'breakdown' => ['distance_km' => $distanceKm, 'total_weight_kg' => $totalWeight, 'disabled' => true]];
        if ($setting->max_delivery_distance_km !== null && $distanceKm > (float) $setting->max_delivery_distance_km) {
            throw ValidationException::withMessages(['shipping_fee' => 'The supplier is outside the store\'s maximum configured delivery distance.']);
        }

        $base = (float) $setting->base_fee;
        $distanceFee = $distanceKm * (float) $setting->per_km_fee;
        $weightFee = $totalWeight * (float) $setting->per_kg_fee;
        $bulkyFee = $hasBulkyItems ? (float) $setting->bulky_item_surcharge : 0.0;
        $remoteFee = 0.0;
        $raw = $base + $distanceFee + $weightFee + $bulkyFee + $remoteFee;
        $free = $setting->free_shipping_min_order !== null && $subtotal >= (float) $setting->free_shipping_min_order;
        $shippingFee = $free ? 0.0 : max($raw, (float) $setting->min_delivery_fee);
        return ['shipping_fee' => round($shippingFee, 2), 'breakdown' => ['distance_km' => round($distanceKm, 2), 'total_weight_kg' => round($totalWeight, 2), 'base_fee' => round($base, 2), 'distance_fee' => round($distanceFee, 2), 'weight_fee' => round($weightFee, 2), 'bulky_item_surcharge' => round($bulkyFee, 2), 'remote_area_surcharge' => $remoteFee, 'minimum_applied' => !$free && $shippingFee > $raw, 'free_shipping_applied' => $free]];
    }

    private function address(array $parts): string
    {
        return implode(', ', array_filter(array_map(fn ($part) => trim((string) $part), $parts)));
    }

    private function resolveDistance(
        DistanceService $distanceService,
        array $supplierAddressCandidates,
        string $branchAddress,
        ?float $branchLatitude,
        ?float $branchLongitude,
    ): float {
        $lastException = null;

        foreach ($supplierAddressCandidates as $supplierAddress) {
            try {
                if ($branchLatitude !== null && $branchLongitude !== null) {
                    return $distanceService->getDistanceKmToCoordinates($supplierAddress, $branchLatitude, $branchLongitude);
                }

                return $distanceService->getDistanceKm($supplierAddress, $branchAddress);
            } catch (\Throwable $exception) {
                $lastException = $exception;
            }
        }

        throw $lastException ?? new \RuntimeException('Unable to resolve supplier address.');
    }
}
