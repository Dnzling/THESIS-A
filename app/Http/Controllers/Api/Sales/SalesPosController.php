<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Inventory\BranchInventory;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesOrderItem;
use App\Models\Sales\SalesPayment;
use App\Models\Hr\Employee;
use App\Models\Store\Branch;
use App\Models\Store\Store;
use App\Models\Store\StoreDeliveryFeeSetting;
use App\Services\Payment\PaymongoService;
use App\Services\Sales\SalesOrderSettlementService;
use App\Services\Sales\OrderCommissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesPosController extends Controller
{
    private const VAT_RATE = 12.0;

    public function __construct(
        private readonly SalesOrderSettlementService $settlementService,
        private readonly PaymongoService $paymongoService,
        private readonly OrderCommissionService $commissionService
    ) {
    }

    public function products(Request $request): JsonResponse
    {
        $user = $request->user();
        $storeId = (int) ($user->store_id ?? 0);
        $branchId = (int) ($request->input('branch_id', $user->branch_id ?? 0));

        $query = BranchInventory::query()
            ->with(['product:id,product_name,sku,base_price,discounted_price,weight_kg,is_active', 'variation:id,variation_name'])
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

    public function estimateDeliveryFee(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'delivery_latitude' => 'required|numeric|between:-90,90',
            'delivery_longitude' => 'required|numeric|between:-180,180',
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

        [$subtotal, $totalWeight] = $this->resolveCartMetrics($validated['items'], $storeId, $branchId);
        [$originLatitude, $originLongitude] = $this->resolveDeliveryOrigin($storeId, $branchId);
        if ($originLatitude === null || $originLongitude === null) {
            return response()->json([
                'success' => false,
                'message' => 'The selected branch or store does not have coordinates for delivery calculation.',
            ], 422);
        }

        $distanceKm = $this->haversineKm(
            $originLatitude,
            $originLongitude,
            (float) $validated['delivery_latitude'],
            (float) $validated['delivery_longitude']
        );
        $estimate = $this->calculateStoreDeliveryFee($storeId, $subtotal, $distanceKm, $totalWeight);

        if (!($estimate['delivery_available'] ?? true)) {
            return response()->json([
                'success' => false,
                'message' => 'The delivery address is outside this store\'s maximum delivery distance.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $estimate,
        ]);
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
            'delivery_latitude' => 'nullable|numeric|between:-90,90',
            'delivery_longitude' => 'nullable|numeric|between:-180,180',
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

        if (($validated['delivery_required'] ?? false)
            && (!is_numeric($validated['delivery_latitude'] ?? null) || !is_numeric($validated['delivery_longitude'] ?? null))) {
            return response()->json([
                'success' => false,
                'message' => 'Pin the customer delivery location before checkout so the shipping fee can be calculated.',
            ], 422);
        }

        [$originLatitude, $originLongitude] = $this->resolveDeliveryOrigin($storeId, $branchId);
        if (($validated['delivery_required'] ?? false) && ($originLatitude === null || $originLongitude === null)) {
            return response()->json([
                'success' => false,
                'message' => 'The selected branch or store does not have coordinates for delivery calculation.',
            ], 422);
        }

        // Resolve employee id for created_by (InventoryTransaction.created_by references employees.id)
        $creatorEmployeeId = $user->employee?->id
            ?? Employee::where('user_id', $user->id)->value('id')
            ?? config('app.system_employee_id', 1);

        $order = DB::transaction(function () use ($validated, $storeId, $branchId, $user, $creatorEmployeeId, $originLatitude, $originLongitude) {
            $subtotal = 0.0;
            $tax = 0.0;
            $totalWeight = 0.0;
            $discount = (float) ($validated['discount_amount'] ?? 0);

            // Resolve employee id for created_by (InventoryTransaction.created_by references employees.id)
            $creatorEmployeeId = $user->employee?->id
                ?? Employee::where('user_id', $user->id)->value('id')
                ?? config('app.system_employee_id', 1);

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
                'shipping_fee' => 0,
                'delivery_distance_km' => null,
                'delivery_weight_kg' => 0,
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
                'created_by' => $creatorEmployeeId,
            ]);

            foreach ($validated['items'] as $itemRow) {
                $inv = BranchInventory::query()
                    ->with(['product:id,product_name,sku,base_price,discounted_price,weight_kg'])
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
                // POS selling prices are VAT-inclusive. Extract VAT for
                // reporting without increasing the amount paid by the buyer.
                $lineTax = $this->extractIncludedVat($lineSubtotal);
                $lineTotal = $lineSubtotal;

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
                $totalWeight += (float) ($inv->product?->weight_kg ?? 0) * $qty;
            }

            $netProductTotal = max(0, $subtotal - $discount);
            $tax = $this->extractIncludedVat($netProductTotal);
            $shippingFee = 0.0;
            $deliveryDistanceKm = null;

            if ((bool) ($validated['delivery_required'] ?? false)) {
                $deliveryDistanceKm = $this->haversineKm(
                    (float) $originLatitude,
                    (float) $originLongitude,
                    (float) $validated['delivery_latitude'],
                    (float) $validated['delivery_longitude']
                );
                $deliveryEstimate = $this->calculateStoreDeliveryFee(
                    $storeId,
                    $netProductTotal,
                    $deliveryDistanceKm,
                    $totalWeight
                );
                if (!($deliveryEstimate['delivery_available'] ?? true)) {
                    abort(response()->json([
                        'success' => false,
                        'message' => 'The delivery address is outside this store\'s maximum delivery distance.',
                    ], 422));
                }
                $shippingFee = (float) $deliveryEstimate['shipping_fee'];
            }

            $total = $netProductTotal + $shippingFee;
            $commission = $this->commissionService->calculate(
                $storeId,
                $subtotal,
                $discount,
                $total
            );
            $tendered = (float) ($order->amount_tendered ?? 0);
            $change = max(0, $tendered - $total);

            $order->update(array_merge([
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'shipping_fee' => $shippingFee,
                'delivery_distance_km' => $deliveryDistanceKm,
                'delivery_weight_kg' => $totalWeight,
                'total_amount' => $total,
                'change_amount' => $change,
            ], $commission));

            $order = $order->fresh(['items', 'branch']);

            return $order;
        });

        try {
            $this->notifyUsersByPermissions(
                $storeId,
                ['sales.pos.view', 'sales.pos.manage', 'sales.orders.view'],
                [
                    'store_id' => $storeId,
                    'branch_id' => $branchId ?: null,
                    'module' => 'sales',
                    'entity_type' => 'sales_order',
                    'entity_id' => $order->id,
                    'action' => 'created',
                    'title' => 'New In-Store Order Received',
                    'message' => "POS order {$order->order_number} has been created.",
                    'severity' => 'info',
                    'link' => "/sales/pos/orders/{$order->id}",
                ],
                [(int) $user->id]
            );
        } catch (\Throwable $exception) {
            report($exception);
        }

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
                'created_by' => $creatorEmployeeId,
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
            'created_by' => $creatorEmployeeId,
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

    public function receiptPdf(Request $request, int $id)
    {
        $query = SalesOrder::query()->with([
            'store:id,name,address,city,province,phone,email',
            'branch:id,name,address,city,province',
            'items:id,order_id,product_name,sku,quantity,unit_price,line_total',
            'receipt',
            'payment',
            'creator:id,fname,lname',
        ]);
        $this->applyStoreScope($request, $query);
        $order = $query->findOrFail($id);

        $data = [
            'order' => $order,
            'store' => $order->store,
            'branch' => $order->branch,
            'items' => $order->items,
            'receipt' => $order->receipt,
            'payment' => $order->payment,
            'issued_by' => $order->creator,
        ];

        $pdf = \PDF::loadView('sales.pos-receipt-pdf', $data)->setPaper('a4', 'portrait');
        $filename = ($order->receipt_number ?: $order->order_number ?: ('POS-' . $order->id)) . '.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    public function sendToLogistics(Request $request, int $id): JsonResponse
    {
        $query = SalesOrder::query()->with(['delivery']);
        $this->applyStoreScope($request, $query);
        $order = $query->findOrFail($id);

        if (!$order->delivery_required) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery is not enabled for this order.',
            ], 422);
        }

        if (empty($order->delivery_address)) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery address is required before sending to logistics.',
            ], 422);
        }

        if ((string) $order->payment_status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Order must be paid before sending to logistics.',
            ], 422);
        }

        if ($order->delivery) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery is already assigned for this order.',
            ], 422);
        }

        $notes = trim((string) ($order->notes ?? ''));
        $line = '[' . now()->format('Y-m-d H:i') . '] Sent to logistics for delivery assignment.';
        $order->notes = $notes === '' ? $line : $notes . PHP_EOL . $line;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Order queued for logistics.',
            'data' => $order->fresh(['delivery', 'branch']),
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $todayDate = now()->toDateString();
        $monthNow = now()->month;
        $yearNow = now()->year;

        $query = SalesOrder::query();
        $this->applyStoreScope($request, $query);
        $todaySalesOrders = (clone $query)->whereDate('created_at', $todayDate);
        $monthSalesOrders = (clone $query)->whereMonth('created_at', $monthNow)->whereYear('created_at', $yearNow);

        $paymentQuery = SalesPayment::query();
        $this->applyStoreScope($request, $paymentQuery);

        $todayPosOrdersCount = (clone $todaySalesOrders)->count();
        $monthPosOrdersCount = (clone $monthSalesOrders)->count();
        $todayPosSales = (float) (clone $todaySalesOrders)->whereIn('payment_status', ['paid', 'succeeded', 'completed'])->sum('total_amount');
        $monthPosSales = (float) (clone $monthSalesOrders)->whereIn('payment_status', ['paid', 'succeeded', 'completed'])->sum('total_amount');

        $todayEcomOrdersCount = 0;
        $monthEcomOrdersCount = 0;
        $todayEcomSales = 0.0;
        $monthEcomSales = 0.0;
        $ecomMethods = collect();
        $ecomPendingPayments = 0;

        if (DB::getSchemaBuilder()->hasTable('ecommerce_orders')) {
            $ecomQuery = DB::table('ecommerce_orders');
            $this->applyStoreScope($request, $ecomQuery);

            $todayEcomOrdersCount = (clone $ecomQuery)->whereDate('created_at', $todayDate)->count();
            $monthEcomOrdersCount = (clone $ecomQuery)->whereMonth('created_at', $monthNow)->whereYear('created_at', $yearNow)->count();

            $todayEcomSales = (float) (clone $ecomQuery)
                ->whereDate('created_at', $todayDate)
                ->whereIn(DB::raw('LOWER(COALESCE(payment_status, status, "pending"))'), ['paid', 'succeeded', 'completed'])
                ->sum('total_amount');

            $monthEcomSales = (float) (clone $ecomQuery)
                ->whereMonth('created_at', $monthNow)
                ->whereYear('created_at', $yearNow)
                ->whereIn(DB::raw('LOWER(COALESCE(payment_status, status, "pending"))'), ['paid', 'succeeded', 'completed'])
                ->sum('total_amount');

            $ecomPendingPayments = (int) (clone $ecomQuery)
                ->whereIn(DB::raw('LOWER(COALESCE(payment_status, status, "pending"))'), ['pending', 'processing', 'awaiting_payment_method'])
                ->count();

            $ecomMethods = (clone $ecomQuery)
                ->select('payment_method', DB::raw('COUNT(*) as total'))
                ->whereNotNull('payment_method')
                ->groupBy('payment_method')
                ->get();
        }

        $posMethods = (clone $paymentQuery)
            ->select('payment_method', DB::raw('COUNT(*) as total'))
            ->groupBy('payment_method')
            ->get();

        $paymentsByMethod = $posMethods
            ->concat($ecomMethods)
            ->groupBy('payment_method')
            ->map(function ($rows, $method) {
                return [
                    'payment_method' => $method,
                    'total' => (int) collect($rows)->sum('total'),
                ];
            })
            ->values();

        $todayPaid = (float) (clone $paymentQuery)
            ->whereDate(DB::raw('COALESCE(paid_at, created_at)'), $todayDate)
            ->where('status', 'paid')
            ->sum('amount');

        if (DB::getSchemaBuilder()->hasTable('ecommerce_orders')) {
            $ecomTodayPaid = (float) DB::table('ecommerce_orders')
                ->when(
                    !$request->user()->hasRole('super_admin'),
                    fn($q) => $q->where('store_id', $request->user()->store_id),
                    fn($q) => $request->filled('store_id')
                        ? $q->where('store_id', (int) $request->input('store_id'))
                        : $q
                )
                ->whereDate('created_at', $todayDate)
                ->whereIn(DB::raw('LOWER(COALESCE(payment_status, status, "pending"))'), ['paid', 'succeeded', 'completed'])
                ->sum('total_amount');
            $todayPaid += $ecomTodayPaid;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'today_orders' => $todayPosOrdersCount + $todayEcomOrdersCount,
                'today_sales' => $todayPosSales + $todayEcomSales,
                'month_orders' => $monthPosOrdersCount + $monthEcomOrdersCount,
                'month_sales' => $monthPosSales + $monthEcomSales,
                'today_paid' => $todayPaid,
                'pending_payments' => (clone $paymentQuery)->whereIn('status', ['pending', 'processing', 'awaiting_payment_method'])->count() + $ecomPendingPayments,
                'payments_by_method' => $paymentsByMethod,
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
                'message' => 'No Online Payment payment is linked to this order.',
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

    private function resolveCartMetrics(array $items, int $storeId, int $branchId): array
    {
        $subtotal = 0.0;
        $totalWeight = 0.0;

        foreach ($items as $itemRow) {
            $inventory = BranchInventory::query()
                ->with(['product:id,base_price,discounted_price,weight_kg'])
                ->where('store_id', $storeId)
                ->when($branchId > 0, fn($query) => $query->where('branch_id', $branchId))
                ->find((int) $itemRow['branch_inventory_id']);

            if (!$inventory) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'A selected product is not available in this store branch.',
                ], 422));
            }

            $quantity = (int) $itemRow['quantity'];
            $unitPrice = (float) ($inventory->product?->discounted_price ?? $inventory->product?->base_price ?? 0);
            $subtotal += $unitPrice * $quantity;
            $totalWeight += (float) ($inventory->product?->weight_kg ?? 0) * $quantity;
        }

        return [round($subtotal, 2), round($totalWeight, 2)];
    }

    private function resolveDeliveryOrigin(int $storeId, int $branchId): array
    {
        if ($branchId > 0) {
            $branch = Branch::query()
                ->where('store_id', $storeId)
                ->find($branchId);

            if ($branch && is_numeric($branch->latitude) && is_numeric($branch->longitude)) {
                return [(float) $branch->latitude, (float) $branch->longitude];
            }
        }

        $store = Store::query()->find($storeId);
        if ($store && is_numeric($store->latitude) && is_numeric($store->longitude)) {
            return [(float) $store->latitude, (float) $store->longitude];
        }

        return [null, null];
    }

    private function calculateStoreDeliveryFee(
        int $storeId,
        float $subtotal,
        float $distanceKm,
        float $totalWeightKg
    ): array {
        $setting = StoreDeliveryFeeSetting::query()->where('store_id', $storeId)->first()
            ?? new StoreDeliveryFeeSetting([
                'store_id' => $storeId,
                'is_active' => true,
                'base_fee' => 100,
                'per_km_fee' => 10,
                'per_kg_fee' => 0,
                'min_delivery_fee' => 80,
                'free_shipping_min_order' => null,
                'max_delivery_distance_km' => null,
            ]);

        $maxDistance = $setting->max_delivery_distance_km;
        if (!is_null($maxDistance) && $distanceKm > (float) $maxDistance) {
            return [
                'delivery_available' => false,
                'shipping_fee' => 0.0,
                'distance_km' => round($distanceKm, 2),
                'total_weight_kg' => round($totalWeightKg, 2),
                'max_delivery_distance_km' => (float) $maxDistance,
            ];
        }

        if (!(bool) $setting->is_active) {
            return [
                'delivery_available' => true,
                'shipping_fee' => 0.0,
                'distance_km' => round($distanceKm, 2),
                'total_weight_kg' => round($totalWeightKg, 2),
                'breakdown' => [
                    'base_fee' => 0.0,
                    'distance_fee' => 0.0,
                    'weight_fee' => 0.0,
                    'minimum_applied' => false,
                    'free_shipping_applied' => false,
                ],
            ];
        }

        $baseFee = (float) $setting->base_fee;
        $distanceFee = round($distanceKm * (float) $setting->per_km_fee, 2);
        $weightFee = round($totalWeightKg * (float) $setting->per_kg_fee, 2);
        $freeShippingApplied = !is_null($setting->free_shipping_min_order)
            && $subtotal >= (float) $setting->free_shipping_min_order;
        $rawFee = $baseFee + $distanceFee + $weightFee;
        $minimumApplied = !$freeShippingApplied && $rawFee < (float) $setting->min_delivery_fee;
        $shippingFee = $freeShippingApplied
            ? 0.0
            : max($rawFee, (float) $setting->min_delivery_fee);

        return [
            'delivery_available' => true,
            'shipping_fee' => round($shippingFee, 2),
            'distance_km' => round($distanceKm, 2),
            'total_weight_kg' => round($totalWeightKg, 2),
            'breakdown' => [
                'base_fee' => round($baseFee, 2),
                'distance_fee' => $distanceFee,
                'weight_fee' => $weightFee,
                'minimum_applied' => $minimumApplied,
                'free_shipping_applied' => $freeShippingApplied,
            ],
        ];
    }

    private function haversineKm(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float
    {
        $earthRadiusKm = 6371;
        $latitudeDelta = deg2rad($toLatitude - $fromLatitude);
        $longitudeDelta = deg2rad($toLongitude - $fromLongitude);
        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($fromLatitude)) * cos(deg2rad($toLatitude)) * sin($longitudeDelta / 2) ** 2;

        return round($earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a)), 2);
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

    private function extractIncludedVat(float $vatInclusiveAmount): float
    {
        if ($vatInclusiveAmount <= 0) {
            return 0.0;
        }

        return round($vatInclusiveAmount - ($vatInclusiveAmount / (1 + (self::VAT_RATE / 100))), 2);
    }

}
