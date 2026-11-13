<?php

namespace App\Http\Controllers\Api\Logistics;

use App\Http\Controllers\Api\Procurement\PurchaseOrder\PurchaseOrderController;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Ecommerce\EcommerceDeliveryVehicle;
use App\Models\Logistics\DeliveryTrip;
use App\Services\Core\PermissionService;
use App\Services\Logistics\DriverPermissionEligibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierPickupAssignmentController extends PurchaseOrderController
{
    private function authorizePickupManagement(Request $request): void
    {
        abort_unless(
            app(PermissionService::class)->userHasPermission($request->user(), 'logistics.supplier-pickups.manage'),
            403,
        );
    }

    public function showAssignment(Request $request, int $id): JsonResponse
    {
        $this->authorizePickupManagement($request);
        $storeId = (int) ($request->user()?->store_id ?: $request->user()?->employee?->store_id);
        abort_if($storeId < 1, 403);
        $po = PurchaseOrder::with(['supplier', 'branch', 'items.product'])
            ->where('store_id', $storeId)->findOrFail($id);
        abort_unless($po->status === 'supplier_accepted', 422, 'Only supplier-accepted purchase orders can be assigned.');
        abort_if($po->fulfillment_method === 'supplier_delivery', 422, 'Supplier delivery does not need a store pickup assignment.');

        return response()->json(['success' => true, 'data' => $po]);
    }

    public function pickupVehicles(Request $request, int $id): JsonResponse
    {
        $this->authorizePickupManagement($request);
        $storeId = (int) ($request->user()?->store_id ?: $request->user()?->employee?->store_id);
        abort_if($storeId < 1, 403);
        PurchaseOrder::where('store_id', $storeId)->findOrFail($id);

        $vehicles = EcommerceDeliveryVehicle::with('branch:id,name')
            ->where('store_id', $storeId)
            ->where('status', 'active')
            ->where('is_active', true)
            ->orderBy('vehicle_name')
            ->get();

        return response()->json(['success' => true, 'data' => $vehicles]);
    }

    public function pickupDrivers(Request $request, int $id, DriverPermissionEligibility $driverEligibility): JsonResponse
    {
        $this->authorizePickupManagement($request);
        return parent::pickupDrivers($request, $id, $driverEligibility);
    }

    public function assignPickup(Request $request, int $id, DriverPermissionEligibility $driverEligibility): JsonResponse
    {
        $this->authorizePickupManagement($request);
        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer'],
            'expected_pickup_date' => ['required', 'date'],
        ]);
        $storeId = (int) ($request->user()?->store_id ?: $request->user()?->employee?->store_id);
        $conflict = DeliveryTrip::query()
            ->where('store_id', $storeId)
            ->where('vehicle_id', (int) $validated['vehicle_id'])
            ->where('status', 'planned')
            ->whereDate('scheduled_departure_at', $validated['expected_pickup_date'])
            ->exists();
        if ($conflict) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'expected_pickup_date' => 'This vehicle already has a planned trip on the selected date. Choose another vehicle or date, or assign this pickup to that trip.',
            ]);
        }

        return parent::assignPickup($request, $id, $driverEligibility);
    }
}
