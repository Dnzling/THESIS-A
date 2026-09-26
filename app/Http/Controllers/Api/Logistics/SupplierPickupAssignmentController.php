<?php

namespace App\Http\Controllers\Api\Logistics;

use App\Http\Controllers\Api\Procurement\PurchaseOrder\PurchaseOrderController;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierPickupAssignmentController extends PurchaseOrderController
{
    public function showAssignment(Request $request, int $id): JsonResponse
    {
        $storeId = (int) ($request->user()?->store_id ?: $request->user()?->employee?->store_id);
        abort_if($storeId < 1, 403);
        $po = PurchaseOrder::with(['supplier', 'branch', 'items.product'])
            ->where('store_id', $storeId)->findOrFail($id);
        abort_unless($po->status === 'supplier_accepted', 422, 'Only supplier-accepted purchase orders can be assigned.');
        abort_if($po->fulfillment_method === 'supplier_delivery', 422, 'Supplier delivery does not need a store pickup assignment.');

        return response()->json(['success' => true, 'data' => $po]);
    }
}
