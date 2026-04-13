<?php

namespace App\Http\Controllers\Api\Procurement\SupplierPortal;

use App\Http\Controllers\Controller;
use App\Models\Core\ActivityLog;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Procurement\Shipping\PurchaseOrderDeliveryLog;
use App\Models\Procurement\Shipping\PurchaseOrderDeliveryLogAttachment;
use App\Models\Procurement\Shipping\PurchaseOrderShipment;
use App\Models\Procurement\SupplierPortal\SupplierPortal;
use App\Models\Procurement\Requisition\PurchaseRequisition;
use App\Models\Procurement\Invoice\Invoice;
use App\Models\Procurement\Invoice\InvoiceItem;
use App\Models\Procurement\Supplier\SupplierContract;
use App\Services\Logistics\DistanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupplierShipmentController extends Controller
{
    public function index(): JsonResponse
    {
        $portal = \App\Models\Procurement\SupplierPortal\SupplierPortal::where('user_id', auth()->id())->firstOrFail();
        $shipments = PurchaseOrderShipment::with(['purchaseOrder', 'branch'])
            ->where('supplier_id', $portal->supplier_id)
            ->orderByDesc('dispatched_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'shipments' => $shipments,
            ],
        ]);
    }

    public function show(int $poId): JsonResponse
    {
        $shipment = PurchaseOrderShipment::with(['purchaseOrder', 'supplier', 'branch'])
            ->where('purchase_order_id', $poId)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'shipment' => $shipment,
            ],
        ]);
    }

    public function showById(int $shipmentId): JsonResponse
    {
        $shipment = PurchaseOrderShipment::with(['purchaseOrder', 'supplier', 'branch'])
            ->findOrFail($shipmentId);

        $portal = \App\Models\Procurement\SupplierPortal\SupplierPortal::where('user_id', auth()->id())->firstOrFail();
        abort_if($portal->supplier_id !== $shipment->supplier_id, 403, 'Shipment does not belong to your supplier.');

        return response()->json([
            'success' => true,
            'data' => [
                'shipment' => $shipment,
            ],
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

        $po->loadMissing(['createdBy']);
        $creatorUserId = $po->createdBy?->user_id;
        if ($creatorUserId) {
            $this->notify((int) $creatorUserId, [
                'store_id' => $po->store_id,
                'branch_id' => $po->branch_id,
                'module' => 'procurement',
                'entity_type' => 'purchase_order_shipment',
                'entity_id' => $shipment->id,
                'action' => 'created',
                'title' => 'Supplier Shipment Created',
                'message' => "Supplier created a shipment for PO {$po->po_number}.",
                'severity' => 'info',
                'link' => "/system/procurement/purchase-orders/{$po->id}",
            ]);
        }

        $this->notifyUsersByPermissions(
            (int) $po->store_id,
            [
                'procurement.receiving.manage',
                'procurement.purchase_orders.manage',
                'logistics.shipments.manage',
            ],
            [
                'store_id' => $po->store_id,
                'branch_id' => $po->branch_id,
                'module' => 'procurement',
                'entity_type' => 'purchase_order_shipment',
                'entity_id' => $shipment->id,
                'action' => 'created',
                'title' => 'New Shipment In Transit',
                'message' => "Shipment for PO {$po->po_number} is now in transit.",
                'severity' => 'info',
                'link' => "/system/procurement/purchase-orders/{$po->id}",
            ],
            [(int) $portal->user_id]
        );

        $this->updatePurchaseRequisitionStatus($po->purchase_requisition_id, 'in_transit');

        return response()->json([
            'success' => true,
            'message' => 'Shipment created successfully.',
            'data' => [
                'shipment' => $shipment,
            ],
        ], 201);
    }

    public function deliver(Request $request, int $shipmentId): JsonResponse
    {
        $shipment = $this->guardedShipment($shipmentId);

        if ($shipment->status === 'delivered') {
            return response()->json([
                'success' => false,
                'message' => 'Shipment has already been marked as delivered.',
            ], 422);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:800',
            'receiver_name' => 'required|string|max:150',
            'attachments' => 'required|array|min:1',
            'attachments.*' => 'required|image|max:10240',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $log = PurchaseOrderDeliveryLog::create([
            'shipment_id' => $shipment->id,
            'created_by' => auth()->id(),
            'event_type' => 'Delivered',
            'notes' => $validated['notes'] ?? null,
            'receiver_name' => $validated['receiver_name'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'logged_at' => now(),
        ]);

        $attachmentFiles = [];
        foreach ($validated['attachments'] as $attachment) {
            if (!$attachment?->isValid()) {
                continue;
            }

            $slug = Str::slug(pathinfo($attachment->getClientOriginalName(), PATHINFO_FILENAME));
            $filename = sprintf('%s-%s.%s', $slug ?: 'delivery', Str::random(6), $attachment->getClientOriginalExtension());
            $path = $attachment->storeAs("supplier/shipments/logs/{$shipment->id}", $filename, 'public');

            $attachmentFiles[] = PurchaseOrderDeliveryLogAttachment::create([
                'delivery_log_id' => $log->id,
                'file_path' => $path,
                'mime_type' => $attachment->getClientMimeType(),
                'size' => $attachment->getSize() ?? 0,
            ]);
        }

        $shipment->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        $shipment->purchaseOrder?->markDelivered();

        $this->updatePurchaseRequisitionStatus($shipment->purchaseOrder?->purchase_requisition_id, 'delivered');

        $createdInvoice = $this->autoCreateInvoiceForDeliveredPurchaseOrder($shipment);

        ActivityLog::record(
            'po_shipment_delivered',
            "Shipment {$shipment->id} confirmed delivered for PO {$shipment->purchaseOrder?->po_number}.",
            [
                'po_number' => $shipment->purchaseOrder?->po_number,
                'shipment_id' => $shipment->id,
                'attachments' => array_map(fn ($attachment) => $attachment->id, $attachmentFiles),
            ],
            'purchase_order',
            $shipment->purchase_order_id
        );

        return response()->json([
            'success' => true,
            'message' => 'Shipment marked as delivered.',
            'data' => [
                'shipment' => $shipment->load(['purchaseOrder', 'supplier', 'branch']),
                'delivery_log' => $log->load(['creator', 'attachments']),
                'invoice' => $createdInvoice,
            ],
        ]);
    }

    protected function buildAddress(array $parts): string
    {
        $filtered = array_values(array_filter($parts, fn ($part) => !empty($part)));
        return implode(', ', $filtered);
    }

    private function updatePurchaseRequisitionStatus(?int $requisitionId, string $status): void
    {
        if (!$requisitionId) {
            return;
        }

        $pr = PurchaseRequisition::find($requisitionId);
        if (!$pr) {
            return;
        }

        $terminalStatuses = ['rejected', 'cancelled', 'delivered'];
        if (in_array($pr->status, $terminalStatuses, true)) {
            return;
        }

        if ($pr->status === $status) {
            return;
        }

        $previousStatus = (string) $pr->status;
        $pr->update(['status' => $status]);

        if ($status === 'delivered') {
            $pr->loadMissing(['requestedBy']);
            $requesterUserId = (int) ($pr->requestedBy?->user_id ?? 0);
            $notificationPayload = [
                'store_id' => (int) $pr->store_id,
                'branch_id' => (int) $pr->branch_id,
                'module' => 'inventory',
                'entity_type' => 'purchase_requisition',
                'entity_id' => (int) $pr->id,
                'action' => 'delivered',
                'title' => 'Purchase Requisition Delivered',
                'message' => "PR {$pr->pr_number} has been marked as delivered.",
                'severity' => 'success',
                'link' => "/inventory/requisites/{$pr->id}",
                'data' => [
                    'pr_id' => (int) $pr->id,
                    'pr_number' => (string) $pr->pr_number,
                    'status_from' => $previousStatus,
                    'status_to' => 'delivered',
                ],
            ];

            if ($requesterUserId > 0) {
                $this->notify($requesterUserId, $notificationPayload);
            }

            $this->notifyUsersByPermissions(
                (int) $pr->store_id,
                [
                    'inventory.requisites.view',
                    'inventory.requisites.manage',
                    'inventory.requisites.approve',
                    'inventory.requisitions.view',
                    'inventory.requisitions.manage',
                    'inventory.requisitions.approve',
                ],
                $notificationPayload,
                $requesterUserId > 0 ? [$requesterUserId] : []
            );
        }
    }

    protected function guardedShipment(int $shipmentId): PurchaseOrderShipment
    {
        $shipment = PurchaseOrderShipment::with(['purchaseOrder', 'supplier'])
            ->findOrFail($shipmentId);

        $portal = SupplierPortal::where('user_id', auth()->id())->firstOrFail();
        abort_if($portal->supplier_id !== $shipment->supplier_id, 403, 'Shipment does not belong to your supplier.');

        return $shipment;
    }

    private function autoCreateInvoiceForDeliveredPurchaseOrder(PurchaseOrderShipment $shipment): ?Invoice
    {
        $po = $shipment->purchaseOrder;
        if (!$po) {
            return null;
        }

        $storeId = (int) ($po->store_id ?? 0);
        $supplierId = (int) ($po->supplier_id ?? 0);
        if ($storeId <= 0 || $supplierId <= 0) {
            return null;
        }

        $existing = Invoice::where('store_id', $storeId)
            ->where('purchase_order_id', (int) $po->id)
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $po->loadMissing(['items']);

        $invoice = null;
        DB::beginTransaction();
        try {
            $contract = SupplierContract::where('store_id', $storeId)
                ->where('supplier_id', $supplierId)
                ->active()
                ->orderBy('end_date', 'desc')
                ->first();

            $invoiceNumber = 'INV-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));
            $invoiceDate = now()->toDateString();
            $dueDate = now()->addDays($this->getPaymentDays((string) $po->payment_terms))->toDateString();

            $itemsPayload = [];
            $invoiceAmount = 0.0;

            foreach (($po->items ?? []) as $poItem) {
                $qty = (int) ($poItem->quantity_ordered ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $unitPrice = (float) ($poItem->unit_cost ?? 0);
                $lineAmount = round($unitPrice * $qty, 2);
                $itemsPayload[] = [
                    'product_id' => (int) $poItem->product_id,
                    'quantity_invoiced' => $qty,
                    'unit_price' => $unitPrice,
                    'line_amount' => $lineAmount,
                ];
                $invoiceAmount += $lineAmount;
            }

            if (empty($itemsPayload)) {
                DB::rollBack();
                return null;
            }

            $taxRate = $contract && !$contract->is_tax_exempt ? (float) ($contract->tax_rate ?? 0) : 0.0;
            $discountRate = $contract ? (float) ($contract->discount_percentage ?? 0) : 0.0;
            $taxAmount = round(($invoiceAmount * $taxRate) / 100, 2);
            $shippingCost = (float) ($po->shipping_cost ?? 0);
            $discountAmount = round(($invoiceAmount * $discountRate) / 100, 2);
            $netAmount = $invoiceAmount + $taxAmount + $shippingCost - $discountAmount;

            $invoice = Invoice::create([
                'store_id' => $storeId,
                'invoice_number' => $invoiceNumber,
                'supplier_id' => $supplierId,
                'purchase_order_id' => (int) $po->id,
                'goods_receipt_id' => null,
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'invoice_amount' => $invoiceAmount,
                'tax_amount' => $taxAmount,
                'shipping_cost' => $shippingCost,
                'discount_amount' => $discountAmount,
                'net_amount' => $netAmount,
                'currency' => (string) ($po->currency ?? 'PHP'),
                'status' => 'pending_approval',
                'match_status' => 'pending',
                'payment_status' => 'pending',
                'remarks' => 'Auto-created from supplier delivered shipment',
            ]);

            foreach ($itemsPayload as $item) {
                InvoiceItem::create(array_merge($item, ['invoice_id' => (int) $invoice->id]));
            }

            $invoice->performThreeWayMatch();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed auto-creating invoice from delivered shipment', [
                'shipment_id' => (int) $shipment->id,
                'purchase_order_id' => (int) $po->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        if ($invoice) {
            $notificationPayload = [
                'store_id' => $storeId,
                'branch_id' => (int) ($po->branch_id ?? 0),
                'module' => 'finance',
                'entity_type' => 'invoice',
                'entity_id' => (int) $invoice->id,
                'action' => 'supplier_invoice_submitted',
                'title' => 'Supplier Invoice Submitted',
                'message' => "Supplier invoice {$invoice->invoice_number} was auto-submitted after PO {$po->po_number} delivery.",
                'severity' => 'info',
                'link' => "/finance/invoices/{$invoice->id}",
                'data' => [
                    'invoice_id' => (int) $invoice->id,
                    'invoice_number' => (string) $invoice->invoice_number,
                    'purchase_order_id' => (int) $po->id,
                    'purchase_order_number' => (string) ($po->po_number ?? ''),
                    'trigger' => 'shipment_delivered',
                ],
            ];

            $this->notifyUsersByPermissions(
                $storeId,
                [
                    'finance.invoices.view',
                    'finance.invoices.manage',
                    'finance.invoices.approve',
                    'finance.payables.view',
                    'finance.payables.manage',
                    'finance.payables.approve',
                ],
                $notificationPayload
            );
        }

        return $invoice?->load('items');
    }

    private function getPaymentDays(string $term): int
    {
        return match ($term) {
            'net_7' => 7,
            'net_15' => 15,
            'net_30' => 30,
            'net_60' => 60,
            'advance_payment' => 0,
            'cash_on_delivery' => 0,
            default => 30,
        };
    }
}
