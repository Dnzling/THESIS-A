<?php

namespace App\Http\Controllers\Api\Procurement\Receiving;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Receiving\GoodsReceipt;
use App\Models\Procurement\Receiving\GoodsReceiptResolution;
use App\Models\Procurement\SupplierPortal\SupplierPortal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GoodsReceiptResolutionController extends Controller
{
    public function store(Request $request, int $receiptId): JsonResponse
    {
        $validated = $request->validate([
            'resolution_type' => 'required|in:replacement,remaining_delivery,partial_acceptance,reject_delivery',
            'procurement_notes' => 'nullable|string|max:3000',
            'items' => 'required|array|min:1',
            'items.*.goods_receipt_item_id' => 'required|integer',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity_due' => 'required|integer|min:1',
        ]);

        $receipt = GoodsReceipt::with(['items', 'purchaseOrder'])
            ->whereHas('purchaseOrder', fn ($query) => $query->where('store_id', $request->user()->store_id))
            ->findOrFail($receiptId);

        if (!$receipt->hasDiscrepancies()) {
            return response()->json(['success' => false, 'message' => 'A full goods receipt does not require a resolution.'], 422);
        }

        foreach ($validated['items'] as $item) {
            $receiptItem = $receipt->items->firstWhere('id', (int) $item['goods_receipt_item_id']);
            $maximumDue = $receiptItem
                ? max(0, (int) $receiptItem->quantity_expected - (int) $receiptItem->quantity_received)
                    + (int) $receiptItem->quantity_damaged
                : 0;
            if (!$receiptItem || (int) $item['product_id'] !== (int) $receiptItem->product_id || (int) $item['quantity_due'] > $maximumDue) {
                return response()->json(['success' => false, 'message' => 'Resolution quantities must match the deficiency recorded on the original GRN.'], 422);
            }
        }

        $resolution = DB::transaction(function () use ($receipt, $validated, $request) {
            return GoodsReceiptResolution::create([
                'resolution_number' => 'GRR-' . now()->format('YmdHis') . '-' . random_int(1000, 9999),
                'original_goods_receipt_id' => $receipt->id,
                'purchase_order_id' => $receipt->purchase_order_id,
                'supplier_id' => $receipt->purchaseOrder->supplier_id,
                'resolution_type' => $validated['resolution_type'],
                'items' => $validated['items'],
                'procurement_notes' => $validated['procurement_notes'] ?? null,
                'status' => 'pending_supplier',
                'flagged_by' => $request->user()->id,
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Deficiency resolution sent to the supplier.', 'data' => $resolution], 201);
    }

    public function showForReceipt(Request $request, int $receiptId): JsonResponse
    {
        $resolution = GoodsReceiptResolution::with(['originalReceipt.items.product', 'followUpReceipt.items.product', 'supplier'])
            ->where('original_goods_receipt_id', $receiptId)
            ->whereHas('purchaseOrder', fn ($query) => $query->where('store_id', $request->user()->store_id))
            ->latest('id')->first();

        return response()->json(['success' => true, 'data' => $resolution]);
    }

    public function supplierForPurchaseOrder(Request $request, int $poId): JsonResponse
    {
        $portal = SupplierPortal::where('user_id', $request->user()->id)->firstOrFail();
        $resolutions = GoodsReceiptResolution::with(['originalReceipt.items.product', 'followUpReceipt'])
            ->where('purchase_order_id', $poId)
            ->where('supplier_id', $portal->supplier_id)
            ->latest('id')->get();

        return response()->json(['success' => true, 'data' => $resolutions]);
    }

    public function accept(Request $request, int $id): JsonResponse
    {
        $resolution = $this->supplierResolution($request, $id);
        if ($resolution->status !== 'pending_supplier') {
            return response()->json(['success' => false, 'message' => 'This resolution has already been answered.'], 422);
        }
        $resolution->update(['status' => 'accepted', 'supplier_responded_by' => $request->user()->id, 'supplier_responded_at' => now()]);
        return response()->json(['success' => true, 'message' => 'Resolution accepted.', 'data' => $resolution]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate(['reason' => 'required|string|min:5|max:2000']);
        $resolution = $this->supplierResolution($request, $id);
        if ($resolution->status !== 'pending_supplier') {
            return response()->json(['success' => false, 'message' => 'This resolution has already been answered.'], 422);
        }
        $resolution->update([
            'status' => 'rejected', 'supplier_rejection_reason' => $validated['reason'],
            'supplier_responded_by' => $request->user()->id, 'supplier_responded_at' => now(),
        ]);
        return response()->json(['success' => true, 'message' => 'Resolution rejected.', 'data' => $resolution]);
    }

    public function submitDelivery(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'promised_delivery_date' => 'required|date|after_or_equal:today',
            'delivery_note_number' => 'nullable|string|max:100',
            'supplier_delivery_notes' => 'nullable|string|max:2000',
            'proof' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ]);
        $resolution = $this->supplierResolution($request, $id);
        if (!in_array($resolution->status, ['accepted', 'delivery_submitted'], true)) {
            return response()->json(['success' => false, 'message' => 'Accept the resolution before submitting delivery details.'], 422);
        }

        if ($resolution->proof_path) Storage::disk('public')->delete($resolution->proof_path);
        $path = $request->file('proof')->store("goods-receipt-resolutions/{$resolution->id}", 'public');
        $resolution->update(array_merge($validated, ['proof_path' => $path, 'status' => 'delivery_submitted']));

        return response()->json(['success' => true, 'message' => 'Replacement delivery details submitted.', 'data' => $resolution]);
    }

    private function supplierResolution(Request $request, int $id): GoodsReceiptResolution
    {
        $portal = SupplierPortal::where('user_id', $request->user()->id)->firstOrFail();
        return GoodsReceiptResolution::where('supplier_id', $portal->supplier_id)->findOrFail($id);
    }
}
