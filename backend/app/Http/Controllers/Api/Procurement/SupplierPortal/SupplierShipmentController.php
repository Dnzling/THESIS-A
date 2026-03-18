<?php

namespace App\Http\Controllers\Api\Procurement\SupplierPortal;

use App\Http\Controllers\Controller;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Procurement\Shipping\PurchaseOrderShipment;
use App\Models\Core\ActivityLog;
use App\Services\Logistics\DistanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierShipmentController extends Controller
{
    public function show(int $poId): JsonResponse
    {
        $shipment = PurchaseOrderShipment::with(['purchaseOrder', 'supplier', 'branch'])
            ->where('purchase_order_id', $poId)
            ->first();

        $invoice = Invoice::with(['items.product'])
            ->where('purchase_order_id', $poId)
            ->latest('id')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'shipment' => $shipment,
                'invoice' => $invoice,
            ],
        ]);
    }

    public function invoice(int $poId): JsonResponse
    {
        $po = PurchaseOrder::with(['supplier'])
            ->findOrFail($poId);

        $portal = \App\Models\Procurement\SupplierPortal\SupplierPortal::where('user_id', auth()->id())->first();
        if (!$portal || !$portal->supplier_id || $portal->supplier_id !== $po->supplier_id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this invoice.',
            ], 403);
        }

        $invoice = Invoice::with(['items.product'])
            ->where('purchase_order_id', $poId)
            ->latest('id')
            ->first();

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found for this purchase order.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $invoice,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'truck_number' => 'nullable|string|max:100',
            'truck_brand' => 'nullable|string|max:100',
            'truck_type' => 'nullable|string|max:100',
            'wheel_count' => 'nullable|integer|min:2|max:24',
            'plate_number' => 'nullable|string|max:50',
            'driver_name' => 'required|string|max:150',
            'driver_contact' => 'nullable|string|max:50',
            'cost_per_km' => 'required|numeric|min:0',
            'distance_km' => 'nullable|numeric|min:0',
            'current_latitude' => 'nullable|numeric|between:-90,90',
            'current_longitude' => 'nullable|numeric|between:-180,180',
            'dispatched_at' => 'nullable|date',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'expected_delivery_date' => 'nullable|date',
        ]);

        $po = PurchaseOrder::with(['supplier', 'branch', 'items.product'])
            ->findOrFail($validated['purchase_order_id']);

        $portal = \App\Models\Procurement\SupplierPortal\SupplierPortal::where('user_id', auth()->id())->first();
        if (!$portal || !$portal->supplier_id || $portal->supplier_id !== $po->supplier_id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to ship this purchase order.',
            ], 403);
        }

        if ($po->status !== 'supplier_accepted') {
            return response()->json([
                'success' => false,
                'message' => 'Purchase order must be accepted before creating a delivery form.',
            ], 422);
        }

        if (PurchaseOrderShipment::where('purchase_order_id', $po->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'A delivery form already exists for this purchase order.',
            ], 422);
        }

        $originAddress = $this->buildAddress([
            $po->supplier?->address,
            $po->supplier?->city,
            $po->supplier?->province,
            $po->supplier?->country,
        ]);

        $destinationAddress = $this->buildAddress([
            $po->branch?->address,
            $po->branch?->city,
            $po->branch?->province,
        ]);

        if (!$originAddress || !$destinationAddress) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier and branch addresses are required to compute shipping cost.',
            ], 422);
        }

        $distanceKm = $validated['distance_km'] ?? null;
        if ($distanceKm === null) {
            $distanceService = new DistanceService();
            $distanceKm = $distanceService->getDistanceKm($originAddress, $destinationAddress);
        }

        $shippingCost = round(((float) $distanceKm) * ((float) $validated['cost_per_km']), 2);

        $shipment = PurchaseOrderShipment::create([
            'purchase_order_id' => $po->id,
            'supplier_id' => $po->supplier_id,
            'branch_id' => $po->branch_id,
            'created_by' => auth()->id(),
            'truck_number' => $validated['truck_number'] ?? null,
            'truck_brand' => $validated['truck_brand'] ?? null,
            'truck_type' => $validated['truck_type'] ?? null,
            'wheel_count' => $validated['wheel_count'] ?? null,
            'plate_number' => $validated['plate_number'] ?? null,
            'driver_name' => $validated['driver_name'],
            'driver_contact' => $validated['driver_contact'] ?? null,
            'origin_address' => $originAddress,
            'destination_address' => $destinationAddress,
            'current_latitude' => $validated['current_latitude'] ?? null,
            'current_longitude' => $validated['current_longitude'] ?? null,
            'distance_km' => $distanceKm,
            'cost_per_km' => $validated['cost_per_km'],
            'shipping_cost' => $shippingCost,
            'tax_rate' => $validated['tax_rate'] ?? null,
            'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
            'dispatched_at' => $validated['dispatched_at'] ?? now(),
            'status' => 'in_transit',
        ]);

        $po->update([
            'status' => 'in_transit',
            'shipping_cost' => $shippingCost,
            'expected_delivery_date' => $validated['expected_delivery_date'] ?? $po->expected_delivery_date,
        ]);

        ActivityLog::record(
            'po_shipment_created',
            "Delivery form created for PO {$po->po_number}.",
            [
                'po_number' => $po->po_number,
                'shipment_id' => $shipment->id,
                'shipping_cost' => $shippingCost,
            ],
            'purchase_order',
            $po->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Shipment created successfully.',
            'data' => [
                'shipment' => $shipment,
            ],
        ], 201);
    }

    protected function buildAddress(array $parts): string
    {
        $filtered = array_values(array_filter($parts, fn ($part) => !empty($part)));
        return implode(', ', $filtered);
    }
}
