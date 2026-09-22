<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\EcommerceOrder;
use App\Models\Procurement\Invoice\Invoice;
use App\Models\Sales\SalesOrder;
use App\Models\Store\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class FinanceTaxVatController extends Controller
{
    private const DEFAULT_VAT_RATE = 12.00;

    public function index(Request $request): JsonResponse
    {
        try {
            $storeId = $request->user()?->store_id;
            $source = strtolower((string) $request->input('source', 'all'));
            $branchId = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;

            $rows = collect();

            if (in_array($source, ['all', 'sales'], true)) {
                $rows = $rows->merge($this->salesRows($request, $storeId, $branchId));
            }

            if (in_array($source, ['all', 'ecommerce'], true)) {
                $rows = $rows->merge($this->ecommerceRows($request, $storeId, $branchId));
            }

            if (in_array($source, ['all', 'purchases'], true)) {
                $rows = $rows->merge($this->purchaseRows($request, $storeId, $branchId));
            }

            $rows = $rows->sortByDesc('date')->values();

            $summary = [
                'output_vat' => round((float) $rows->where('tax_type', 'output')->sum('tax_amount'), 2),
                'input_vat' => round((float) $rows->where('tax_type', 'input')->sum('tax_amount'), 2),
                'taxable_sales' => round((float) $rows->where('tax_type', 'output')->sum('taxable_amount'), 2),
                'taxable_purchases' => round((float) $rows->where('tax_type', 'input')->sum('taxable_amount'), 2),
                'net_vat' => round(
                    (float) $rows->where('tax_type', 'output')->sum('tax_amount')
                    - (float) $rows->where('tax_type', 'input')->sum('tax_amount'),
                    2
                ),
                'transaction_count' => $rows->count(),
            ];

            $perPage = max(1, min((int) $request->input('per_page', 15), 100));
            $page = max(1, (int) $request->input('page', 1));
            $total = $rows->count();
            $pagedRows = $rows->forPage($page, $perPage)->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'rows' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                        'last_page' => max(1, (int) ceil($total / $perPage)),
                        'data' => $pagedRows,
                    ],
                    'branches' => Branch::query()
                        ->where('store_id', $storeId)
                        ->where('status', 'active')
                        ->orderBy('name')
                        ->get(['id', 'name', 'branch_type']),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to retrieve finance tax/VAT report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load the tax/VAT report.',
            ], 500);
        }
    }

    private function salesRows(Request $request, ?int $storeId, ?int $branchId): Collection
    {
        $query = SalesOrder::query()
            ->with('branch:id,name')
            ->where('store_id', $storeId)
            ->where('status', '!=', 'cancelled');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $this->applyDateFilter($query, $request, 'created_at');

        return $query->get()->map(function (SalesOrder $order) {
            $subtotal = (float) ($order->subtotal ?? 0);
            $discount = (float) ($order->discount_amount ?? 0);
            $tax = (float) ($order->tax_amount ?? 0);

            return [
                'tax_type' => 'output',
                'source' => 'sales',
                'source_label' => 'In Store POS',
                'reference' => $order->order_number ?: 'SO-' . $order->id,
                'date' => optional($order->created_at)->toDateString(),
                'branch' => $order->branch?->name ?: 'Unassigned branch',
                'counterparty' => $order->customer_name ?: 'Walk-in Customer',
                'tax_rate' => self::DEFAULT_VAT_RATE,
                'taxable_amount' => round(max(0, $subtotal - $discount - $tax), 2),
                'tax_amount' => round($tax, 2),
                'gross_amount' => round((float) ($order->total_amount ?? 0), 2),
                'status' => $order->payment_status ?: $order->status,
            ];
        });
    }

    private function ecommerceRows(Request $request, ?int $storeId, ?int $branchId): Collection
    {
        $query = EcommerceOrder::query()
            ->with('assignedBranch:id,name')
            ->where('store_id', $storeId)
            ->where('status', '!=', 'cancelled');

        if ($branchId) {
            $query->where('assigned_branch_id', $branchId);
        }

        $this->applyDateFilter($query, $request, 'placed_at', 'created_at');

        return $query->get()->map(function (EcommerceOrder $order) {
            $subtotal = (float) ($order->subtotal ?? 0);
            $discount = (float) ($order->discount_amount ?? 0);
            $tax = (float) ($order->tax_amount ?? 0);

            return [
                'tax_type' => 'output',
                'source' => 'ecommerce',
                'source_label' => 'Ecommerce',
                'reference' => $order->order_number ?: 'EC-' . $order->id,
                'date' => optional($order->placed_at ?: $order->created_at)->toDateString(),
                'branch' => $order->assignedBranch?->name ?: 'Unassigned branch',
                'counterparty' => $order->shipping_name ?: 'Ecommerce Customer',
                'tax_rate' => self::DEFAULT_VAT_RATE,
                'taxable_amount' => round(max(0, $subtotal - $discount - $tax), 2),
                'tax_amount' => round($tax, 2),
                'gross_amount' => round((float) ($order->total_amount ?? 0), 2),
                'status' => $order->payment_status ?: $order->status,
            ];
        });
    }

    private function purchaseRows(Request $request, ?int $storeId, ?int $branchId): Collection
    {
        $query = Invoice::query()
            ->with([
                'supplier:id,supplier_name',
                'purchaseOrder:id,branch_id,po_number',
                'purchaseOrder.branch:id,name',
            ])
            ->where('store_id', $storeId)
            ->whereNotIn('status', ['cancelled', 'rejected']);

        if ($branchId) {
            $query->whereHas('purchaseOrder', fn ($po) => $po->where('branch_id', $branchId));
        }

        $this->applyDateFilter($query, $request, 'invoice_date');

        return $query->get()->map(function (Invoice $invoice) {
            $invoiceAmount = (float) ($invoice->invoice_amount ?? 0);
            $discount = (float) ($invoice->discount_amount ?? 0);
            $tax = (float) ($invoice->tax_amount ?? 0);

            return [
                'tax_type' => 'input',
                'source' => 'purchases',
                'source_label' => 'Supplier Invoice',
                'reference' => $invoice->invoice_number ?: 'INV-' . $invoice->id,
                'date' => optional($invoice->invoice_date ?: $invoice->created_at)->toDateString(),
                'branch' => $invoice->purchaseOrder?->branch?->name ?: 'Unassigned branch',
                'counterparty' => $invoice->supplier?->supplier_name ?: 'Supplier',
                'tax_rate' => self::DEFAULT_VAT_RATE,
                'taxable_amount' => round(max(0, $invoiceAmount - $discount), 2),
                'tax_amount' => round($tax, 2),
                'gross_amount' => round((float) ($invoice->net_amount ?? $invoiceAmount), 2),
                'status' => $invoice->payment_status ?: $invoice->status,
            ];
        });
    }

    private function applyDateFilter($query, Request $request, string $primaryColumn, ?string $fallbackColumn = null): void
    {
        if (!$request->filled('date_from') && !$request->filled('date_to')) {
            return;
        }

        if ($fallbackColumn) {
            $query->where(function ($date) use ($request, $primaryColumn, $fallbackColumn) {
                $date->where(function ($primary) use ($request, $primaryColumn) {
                    if ($request->filled('date_from')) {
                        $primary->whereDate($primaryColumn, '>=', $request->input('date_from'));
                    }
                    if ($request->filled('date_to')) {
                        $primary->whereDate($primaryColumn, '<=', $request->input('date_to'));
                    }
                })->orWhere(function ($fallback) use ($request, $primaryColumn, $fallbackColumn) {
                    $fallback->whereNull($primaryColumn);
                    if ($request->filled('date_from')) {
                        $fallback->whereDate('created_at', '>=', $request->input('date_from'));
                    }
                    if ($request->filled('date_to')) {
                        $fallback->whereDate('created_at', '<=', $request->input('date_to'));
                    }
                });
            });
            return;
        }

        if ($request->filled('date_from')) {
            $query->whereDate($primaryColumn, '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate($primaryColumn, '<=', $request->input('date_to'));
        }
    }
}
