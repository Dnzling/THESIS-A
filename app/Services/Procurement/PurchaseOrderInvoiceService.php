<?php

namespace App\Services\Procurement;

use App\Models\Procurement\Invoice\Invoice;
use App\Models\Procurement\Invoice\InvoiceItem;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Procurement\Receiving\GoodsReceipt;
use App\Models\Procurement\Receiving\GoodsReceiptResolution;
use App\Models\Procurement\Supplier\SupplierContract;
use App\Models\Core\ActivityLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PurchaseOrderInvoiceService
{
    public function createForCompletedReceipt(PurchaseOrder $po, GoodsReceipt $grn): ?Invoice
    {
        if (Invoice::query()->where('purchase_order_id', $po->id)->lockForUpdate()->exists()) {
            return null;
        }

        $po->loadMissing('items');
        $creditedItems = GoodsReceiptResolution::query()
            ->where('purchase_order_id', $po->id)
            ->where('status', 'resolved')
            ->whereIn('supplier_action', ['issue_credit', 'dispute'])
            ->get(['items'])
            ->flatMap(fn (GoodsReceiptResolution $resolution) => $resolution->items ?? [])
            ->groupBy(fn (array $item) => (int) ($item['purchase_order_item_id'] ?? 0));

        $items = [];
        $invoiceAmount = 0.0;
        $unresolvedQuantityByItem = GoodsReceiptResolution::query()
            ->where('purchase_order_id', $po->id)
            ->whereIn('status', ['pending_supplier', 'accepted', 'delivery_submitted', 'credit_submitted', 'disputed', 'rejected'])
            ->whereNotIn('supplier_action', ['issue_credit', 'dispute'])
            ->get(['items'])
            ->flatMap(fn (GoodsReceiptResolution $resolution) => $resolution->items ?? [])
            ->groupBy(fn (array $item) => (int) ($item['purchase_order_item_id'] ?? 0));
        foreach ($po->items as $poItem) {
            $creditedQuantity = (int) $creditedItems->get($poItem->id, collect())->sum('quantity_due');
            $unresolvedQuantity = (int) $unresolvedQuantityByItem->get($poItem->id, collect())->sum('quantity_due');
            // Unresolved shortages and damaged units remain excluded from the
            // invoice. Once a credit is approved, that settled quantity becomes
            // invoiceable even though it was never added to usable inventory.
            $quantity = max(0, (int) $poItem->quantity_received + $creditedQuantity - $unresolvedQuantity);
            if ($quantity <= 0) continue;

            $unitPrice = (float) ($poItem->unit_cost ?? 0);
            $lineAmount = round($unitPrice * $quantity, 2);
            $items[] = [
                'product_id' => $poItem->product_id,
                'quantity_invoiced' => $quantity,
                'unit_price' => $unitPrice,
                'line_amount' => $lineAmount,
            ];
            $invoiceAmount += $lineAmount;
        }

        if (!$items) {
            Log::warning('Skipped automatic invoice because PO has no invoiceable items.', [
                'purchase_order_id' => $po->id,
                'goods_receipt_id' => $grn->id,
            ]);
            return null;
        }

        $contract = SupplierContract::query()
            ->where('store_id', $po->store_id)
            ->where('supplier_id', $po->supplier_id)
            ->active()
            ->latest('end_date')
            ->first();
        $taxRate = $contract && !$contract->is_tax_exempt ? (float) ($contract->tax_rate ?? 0) : 0.0;
        $discountRate = (float) ($contract->discount_percentage ?? 0);
        $discountAmount = round(($invoiceAmount * $discountRate) / 100, 2);
        $taxAmount = InclusiveTaxCalculator::includedTax(max(0, $invoiceAmount - $discountAmount), $taxRate);
        $shippingCost = $po->fulfillment_method === 'supplier_delivery' ? (float) ($po->shipping_cost ?? 0) : 0.0;

        $invoice = Invoice::create([
            'store_id' => $po->store_id,
            'invoice_number' => 'INV-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4)),
            'supplier_id' => $po->supplier_id,
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $grn->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays($this->paymentDaysFor($po->payment_terms))->toDateString(),
            'invoice_amount' => $invoiceAmount,
            'tax_amount' => $taxAmount,
            'shipping_cost' => $shippingCost,
            'discount_amount' => $discountAmount,
            'net_amount' => max(0, $invoiceAmount - $discountAmount) + $shippingCost,
            'currency' => $po->currency ?? 'PHP',
            'status' => 'pending_approval',
            'match_status' => 'pending',
            'payment_status' => 'pending',
            'remarks' => "Auto-created from goods receipt {$grn->grn_number}. Invoice remains subject to receipt discrepancy review where applicable.",
        ]);

        foreach ($items as $item) {
            InvoiceItem::create(array_merge($item, ['invoice_id' => $invoice->id]));
        }
        $invoice->load(['purchaseOrder.items', 'items'])->performThreeWayMatch();
        ActivityLog::record(
            'invoice_auto_created_from_goods_receipt',
            "Invoice {$invoice->invoice_number} automatically sent to Finance for PO {$po->po_number}.",
            ['invoice_id' => $invoice->id, 'goods_receipt_id' => $grn->id, 'po_number' => $po->po_number],
            'invoice',
            $invoice->id
        );
        return $invoice;
    }

    private function paymentDaysFor(?string $term): int
    {
        return match ($term) {
            'net_7' => 7,
            'net_15' => 15,
            'net_30' => 30,
            'net_60' => 60,
            default => 0,
        };
    }
}
