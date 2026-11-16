<?php

namespace App\Services\Procurement;

use App\Models\Procurement\Supplier\Supplier;
use App\Models\Procurement\SupplierPortal\SupplierPortal;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;
use App\Models\Store\Branch;
use App\Services\Logistics\DistanceService;
use Illuminate\Validation\ValidationException;

class PurchaseOrderShippingFeeService
{
    public function estimate(int $storeId, Supplier $supplier, Branch $branch, array $items, float $subtotal): array
    {
        $portal = $supplier->supplier_portal_id
            ? SupplierPortal::query()->whereKey($supplier->supplier_portal_id)->first()
            : null;
        if (!$portal || $portal->delivery_fee_per_km === null || $portal->delivery_fee_per_kg === null) {
            throw ValidationException::withMessages(['shipping_fee' => 'The supplier must set both delivery rates before a Supplier Delivery PO can be created.']);
        }

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

        $products = Product::query()->where('store_id', $storeId)->whereIn('id', collect($items)->pluck('product_id')->filter()->unique())->get()->keyBy('id');
        $variations = ProductVariation::query()
            ->where('store_id', $storeId)
            ->whereIn('id', collect($items)->pluck('variation_id')->filter()->unique())
            ->get()->keyBy('id');
        $totalWeight = 0.0;
        foreach ($items as $item) {
            $product = $products->get((int) ($item['product_id'] ?? 0));
            $quantity = max(0, (float) ($item['quantity_ordered'] ?? 0));
            if (!$product) continue;
            $variation = $variations->get((int) ($item['variation_id'] ?? 0));
            $weight = (float) ($variation?->weight_kg ?? $product->weight_kg ?? 0);
            $totalWeight += $weight * $quantity;
        }
        $perKm = (float) $portal->delivery_fee_per_km;
        $perKg = (float) $portal->delivery_fee_per_kg;
        $distanceFee = $distanceKm * $perKm;
        $weightFee = $totalWeight * $perKg;
        return [
            'shipping_fee' => round($distanceFee + $weightFee, 2),
            'breakdown' => [
                'distance_km' => round($distanceKm, 2),
                'total_weight_kg' => round($totalWeight, 2),
                'rate_per_km' => $perKm,
                'rate_per_kg' => $perKg,
                'distance_fee' => round($distanceFee, 2),
                'weight_fee' => round($weightFee, 2),
            ],
        ];
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
