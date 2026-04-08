<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\EcommerceOrder;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesReceipt;
use Illuminate\Support\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceReceivablesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search', ''));
        $status = strtolower((string) $request->input('status', ''));
        $source = strtolower((string) $request->input('source', 'all'));

        $rows = collect();

        if (in_array($source, ['all', 'sales'], true)) {
            $rows = $rows->merge($this->salesRows($request));
        }

        if (in_array($source, ['all', 'ecommerce'], true)) {
            $rows = $rows->merge($this->ecommerceRows($request));
        }

        if ($search !== '') {
            $needle = strtolower($search);
            $rows = $rows->filter(function (array $row) use ($needle) {
                return str_contains(strtolower(implode(' ', [
                    $row['reference'] ?? '',
                    $row['customer'] ?? '',
                    $row['status'] ?? '',
                    $row['source_type'] ?? '',
                ])), $needle);
            });
        }

        if ($status !== '') {
            $rows = $rows->filter(fn (array $row) => strtolower((string) ($row['status'] ?? '')) === $status);
        }

        $rows = $rows->sortByDesc('created_at')->values();

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }

    public function show(Request $request, string $source, int $id): JsonResponse
    {
        $source = strtolower($source);
        if (!in_array($source, ['sales', 'ecommerce'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid receivable source.',
            ], 422);
        }

        if ($source === 'sales') {
            $receipt = $this->findSalesReceipt($request, $id);
            $order = $receipt?->order ?: $this->findSalesOrder($request, $id);

            return response()->json([
                'success' => true,
                'data' => [
                    'source_type' => 'sales',
                    'id' => $receipt?->id ?: $order->id,
                    'reference' => $receipt?->receipt_number ?: ($order->order_number ?: ('SO-' . $order->id)),
                    'order_reference' => $order->order_number,
                    'status' => $this->mapPaymentStatus($order->payment_status, $order->status),
                    'order_status' => $order->status,
                    'receipt' => [
                        'id' => $receipt?->id,
                        'receipt_number' => $receipt?->receipt_number,
                        'amount' => (float) ($receipt?->amount ?? 0),
                        'currency' => $receipt?->currency,
                        'payment_method' => $receipt?->payment_method,
                        'payment_reference' => $receipt?->payment_reference,
                        'issued_at' => $receipt?->issued_at,
                        'payload' => $receipt?->payload,
                    ],
                    'customer' => [
                        'name' => data_get($receipt?->payload, 'customer_name', $order->customer_name ?: 'Walk-in Customer'),
                        'phone' => $order->customer_phone,
                        'email' => $order->delivery_email,
                    ],
                    'billing' => [
                        'address' => $order->delivery_address,
                        'province' => $order->delivery_province,
                        'city' => $order->delivery_city,
                        'barangay' => $order->delivery_barangay,
                        'address_line' => $order->delivery_address_line,
                    ],
                    'payment' => [
                        'method' => $receipt?->payment_method ?: $order->payment_method,
                        'channel' => $order->payment_channel,
                        'status' => $order->payment_status,
                        'reference' => $receipt?->payment_reference ?: $order->payment_reference,
                        'paid_at' => $receipt?->issued_at ?: $order->paid_at,
                    ],
                    'amounts' => [
                        'subtotal' => (float) ($order->subtotal ?? 0),
                        'discount' => (float) ($order->discount_amount ?? 0),
                        'tax' => (float) ($order->tax_amount ?? 0),
                        'shipping_fee' => 0,
                        'total' => (float) ($order->total_amount ?? 0),
                    ],
                    'delivery' => [
                        'id' => $order->delivery?->id,
                        'status' => $order->delivery?->status,
                        'tracking_number' => $order->delivery?->tracking_number,
                        'courier_name' => $order->delivery?->courier_name,
                        'courier_contact' => $order->delivery?->courier_contact,
                        'distance_km' => (float) ($order->delivery?->distance_km ?? 0),
                        'per_km_charge' => (float) ($order->delivery?->per_km_charge ?? 0),
                        'estimated_fee' => (float) ($order->delivery?->estimated_fee ?? 0),
                    ],
                    'dates' => [
                        'created_at' => $receipt?->issued_at ?: $order->created_at,
                        'due_date' => optional($receipt?->issued_at ?: $order->created_at)->copy()?->addDays(7),
                    ],
                    'items' => $order->items->map(fn ($item) => [
                        'id' => $item->id,
                        'sku' => $item->sku,
                        'name' => $item->product_name,
                        'quantity' => (float) ($item->quantity ?? 0),
                        'unit_price' => (float) ($item->unit_price ?? 0),
                        'line_total' => (float) ($item->line_total ?? 0),
                    ])->values(),
                ],
            ]);
        }

        $order = $this->findEcommerceOrder($request, $id);

        return response()->json([
            'success' => true,
            'data' => [
                'source_type' => 'ecommerce',
                'id' => $order->id,
                'reference' => $order->order_number ?: ('EC-' . $order->id),
                'status' => $this->mapPaymentStatus($order->payment_status, $order->status),
                'order_status' => $order->status,
                'customer' => [
                    'name' => $order->shipping_name ?: 'Ecommerce Customer',
                    'phone' => $order->shipping_phone,
                    'email' => $order->shipping_email,
                ],
                'billing' => [
                    'address' => $order->shipping_address,
                    'province' => null,
                    'city' => null,
                    'barangay' => null,
                    'address_line' => null,
                ],
                'payment' => [
                    'method' => $order->payment_method,
                    'channel' => null,
                    'status' => $order->payment_status,
                    'reference' => null,
                    'paid_at' => null,
                ],
                'amounts' => [
                    'subtotal' => (float) ($order->subtotal ?? 0),
                    'discount' => (float) ($order->discount_amount ?? 0),
                    'tax' => (float) ($order->tax_amount ?? 0),
                    'shipping_fee' => (float) ($order->shipping_fee ?? 0),
                    'total' => (float) ($order->total_amount ?? 0),
                ],
                'delivery' => [
                    'id' => $order->delivery?->id,
                    'status' => $order->delivery?->status,
                    'tracking_number' => $order->delivery?->tracking_number,
                    'courier_name' => $order->delivery?->courier_name,
                    'courier_contact' => $order->delivery?->courier_contact,
                    'distance_km' => (float) ($order->delivery?->distance_km ?? 0),
                    'per_km_charge' => (float) ($order->delivery?->per_km_charge ?? 0),
                    'estimated_fee' => (float) ($order->delivery?->estimated_fee ?? 0),
                ],
                'dates' => [
                    'created_at' => $order->placed_at ?: $order->created_at,
                    'due_date' => optional($order->placed_at ?: $order->created_at)->copy()?->addDays(7),
                ],
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'sku' => $item->sku,
                    'name' => $item->product_name,
                    'quantity' => (float) ($item->quantity ?? 0),
                    'unit_price' => (float) ($item->unit_price ?? 0),
                    'line_total' => (float) ($item->line_total ?? 0),
                ])->values(),
            ],
        ]);
    }

    private function salesRows(Request $request): Collection
    {
        $query = SalesOrder::query();
        $this->applySalesTenantScope($request, $query);

        return $query
            ->select([
                'id',
                'order_number',
                'customer_name',
                'total_amount',
                'payment_status',
                'status',
                'created_at',
                'paid_at',
            ])
            ->get()
            ->map(function (SalesOrder $order) {
                $createdAt = $order->created_at;
                $dueDate = $createdAt ? $createdAt->copy()->addDays(7) : null;

                return [
                    'source_type' => 'sales',
                    'id' => $order->id,
                    'reference' => $order->order_number ?: ('SO-' . $order->id),
                    'customer' => $order->customer_name ?: 'Walk-in Customer',
                    'amount' => (float) ($order->total_amount ?? 0),
                    'due_date' => $dueDate?->toDateString(),
                    'status' => $this->mapPaymentStatus($order->payment_status, $order->status),
                    'created_at' => $createdAt,
                    'paid_at' => $order->paid_at,
                ];
            });
    }

    private function ecommerceRows(Request $request): Collection
    {
        $query = EcommerceOrder::query();
        $this->applyEcommerceTenantScope($request, $query);

        return $query
            ->select([
                'id',
                'order_number',
                'shipping_name',
                'total_amount',
                'payment_status',
                'status',
                'placed_at',
                'created_at',
            ])
            ->get()
            ->map(function (EcommerceOrder $order) {
                $createdAt = $order->placed_at ?: $order->created_at;
                $dueDate = $createdAt ? $createdAt->copy()->addDays(7) : null;

                return [
                    'source_type' => 'ecommerce',
                    'id' => $order->id,
                    'reference' => $order->order_number ?: ('EC-' . $order->id),
                    'customer' => $order->shipping_name ?: 'Ecommerce Customer',
                    'amount' => (float) ($order->total_amount ?? 0),
                    'due_date' => $dueDate?->toDateString(),
                    'status' => $this->mapPaymentStatus($order->payment_status, $order->status),
                    'created_at' => $createdAt,
                    'paid_at' => null,
                ];
            });
    }

    private function mapPaymentStatus(?string $paymentStatus, ?string $orderStatus): string
    {
        $payment = strtolower((string) $paymentStatus);
        $order = strtolower((string) $orderStatus);

        if (in_array($payment, ['paid', 'completed', 'settled'], true)) {
            return 'paid';
        }

        if (in_array($payment, ['partial', 'partially_paid'], true)) {
            return 'partial';
        }

        if (in_array($order, ['cancelled', 'canceled', 'voided'], true)) {
            return 'cancelled';
        }

        return 'pending';
    }

    private function resolveStoreId(Request $request): ?int
    {
        $user = $request->user();

        if (!$user->hasRole('super_admin')) {
            return $user->store_id ? (int) $user->store_id : null;
        }

        if ($request->filled('store_id')) {
            return (int) $request->input('store_id');
        }

        return $user->store_id ? (int) $user->store_id : null;
    }

    private function resolveBranchId(Request $request): ?int
    {
        $user = $request->user();

        if ($user->hasRole('super_admin') && $request->filled('branch_id')) {
            return (int) $request->input('branch_id');
        }

        return $user->employee?->branch_id ? (int) $user->employee->branch_id : null;
    }

    private function applyEcommerceTenantScope(Request $request, $query): void
    {
        $storeId = $this->resolveStoreId($request);
        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $branchId = $this->resolveBranchId($request);
        if ($branchId) {
            $query->where(function ($builder) use ($branchId) {
                $builder->where('assigned_branch_id', $branchId)
                    ->orWhereNull('assigned_branch_id');
            });
        }
    }

    private function applySalesTenantScope(Request $request, $query): void
    {
        $storeId = $this->resolveStoreId($request);
        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $branchId = $this->resolveBranchId($request);
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
    }

    private function findSalesOrder(Request $request, int $id): SalesOrder
    {
        $query = SalesOrder::query()->with(['items', 'delivery']);
        $this->applySalesTenantScope($request, $query);

        return $query->findOrFail($id);
    }

    private function findSalesReceipt(Request $request, int $id): ?SalesReceipt
    {
        $query = SalesReceipt::query()->with(['order.items', 'order.delivery']);

        $storeId = $this->resolveStoreId($request);
        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $branchId = $this->resolveBranchId($request);
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->where('sales_order_id', $id)->first();
    }

    private function findEcommerceOrder(Request $request, int $id): EcommerceOrder
    {
        $query = EcommerceOrder::query()->with(['items', 'delivery']);
        $this->applyEcommerceTenantScope($request, $query);

        return $query->findOrFail($id);
    }
}
