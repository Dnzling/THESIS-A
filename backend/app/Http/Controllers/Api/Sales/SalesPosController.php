<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Inventory\BranchInventory;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesOrderItem;
use App\Models\Sales\SalesPayment;
use App\Services\Payment\PaymongoService;
use App\Services\Sales\SalesOrderSettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesPosController extends Controller
{
    public function __construct(
        private readonly SalesOrderSettlementService $settlementService,
        private readonly PaymongoService $paymongoService
    ) {
    }

    public function products(Request $request): JsonResponse
    {
        $user = $request->user();
        $storeId = (int) ($user->store_id ?? 0);
        $branchId = (int) ($request->input('branch_id', $user->branch_id ?? 0));

        $query = BranchInventory::query()
            ->with(['product:id,product_name,sku,base_price,discounted_price,is_active', 'variation:id,variation_name'])
            ->where('store_id', $storeId)
            ->when($branchId > 0, fn($q) => $q->where('branch_id', $branchId))
            ->where('quantity_available', '>', 0)
            ->whereHas('product', fn($q) => $q->where('is_active', true)->whereNull('deleted_at'));

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $rows = $query->orderByDesc('quantity_available')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'customer_name' => 'nullable|string|max:150',
            'customer_phone' => 'nullable|string|max:50',
            'payment_method' => 'required|in:cash,card,gcash,cod',
            'discount_amount' => 'nullable|numeric|min:0',
            'amount_tendered' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'return_url' => 'nullable|url|max:500',
            'delivery_required' => 'nullable|boolean',
            'delivery_address' => 'required_if:delivery_required,true|string|max:1000',
            'delivery_notes' => 'nullable|string|max:1000',
            'delivery_province' => 'nullable|string|max:150',
            'delivery_city' => 'nullable|string|max:150',
            'delivery_barangay' => 'nullable|string|max:150',
            'delivery_address_line' => 'nullable|string|max:255',
            'delivery_latitude' => 'nullable|numeric',
            'delivery_longitude' => 'nullable|numeric',
            'delivery_email' => 'nullable|email|max:150',
            'items' => 'required|array|min:1',
            'items.*.branch_inventory_id' => 'required|exists:branch_inventory,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $storeId = (int) ($user->store_id ?? 0);
        $branchId = (int) ($validated['branch_id'] ?? $user->branch_id ?? 0);
        if (!$storeId) {
            return response()->json(['success' => false, 'message' => 'No store assigned.'], 422);
        }

        $order = DB::transaction(function () use ($validated, $storeId, $branchId, $user) {
            $subtotal = 0.0;
            $tax = 0.0;
            $discount = (float) ($validated['discount_amount'] ?? 0);

            $order = SalesOrder::create([
                'store_id' => $storeId,
                'branch_id' => $branchId ?: null,
                'order_number' => $this->nextOrderNumber(),
                'status' => 'pending_payment',
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'pending',
                'subtotal' => 0,
                'discount_amount' => $discount,
                'tax_amount' => 0,
                'total_amount' => 0,
                'amount_tendered' => (float) ($validated['amount_tendered'] ?? 0),
                'change_amount' => 0,
                'notes' => $validated['notes'] ?? null,
                'delivery_required' => (bool) ($validated['delivery_required'] ?? false),
                'delivery_address' => $validated['delivery_address'] ?? null,
                'delivery_notes' => $validated['delivery_notes'] ?? null,
                'delivery_province' => $validated['delivery_province'] ?? null,
                'delivery_city' => $validated['delivery_city'] ?? null,
                'delivery_barangay' => $validated['delivery_barangay'] ?? null,
                'delivery_address_line' => $validated['delivery_address_line'] ?? null,
                'delivery_latitude' => $validated['delivery_latitude'] ?? null,
                'delivery_longitude' => $validated['delivery_longitude'] ?? null,
                'delivery_email' => $validated['delivery_email'] ?? null,
                'created_by' => $user->id,
            ]);

            foreach ($validated['items'] as $itemRow) {
                $inv = BranchInventory::query()
                    ->with(['product:id,product_name,sku,base_price,discounted_price,tax_rate'])
                    ->lockForUpdate()
                    ->findOrFail((int) $itemRow['branch_inventory_id']);

                if ((int) $inv->store_id !== $storeId) {
                    abort(response()->json(['success' => false, 'message' => 'Invalid inventory item for this store.'], 422));
                }

                $qty = (int) $itemRow['quantity'];
                if ((int) $inv->quantity_available < $qty) {
                    abort(response()->json(['success' => false, 'message' => "Insufficient stock for {$inv->product?->product_name}."], 422));
                }

                $unitPrice = (float) ($inv->product?->discounted_price ?? $inv->product?->base_price ?? 0);
                $lineSubtotal = $qty * $unitPrice;
                $lineTax = 0.0;
                $lineTotal = $lineSubtotal + $lineTax;

                SalesOrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $inv->product_id,
                    'variation_id' => $inv->variation_id,
                    'branch_inventory_id' => $inv->id,
                    'product_name' => $inv->product?->product_name ?? 'Product',
                    'sku' => $inv->product?->sku,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'line_discount' => 0,
                    'line_tax' => $lineTax,
                    'line_total' => $lineTotal,
                ]);

                $subtotal += $lineSubtotal;
                $tax += $lineTax;
            }

            $total = max(0, $subtotal + $tax - $discount);
            $tendered = (float) ($order->amount_tendered ?? 0);
            $change = max(0, $tendered - $total);

            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'total_amount' => $total,
                'change_amount' => $change,
            ]);

            $order = $order->fresh(['items', 'branch']);

            return $order;
        });

        if (in_array($validated['payment_method'], ['cash', 'card', 'cod'], true)) {
            $manualPayment = SalesPayment::create([
                'store_id' => $storeId,
                'branch_id' => $branchId ?: null,
                'sales_order_id' => $order->id,
                'payment_provider' => 'manual',
                'payment_method' => $validated['payment_method'],
                'currency' => 'PHP',
                'amount' => (float) ($order->total_amount ?? 0),
                'status' => 'paid',
                'provider_reference' => 'MANUAL-' . strtoupper($validated['payment_method']) . '-' . now()->format('YmdHis'),
                'paid_at' => now(),
                'metadata' => ['source' => 'sales_pos'],
                'created_by' => $user->id,
            ]);

            $settled = $this->settlementService->settlePaid(
                $order,
                $validated['payment_method'],
                (string) $manualPayment->provider_reference,
                $manualPayment
            );

            return response()->json([
                'success' => true,
                'message' => 'POS checkout completed.',
                'data' => $settled,
            ], 201);
        }

        $onlinePayment = SalesPayment::create([
            'store_id' => $storeId,
            'branch_id' => $branchId ?: null,
            'sales_order_id' => $order->id,
            'payment_provider' => 'paymongo',
            'payment_method' => $validated['payment_method'],
            'currency' => 'PHP',
            'amount' => (float) ($order->total_amount ?? 0),
            'status' => 'pending',
            'metadata' => ['source' => 'sales_pos'],
            'created_by' => $user->id,
        ]);

        $amount = (int) round(((float) $order->total_amount) * 100);
        $intentPayload = $this->paymongoService->createIntent([
            'data' => [
                'attributes' => [
                    'amount' => max(1, $amount),
                    'currency' => 'PHP',
                    'capture_type' => 'automatic',
                    'description' => "POS Order {$order->order_number}",
                    'statement_descriptor' => "POS {$order->order_number}",
                    'payment_method_allowed' => ['gcash'],
                    'metadata' => [
                        'sales_order_id' => (string) $order->id,
                        'payment_id' => (string) $onlinePayment->id,
                    ],
                ],
            ],
        ]);

        $intentId = data_get($intentPayload, 'data.id');
        if (!$intentId) {
            $onlinePayment->update(['status' => 'failed', 'metadata' => ['paymongo_error' => data_get($intentPayload, 'errors')]]);

            return response()->json([
                'success' => false,
                'message' => data_get($intentPayload, 'errors.0.detail', 'Failed to initialize GCash checkout.'),
                'errors' => data_get($intentPayload, 'errors', []),
            ], 422);
        }

        $fallbackPhone = preg_replace('/\D+/', '', (string) ($validated['customer_phone'] ?? '09170000000'));
        $paymentMethodPayload = $this->paymongoService->createPaymentMethod([
            'data' => [
                'attributes' => [
                    'type' => 'gcash',
                    'billing' => [
                        'name' => (string) ($validated['customer_name'] ?? trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')) ?: 'Sales Customer'),
                        'email' => (string) ($user->email ?? ('sales+' . $order->id . '@local.test')),
                        'phone' => $fallbackPhone ?: '09170000000',
                    ],
                ],
            ],
        ]);

        $paymentMethodId = data_get($paymentMethodPayload, 'data.id');
        if (!$paymentMethodId) {
            $onlinePayment->update(['status' => 'failed', 'provider_reference' => $intentId]);

            return response()->json([
                'success' => false,
                'message' => data_get($paymentMethodPayload, 'errors.0.detail', 'Unable to create GCash payment method.'),
                'errors' => data_get($paymentMethodPayload, 'errors', []),
            ], 422);
        }

        $attachPayload = $this->paymongoService->attachIntent($intentId, [
            'data' => [
                'attributes' => [
                    'payment_method' => $paymentMethodId,
                    'return_url' => $validated['return_url'] ?? rtrim(config('app.url'), '/') . '/sales/pos',
                ],
            ],
        ]);

        $redirectUrl = data_get($attachPayload, 'data.attributes.next_action.redirect.url');
        if (!$redirectUrl) {
            $onlinePayment->update(['status' => 'failed', 'provider_reference' => $intentId]);

            return response()->json([
                'success' => false,
                'message' => data_get($attachPayload, 'errors.0.detail', 'Unable to open GCash checkout.'),
                'errors' => data_get($attachPayload, 'errors', []),
            ], 422);
        }

        $onlinePayment->update([
            'provider_reference' => $intentId,
            'status' => data_get($attachPayload, 'data.attributes.status', 'processing'),
            'checkout_url' => $redirectUrl,
            'metadata' => [
                'payment_method_id' => $paymentMethodId,
                'source' => 'sales_pos',
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order created. Continue checkout to complete payment.',
            'data' => $order->fresh(['items', 'branch', 'payment']),
            'checkout_url' => $redirectUrl,
            'payment_intent_id' => $intentId,
        ], 201);
    }

    public function orders(Request $request): JsonResponse
    {
        $query = SalesOrder::query()
            ->with([
                'branch:id,name',
                'creator:id,fname,lname',
                'payment:id,sales_order_id,payment_provider,payment_method,status,provider_reference,checkout_url',
                'delivery:id,sales_order_id,status,tracking_number,driver_user_id,scheduled_delivery_at',
            ])
            ->withCount('items');
        $this->applyStoreScope($request, $query);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $rows = $query->orderByDesc('created_at')->paginate((int) $request->input('per_page', 20));
        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $query = SalesOrder::query()->with([
            'branch:id,name',
            'creator:id,fname,lname,email',
            'items.product:id,product_name,sku',
            'items.variation:id,variation_name',
            'payment',
            'receipt',
            'delivery',
        ]);
        $this->applyStoreScope($request, $query);
        $order = $query->findOrFail($id);

        return response()->json(['success' => true, 'data' => $order]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $query = SalesOrder::query();
        $this->applyStoreScope($request, $query);

        $today = (clone $query)->whereDate('created_at', now()->toDateString());
        $month = (clone $query)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);

        $paymentQuery = SalesPayment::query();
        $this->applyStoreScope($request, $paymentQuery);

        return response()->json([
            'success' => true,
            'data' => [
                'today_orders' => (clone $today)->count(),
                'today_sales' => (float) (clone $today)->sum('total_amount'),
                'month_orders' => (clone $month)->count(),
                'month_sales' => (float) (clone $month)->sum('total_amount'),
                'today_paid' => (float) (clone $paymentQuery)->whereDate('paid_at', now()->toDateString())->where('status', 'paid')->sum('amount'),
                'pending_payments' => (clone $paymentQuery)->whereIn('status', ['pending', 'processing', 'awaiting_payment_method'])->count(),
                'payments_by_method' => (clone $paymentQuery)
                    ->select('payment_method', DB::raw('COUNT(*) as total'))
                    ->groupBy('payment_method')
                    ->get(),
                'recent_orders' => (clone $query)->with('branch:id,name')->orderByDesc('created_at')->limit(8)->get(),
            ],
        ]);
    }

    public function paymentAnalytics(Request $request): JsonResponse
    {
        $paymentQuery = SalesPayment::query();
        $this->applyStoreScope($request, $paymentQuery);

        $from = $request->date('from', now()->subDays(30)->startOfDay())?->startOfDay() ?? now()->subDays(30)->startOfDay();
        $to = $request->date('to', now()->endOfDay())?->endOfDay() ?? now()->endOfDay();

        $rangeQuery = (clone $paymentQuery)->whereBetween('created_at', [$from, $to]);
        $paidCount = (clone $rangeQuery)->where('status', 'paid')->count();
        $allCount = (clone $rangeQuery)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'total_payments' => $allCount,
                'paid_payments' => $paidCount,
                'failed_payments' => (clone $rangeQuery)->where('status', 'failed')->count(),
                'pending_payments' => (clone $rangeQuery)->whereIn('status', ['pending', 'processing', 'awaiting_payment_method'])->count(),
                'paid_amount' => (float) (clone $rangeQuery)->where('status', 'paid')->sum('amount'),
                'conversion_rate' => $allCount > 0 ? round(($paidCount / $allCount) * 100, 2) : 0,
                'method_breakdown' => (clone $rangeQuery)
                    ->select('payment_method', DB::raw('COUNT(*) as total'), DB::raw('SUM(CASE WHEN status = "paid" THEN amount ELSE 0 END) as paid_amount'))
                    ->groupBy('payment_method')
                    ->get(),
                'daily_paid' => (clone $rangeQuery)
                    ->where('status', 'paid')
                    ->select(DB::raw('DATE(COALESCE(paid_at, created_at)) as date'), DB::raw('SUM(amount) as total'))
                    ->groupBy(DB::raw('DATE(COALESCE(paid_at, created_at))'))
                    ->orderBy('date')
                    ->get(),
            ],
        ]);
    }

    public function syncPayment(Request $request, int $id): JsonResponse
    {
        $query = SalesOrder::query()->with('payment');
        $this->applyStoreScope($request, $query);
        $order = $query->findOrFail($id);

        $payment = $order->payment;
        if (!$payment || $payment->payment_provider !== 'paymongo' || !$payment->provider_reference) {
            return response()->json([
                'success' => false,
                'message' => 'No PayMongo payment is linked to this order.',
            ], 422);
        }

        $payload = $this->paymongoService->retrieveIntent((string) $payment->provider_reference);
        $status = (string) data_get($payload, 'data.attributes.status', '');

        $statusMap = [
            'awaiting_payment_method' => 'awaiting_payment_method',
            'awaiting_next_action' => 'processing',
            'processing' => 'processing',
            'succeeded' => 'paid',
            'failed' => 'failed',
            'cancelled' => 'cancelled',
        ];

        $mapped = $statusMap[$status] ?? $payment->status;
        $payment->update([
            'status' => $mapped,
            'metadata' => array_merge((array) $payment->metadata, ['paymongo_status' => $status]),
            'paid_at' => $mapped === 'paid' ? now() : $payment->paid_at,
        ]);

        if ($mapped === 'paid') {
            try {
                $this->settlementService->settlePaid($order, (string) $payment->payment_method, (string) $payment->provider_reference, $payment);
            } catch (\Throwable $e) {
                $this->settlementService->markPaymentFailed($order, $payment);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment was captured but settlement failed: ' . $e->getMessage(),
                ], 422);
            }
        } elseif ($mapped === 'failed') {
            $this->settlementService->markPaymentFailed($order, $payment);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment status synchronized.',
            'data' => $order->fresh(['items', 'payment', 'receipt', 'branch']),
            'paymongo' => $payload,
        ]);
    }

    private function applyStoreScope(Request $request, $query): void
    {
        $user = $request->user();
        if (!$user->hasRole('super_admin')) {
            $query->where('store_id', $user->store_id);
            return;
        }

        if ($request->filled('store_id')) {
            $query->where('store_id', (int) $request->input('store_id'));
        }
    }

    private function nextOrderNumber(): string
    {
        $prefix = 'POS-' . now()->format('Ymd') . '-';
        $last = SalesOrder::query()->where('order_number', 'like', "{$prefix}%")->orderByDesc('id')->value('order_number');
        $seq = 1;
        if ($last && preg_match('/(\d+)$/', (string) $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

}
