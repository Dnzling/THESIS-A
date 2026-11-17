<?php

namespace App\Http\Controllers\Api\WarehouseOperations;

use App\Http\Controllers\Api\Procurement\Receiving\GoodsReceiptController as SharedGoodsReceiptController;
use App\Http\Controllers\Controller;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Procurement\Receiving\GoodsReceipt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseGoodsReceiptController extends Controller
{
    private function storeId(Request $request): int
    {
        $storeId = (int) ($request->user()?->store_id ?: $request->user()?->employee?->store_id);
        abort_if($storeId < 1, 403, 'Your account is not assigned to a store.');

        return $storeId;
    }

    private function authorizeReceiving(Request $request): void
    {
        $user = $request->user();
        abort_unless(
            $user && $user->hasPermissionTo('warehouse.receiving.view', $this->storeId($request)),
            403,
            'You do not have permission to access warehouse receiving.'
        );
    }

    private function assertWarehousePurchaseOrder(Request $request, int $purchaseOrderId): void
    {
        PurchaseOrder::query()
            ->where('store_id', $this->storeId($request))
            ->whereHas('branch', fn ($query) => $query->where('branch_type', 'warehouse'))
            ->findOrFail($purchaseOrderId);
    }

    private function assertWarehouseReceipt(Request $request, int $receiptId): void
    {
        GoodsReceipt::query()
            ->whereHas('purchaseOrder', function ($query) use ($request) {
                $query->where('store_id', $this->storeId($request))
                    ->whereHas('branch', fn ($branch) => $branch->where('branch_type', 'warehouse'));
            })
            ->findOrFail($receiptId);
    }

    public function store(Request $request, SharedGoodsReceiptController $receipts): JsonResponse
    {
        $this->authorizeReceiving($request);
        $validated = $request->validate(['purchase_order_id' => ['required', 'integer']]);
        $this->assertWarehousePurchaseOrder($request, (int) $validated['purchase_order_id']);

        return $receipts->store($request);
    }

    public function show(Request $request, int $id, SharedGoodsReceiptController $receipts): JsonResponse
    {
        $this->authorizeReceiving($request);
        $this->assertWarehouseReceipt($request, $id);

        return $receipts->show($request, $id);
    }

    public function updateDraft(Request $request, int $id, SharedGoodsReceiptController $receipts): JsonResponse
    {
        $this->authorizeReceiving($request);
        $this->assertWarehouseReceipt($request, $id);

        return $receipts->updateDraft($request, $id);
    }

    public function verify(Request $request, int $id, SharedGoodsReceiptController $receipts): JsonResponse
    {
        $this->authorizeReceiving($request);
        $this->assertWarehouseReceipt($request, $id);

        return $receipts->verify($id);
    }
}
