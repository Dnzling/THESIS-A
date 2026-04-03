<?php

namespace App\Http\Controllers\Api\Procurement\SupplierPortal;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Shipping\PurchaseOrderDeliveryLog;
use App\Models\Procurement\Shipping\PurchaseOrderShipment;
use App\Models\Procurement\SupplierPortal\SupplierPortal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierDeliveryLogController extends Controller
{
    public function index(int $shipmentId): JsonResponse
    {
        $shipment = $this->guardedShipment($shipmentId);

        $logs = PurchaseOrderDeliveryLog::with(['creator', 'attachments'])
            ->where('shipment_id', $shipment->id)
            ->orderByDesc('logged_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'shipment' => $shipment,
                'logs' => $logs,
            ],
        ]);
    }

    public function store(Request $request, int $shipmentId): JsonResponse
    {
        $shipment = $this->guardedShipment($shipmentId);

        $validated = $request->validate([
            'event_type' => 'required|string|max:100',
            'notes' => 'nullable|string|max:800',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $log = PurchaseOrderDeliveryLog::create([
            'shipment_id' => $shipment->id,
            'created_by' => auth()->id(),
            'event_type' => $validated['event_type'],
            'notes' => $validated['notes'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'logged_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $log->load('attachments'),
        ], 201);
    }

    protected function guardedShipment(int $shipmentId): PurchaseOrderShipment
    {
        $shipment = PurchaseOrderShipment::with(['purchaseOrder', 'supplier'])
            ->findOrFail($shipmentId);

        $portal = SupplierPortal::where('user_id', auth()->id())->firstOrFail();
        abort_if($portal->supplier_id !== $shipment->supplier_id, 403, 'Shipment does not belong to your supplier.');

        return $shipment;
    }
}
