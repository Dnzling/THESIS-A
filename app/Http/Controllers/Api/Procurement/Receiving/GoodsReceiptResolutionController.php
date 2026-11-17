<?php

namespace App\Http\Controllers\Api\Procurement\Receiving;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Receiving\GoodsReceipt;
use App\Models\Procurement\Receiving\GoodsReceiptResolution;
use App\Models\Procurement\SupplierPortal\SupplierPortal;
use App\Models\Procurement\Invoice\Invoice;
use App\Models\Core\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GoodsReceiptResolutionController extends Controller
{
    public function store(Request $request, int $receiptId): JsonResponse
    {
        $user = $request->user();
        $storeId = (int) ($user?->store_id ?? 0);
        abort_unless($storeId > 0 && (
            $user->hasPermissionTo('procurement.purchase_orders.manage', $storeId)
            || $user->hasPermissionTo('inventory.receiving.manage', $storeId)
        ), 403);
        $assignedBranchId = (int) ($user?->branch_id ?? $user?->employee?->branch_id ?? 0);
        $validated = $request->validate([
            'resolution_type' => 'required|in:replacement,remaining_delivery,partial_acceptance,reject_delivery',
            'procurement_notes' => 'nullable|string|max:3000',
            'items' => 'required|array|min:1',
            'items.*.goods_receipt_item_id' => 'required|integer',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity_due' => 'required|integer|min:1',
        ]);

        $receipt = GoodsReceipt::with(['items.purchaseOrderItem', 'purchaseOrder'])
            ->whereHas('purchaseOrder', fn ($query) => $query->where('store_id', $request->user()->store_id))
            ->when($assignedBranchId > 0, fn ($query) => $query->where('branch_id', $assignedBranchId))
            ->findOrFail($receiptId);

        if (!$receipt->hasDiscrepancies()) {
            return response()->json(['success' => false, 'message' => 'A full goods receipt does not require a resolution.'], 422);
        }
        if (GoodsReceiptResolution::query()->where('original_goods_receipt_id', $receipt->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'This goods receipt already has a resolution on its purchase order.'], 422);
        }

        foreach ($validated['items'] as $item) {
            $receiptItem = $receipt->items->firstWhere('id', (int) $item['goods_receipt_item_id']);
            $maximumDue = $receiptItem
                ? max(0, (int) $receiptItem->quantity_expected - (int) $receiptItem->quantity_received + (int) $receiptItem->quantity_damaged)
                : 0;
            if (!$receiptItem || (int) $item['product_id'] !== (int) $receiptItem->product_id || (int) $item['quantity_due'] > $maximumDue) {
                return response()->json(['success' => false, 'message' => 'Resolution quantities must match the deficiency recorded on the original GRN.'], 422);
            }
        }

        $resolution = DB::transaction(function () use ($receipt, $validated, $request) {
            $resolution = GoodsReceiptResolution::create([
                'resolution_number' => 'GRR-' . now()->format('YmdHis') . '-' . random_int(1000, 9999),
                'original_goods_receipt_id' => $receipt->id,
                'purchase_order_id' => $receipt->purchase_order_id,
                'supplier_id' => $receipt->purchaseOrder->supplier_id,
                'resolution_type' => $validated['resolution_type'],
                'items' => array_map(function (array $item) use ($receipt) {
                    $receiptItem = $receipt->items->firstWhere('id', (int) $item['goods_receipt_item_id']);
                    return $item + ['purchase_order_item_id' => (int) $receiptItem->purchase_order_item_id];
                }, $validated['items']),
                'procurement_notes' => $validated['procurement_notes'] ?? null,
                'status' => 'pending_supplier',
                'flagged_by' => $request->user()->id,
            ]);
            ActivityLog::record(
                'po_receipt_discrepancy_sent',
                "Receipt discrepancy {$resolution->resolution_number} sent to supplier for PO {$receipt->purchaseOrder->po_number}.",
                ['resolution_id' => $resolution->id, 'resolution_number' => $resolution->resolution_number, 'goods_receipt_id' => $receipt->id],
                'purchase_order',
                $receipt->purchase_order_id
            );
            return $resolution;
        });

        return response()->json(['success' => true, 'message' => 'Deficiency resolution sent to the supplier.', 'data' => $resolution], 201);
    }

    public function showForReceipt(Request $request, int $receiptId): JsonResponse
    {
        $storeId = (int) ($request->user()?->store_id ?? 0);
        abort_unless($storeId > 0, 403);
        $branchId = (int) ($request->user()?->branch_id ?? $request->user()?->employee?->branch_id ?? 0);
        $resolution = GoodsReceiptResolution::with(['originalReceipt.items.product', 'followUpReceipt.items.product', 'followUpReceipts.items.product', 'supplier'])
            ->whereHas('purchaseOrder', fn ($query) => $query->where('store_id', $storeId)->when($branchId > 0, fn ($branchQuery) => $branchQuery->where('branch_id', $branchId)))
            ->where(fn ($query) => $query
                ->where('original_goods_receipt_id', $receiptId)
                ->orWhereHas('followUpReceipts', fn ($followUps) => $followUps->where('goods_receipts.id', $receiptId)))
            ->latest('id')->first();

        return response()->json(['success' => true, 'data' => $resolution]);
    }

    public function supplierForPurchaseOrder(Request $request, int $poId): JsonResponse
    {
        $portal = SupplierPortal::where('user_id', $request->user()->id)->firstOrFail();
        $resolutions = GoodsReceiptResolution::with(['originalReceipt.items.product', 'originalReceipt.items.purchaseOrderItem', 'followUpReceipt', 'followUpReceipts'])
            ->where('purchase_order_id', $poId)
            ->where('supplier_id', $portal->supplier_id)
            ->latest('id')->get();

        $resolutions->each(function (GoodsReceiptResolution $resolution) {
            $receiptItems = $resolution->originalReceipt?->items?->keyBy('id') ?? collect();
            $resolution->setAttribute('estimated_credit_amount', collect($resolution->items ?? [])->sum(function (array $item) use ($receiptItems) {
                $receiptItem = $receiptItems->get((int) ($item['goods_receipt_item_id'] ?? 0));
                return (int) ($item['quantity_due'] ?? 0) * (float) ($receiptItem?->purchaseOrderItem?->unit_cost ?? 0);
            }));
        });

        return response()->json(['success' => true, 'data' => $resolutions]);
    }

    public function respond(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:send_missing,replace_damaged,issue_credit,dispute',
            'notes' => 'required|string|min:5|max:3000',
            'credit_amount' => 'prohibited_unless:action,issue_credit,dispute|nullable|numeric|min:0.01',
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ]);
        $resolution = $this->supplierResolution($request, $id);
        if ($resolution->status !== 'pending_supplier') {
            return response()->json(['success' => false, 'message' => 'This discrepancy has already been answered.'], 422);
        }

        $receipt = $resolution->originalReceipt()->with('items.purchaseOrderItem')->firstOrFail();
        $maximumCredit = collect($resolution->items ?? [])->sum(function (array $item) use ($receipt) {
            $receiptItem = $receipt->items->firstWhere('id', (int) $item['goods_receipt_item_id']);
            $poItem = $receiptItem?->purchaseOrderItem;
            $creditQuantity = min((int) ($item['quantity_due'] ?? 0), (int) ($receiptItem?->quantity_damaged ?? 0));
            $creditQuantity += max(0, (int) ($item['quantity_due'] ?? 0) - $creditQuantity);
            return $creditQuantity * (float) ($poItem?->unit_cost ?? 0);
        });
        if (in_array($validated['action'], ['issue_credit', 'dispute'], true)
            && empty($validated['credit_amount'])) {
            return response()->json(['success' => false, 'message' => 'The reported value for an issue credit or dispute is required.'], 422);
        }
        if (in_array($validated['action'], ['issue_credit', 'dispute'], true)
            && abs((float) $validated['credit_amount'] - $maximumCredit) > 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'The credit amount must match the PO value of the reported shortage or damaged items (' . number_format($maximumCredit, 2) . ').',
            ], 422);
        }

        $proofPath = $resolution->proof_path;
        if ($request->hasFile('proof')) {
            if ($proofPath) Storage::disk('public')->delete($proofPath);
            $proofPath = $request->file('proof')->store("goods-receipt-resolutions/{$resolution->id}", 'public');
        }

        $status = match ($validated['action']) {
            'issue_credit' => 'credit_submitted',
            'dispute' => 'disputed',
            default => 'accepted',
        };
        $resolution->update([
            'supplier_action' => $validated['action'],
            'supplier_response_notes' => $validated['notes'],
            'supplier_rejection_reason' => $validated['action'] === 'dispute' ? $validated['notes'] : null,
            'supplier_credit_amount' => in_array($validated['action'], ['issue_credit', 'dispute'], true) ? $validated['credit_amount'] : null,
            'proof_path' => $proofPath,
            'status' => $status,
            'supplier_responded_by' => $request->user()->id,
            'supplier_responded_at' => now(),
        ]);
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'store_id' => $resolution->purchaseOrder->store_id,
            'branch_id' => $resolution->purchaseOrder->branch_id,
            'action' => 'po_receipt_discrepancy_supplier_response',
            'entity_type' => 'purchase_order',
            'entity_id' => $resolution->purchase_order_id,
            'description' => "Supplier responded to receipt discrepancy {$resolution->resolution_number}: " . str_replace('_', ' ', $validated['action']) . '.',
            'meta' => ['resolution_id' => $resolution->id, 'supplier_action' => $validated['action']],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['success' => true, 'message' => 'Supplier response recorded on the purchase order.', 'data' => $resolution->fresh()]);
    }

    public function review(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'decision' => 'required|in:approve_credit,accept_dispute,request_delivery',
            'notes' => 'required|string|min:5|max:3000',
        ]);
        $storeId = (int) $request->user()?->store_id;
        $branchId = (int) ($request->user()?->branch_id ?? $request->user()?->employee?->branch_id ?? 0);
        abort_unless($storeId > 0 && (
            $request->user()->hasPermissionTo('procurement.purchase_orders.manage', $storeId)
            || $request->user()->hasPermissionTo('procurement.purchase_orders.approve', $storeId)
        ), 403);

        $resolution = DB::transaction(function () use ($request, $validated, $id, $storeId, $branchId) {
            $resolution = GoodsReceiptResolution::query()
                ->with(['purchaseOrder.items', 'originalReceipt.items'])
                ->whereHas('purchaseOrder', fn ($query) => $query->where('store_id', $storeId)->when($branchId > 0, fn ($branchQuery) => $branchQuery->where('branch_id', $branchId)))
                ->lockForUpdate()
                ->findOrFail($id);

            if ($validated['decision'] === 'approve_credit') {
                if ($resolution->status !== 'credit_submitted') {
                    abort(422, 'There is no supplier credit awaiting approval.');
                }
                $this->recordSettledDamagedInventory($resolution);
                foreach ($resolution->items ?? [] as $item) {
                    $poItem = $resolution->purchaseOrder->items->firstWhere('id', (int) ($item['purchase_order_item_id'] ?? 0));
                    if (!$poItem || (int) $item['quantity_due'] > $poItem->quantity_pending) {
                        abort(422, 'The credit quantity no longer matches the open PO quantity.');
                    }
                    $poItem->increment('quantity_rejected', (int) $item['quantity_due']);
                }
                $resolution->update([
                    'status' => 'resolved',
                    'supplier_credit_amount' => (float) $resolution->supplier_credit_amount,
                    'procurement_notes' => trim($resolution->procurement_notes . "\n\nCredit approved: " . $validated['notes']),
                    'credit_reviewed_by' => $request->user()->id,
                    'credit_reviewed_at' => now(),
                    'resolved_at' => now(),
                ]);
                $this->syncResolvedPurchaseOrder($resolution);
            } elseif ($validated['decision'] === 'accept_dispute') {
                if ($resolution->status !== 'disputed') {
                    abort(422, 'There is no supplier dispute awaiting review.');
                }
                $this->recordSettledDamagedInventory($resolution);
                foreach ($resolution->items ?? [] as $item) {
                    $poItem = $resolution->purchaseOrder->items->firstWhere('id', (int) ($item['purchase_order_item_id'] ?? 0));
                    if (!$poItem || (int) $item['quantity_due'] > $poItem->quantity_pending) {
                        abort(422, 'The disputed quantity no longer matches the open PO quantity.');
                    }
                    $poItem->increment('quantity_rejected', (int) $item['quantity_due']);
                }
                $acceptedDisputeCredit = collect($resolution->items ?? [])->sum(function (array $item) use ($resolution) {
                    $poItem = $resolution->purchaseOrder->items->firstWhere('id', (int) ($item['purchase_order_item_id'] ?? 0));
                    return (int) ($item['quantity_due'] ?? 0) * (float) ($poItem?->unit_cost ?? 0);
                });
                $resolution->update([
                    'status' => 'resolved',
                    'supplier_credit_amount' => $acceptedDisputeCredit,
                    'resolution_type' => 'partial_acceptance',
                    'procurement_notes' => trim($resolution->procurement_notes . "\n\nDispute accepted: " . $validated['notes']),
                    'credit_reviewed_by' => $request->user()->id,
                    'credit_reviewed_at' => now(),
                    'resolved_at' => now(),
                ]);
                $this->syncResolvedPurchaseOrder($resolution);
            } else {
                if (!in_array($resolution->status, ['disputed', 'rejected'], true)) {
                    abort(422, 'Only a disputed resolution can be returned for delivery.');
                }
                $resolution->update([
                    'status' => 'accepted',
                    'supplier_action' => $resolution->resolution_type === 'replacement' ? 'replace_damaged' : 'send_missing',
                    'procurement_notes' => trim($resolution->procurement_notes . "\n\nDelivery requested: " . $validated['notes']),
                ]);
            }

            ActivityLog::record(
                'po_receipt_discrepancy_reviewed',
                "Procurement recorded {$validated['decision']} for receipt discrepancy {$resolution->resolution_number}.",
                ['resolution_id' => $resolution->id, 'decision' => $validated['decision'], 'notes' => $validated['notes']],
                'purchase_order',
                $resolution->purchase_order_id
            );

            return $resolution->fresh(['originalReceipt.items.product', 'followUpReceipt.items.product', 'supplier']);
        });

        return response()->json(['success' => true, 'message' => 'Discrepancy review saved.', 'data' => $resolution]);
    }

    private function syncResolvedPurchaseOrder(GoodsReceiptResolution $resolution): void
    {
        $po = $resolution->purchaseOrder()->with('items')->first();
        if (!$po || !$po->items->every(fn ($item) => $item->isFullyReceived())) {
            return;
        }

        $isSimpleInventoryPo = in_array($po->status, ['pending_receipt', 'partially_received'], true);
        $po->markGoodsReceived();
        $this->syncPurchaseRequisitionReceived($po->purchase_requisition_id);
        $invoice = Invoice::query()->where('purchase_order_id', $po->id)->first();
        if ($invoice) {
            $invoice->load(['purchaseOrder.items', 'items'])->performThreeWayMatch();
            return;
        }

        $originalReceipt = $resolution->originalReceipt()->first();
        if ($originalReceipt && !$isSimpleInventoryPo) {
            $createdInvoice = app(\App\Services\Procurement\PurchaseOrderInvoiceService::class)
                ->createForCompletedReceipt($po, $originalReceipt);
            $createdInvoice?->load(['purchaseOrder.items', 'items'])->performThreeWayMatch();
        }
    }

    private function syncPurchaseRequisitionReceived(?int $requisitionId): void
    {
        if (!$requisitionId) return;
        $hasOpenPurchaseOrders = \App\Models\Procurement\PurchaseOrder\PurchaseOrder::query()
            ->where('purchase_requisition_id', $requisitionId)
            ->whereNotIn('status', ['goods_received', 'cancelled', 'canceled', 'rejected'])
            ->exists();
        if (!$hasOpenPurchaseOrders) {
            \App\Models\Procurement\Requisition\PurchaseRequisition::query()
                ->whereKey($requisitionId)
                ->whereNotIn('status', ['rejected', 'cancelled', 'delivered', 'received'])
                ->update(['status' => 'received']);
        }
    }

    private function recordSettledDamagedInventory(GoodsReceiptResolution $resolution): void
    {
        $receiptItems = $resolution->originalReceipt?->items?->keyBy('id') ?? collect();
        foreach ($resolution->items ?? [] as $item) {
            $receiptItem = $receiptItems->get((int) ($item['goods_receipt_item_id'] ?? 0));
            if (!$receiptItem || (int) $receiptItem->quantity_damaged <= 0) {
                continue;
            }
            $poItem = $resolution->purchaseOrder->items->firstWhere('id', (int) ($item['purchase_order_item_id'] ?? 0));
            if (!$poItem) continue;

            $inventory = \App\Models\Inventory\BranchInventory::query()
                ->where('store_id', $resolution->purchaseOrder->store_id)
                ->where('branch_id', $resolution->purchaseOrder->branch_id)
                ->where('product_id', $poItem->product_id)
                ->when($poItem->variation_id, fn ($query) => $query->where('variation_id', $poItem->variation_id), fn ($query) => $query->whereNull('variation_id'))
                ->lockForUpdate()
                ->first();
            if (!$inventory) continue;

            $settledDamaged = min((int) $receiptItem->quantity_damaged, (int) $item['quantity_due']);
            $inventory->quantity_on_hand = max(0, (int) $inventory->quantity_on_hand - $settledDamaged);
            $inventory->quantity_damaged = max(0, (int) $inventory->quantity_damaged - $settledDamaged);
            $inventory->suppressAutomaticTransactionLog();
            $inventory->save();
            $inventory->updateStockStatus();
        }
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
        if (!in_array($resolution->supplier_action, ['send_missing', 'replace_damaged'], true)
            || !in_array($resolution->status, ['accepted', 'delivery_submitted'], true)) {
            return response()->json(['success' => false, 'message' => 'Accept the resolution before submitting delivery details.'], 422);
        }

        if ($resolution->proof_path) Storage::disk('public')->delete($resolution->proof_path);
        $path = $request->file('proof')->store("goods-receipt-resolutions/{$resolution->id}", 'public');
        $resolution->update(array_merge($validated, ['proof_path' => $path, 'status' => 'delivery_submitted']));
        $po = $resolution->purchaseOrder;
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'store_id' => $po->store_id,
            'branch_id' => $po->branch_id,
            'action' => 'po_receipt_follow_up_delivery_submitted',
            'entity_type' => 'purchase_order',
            'entity_id' => $po->id,
            'description' => "Supplier submitted follow-up delivery details for discrepancy {$resolution->resolution_number}.",
            'meta' => ['resolution_id' => $resolution->id, 'promised_delivery_date' => $validated['promised_delivery_date'], 'delivery_note_number' => $validated['delivery_note_number'] ?? null],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['success' => true, 'message' => 'Replacement delivery details submitted.', 'data' => $resolution]);
    }

    private function supplierResolution(Request $request, int $id): GoodsReceiptResolution
    {
        $portal = SupplierPortal::where('user_id', $request->user()->id)->firstOrFail();
        return GoodsReceiptResolution::where('supplier_id', $portal->supplier_id)->findOrFail($id);
    }
}
