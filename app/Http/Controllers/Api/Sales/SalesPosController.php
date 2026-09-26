<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Inventory\BranchInventory;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesOrderDelivery;
use App\Models\Sales\SalesOrderDeliveryLog;
use App\Models\Sales\SalesOrderItem;
use App\Models\Sales\SalesPayment;
use App\Models\Hr\Employee;
use App\Models\Store\Branch;
use App\Models\Store\StoreDeliveryFeeSetting;
use App\Models\Store\Store;
use App\Services\Payment\PaymongoService;
use App\Services\Sales\SalesOrderSettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Ecommerce\EcommerceOrder;

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
            ->with(['product:id,product_name,sku,base_price,discounted_price,is_active,product_type', 'variation:id,variation_name,variation_sku'])
            ->where('store_id', $storeId)
            ->when($branchId > 0, fn($q) => $q->where('branch_id', $branchId))
            ->where('quantity_available', '>', 0)
            ->whereHas('product', fn($q) => $q
                ->where('product_type', 'finished_good')
                ->where('is_active', true)
                ->whereNull('deleted_at'));

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

    /**
     * Return one normalized list containing POS and ecommerce orders.
     */
    public function unifiedOrders(Request $request): JsonResponse
    {
        $user = $request->user();
        $storeId = (int) ($user?->store_id ?? 0);
        $canView = $user?->hasAnyPermission([
            'sales.pos.view',
            'sales.pos.manage',
            'sales.ecommerce-orders.view',
            'sales.ecommerce-orders.manage',
        ], $storeId);

        if (! $canView) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to view sales orders.'], 403);
        }

        $search = trim((string) $request->input('search', ''));
        $status = trim((string) $request->input('status', ''));
        $channel = strtolower(trim((string) $request->input('channel', '')));

        $orders = collect();

        if ($channel === '' || in_array($channel, ['pos', 'in_store', 'in-store'], true)) {
            $posQuery = SalesOrder::query()
                ->with(['branch:id,name', 'payment:id,sales_order_id,payment_method,status', 'delivery:id,sales_order_id,status,tracking_number'])
                ->withCount('items');
            $this->applyStoreScope($request, $posQuery);
            $posQuery->when($search !== '', fn ($query) => $query->where(function ($nested) use ($search) {
                $nested->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            }));
            $posQuery->when($status !== '', fn ($query) => $query->where('status', $status));

            $orders = $orders->concat($posQuery->get()->map(fn (SalesOrder $order) => [
                'key' => 'pos-' . $order->id,
                'id' => (int) $order->id,
                'order_number' => $order->order_number ?: ('POS-' . $order->id),
                'customer_name' => $order->customer_name ?: 'Walk-in',
                'customer_contact' => $order->customer_phone,
                'payment_method' => $order->payment_method ?: $order->payment?->payment_method,
                'payment_status' => $order->payment_status ?: $order->payment?->status,
                'status' => $this->salesOrderDisplayStatus($order),
                'total_amount' => (float) $order->total_amount,
                'created_at' => $order->created_at,
                'channel' => 'In-Store',
                'order_type' => 'pos',
                'branch_id' => $order->branch_id,
                'branch_name' => $order->branch?->name,
                'items_count' => (int) $order->items_count,
                'delivery_required' => (bool) $order->delivery_required,
                'delivery' => $order->delivery,
                'route_name' => 'sales.pos.order-detail',
            ]));
        }

        if ($channel === '' || in_array($channel, ['ecommerce', 'online'], true)) {
            $ecommerceQuery = EcommerceOrder::query()
                ->with(['assignedBranch:id,name', 'delivery:id,order_id,status,tracking_number'])
                ->withCount('items');
            $this->applyStoreScope($request, $ecommerceQuery);
            $ecommerceQuery->when($search !== '', fn ($query) => $query->where(function ($nested) use ($search) {
                $nested->where('order_number', 'like', "%{$search}%")
                    ->orWhere('shipping_name', 'like', "%{$search}%")
                    ->orWhere('shipping_phone', 'like', "%{$search}%");
            }));
            $ecommerceQuery->when($status !== '', fn ($query) => $query->where('status', $status));

            $orders = $orders->concat($ecommerceQuery->get()->map(fn (EcommerceOrder $order) => [
                'key' => 'ecommerce-' . $order->id,
                'id' => (int) $order->id,
                'order_number' => $order->order_number ?: ('WEB-' . $order->id),
                'customer_name' => $order->shipping_name,
                'customer_contact' => $order->shipping_phone,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'status' => $this->deliveryDisplayStatus($order->delivery?->status, $order->status ?: 'pending'),
                'total_amount' => (float) $order->total_amount,
                'created_at' => $order->placed_at ?: $order->created_at,
                'channel' => 'Online',
                'order_type' => 'ecommerce',
                'branch_id' => $order->assigned_branch_id,
                'branch_name' => $order->assignedBranch?->name,
                'items_count' => (int) $order->items_count,
                'delivery_required' => true,
                'delivery' => $order->delivery,
                'route_name' => 'sales.ecommerce-orders.detail',
            ]));
        }

        return response()->json([
            'success' => true,
            'data' => $orders->sortByDesc(fn (array $order) => (string) $order['created_at'])->values(),
        ]);
    }

    public function shippingEstimate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subtotal' => 'required|numeric|min:0',
            'branch_id' => 'nullable|exists:branches,id',
            'delivery_latitude' => 'nullable|numeric|between:-90,90',
            'delivery_longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $storeId = (int) ($request->user()->store_id ?? 0);
        $branchId = (int) ($validated['branch_id'] ?? $request->user()->branch_id ?? 0);

        return response()->json([
            'success' => true,
            'data' => $this->calculateShippingFee(
                $storeId,
                $branchId,
                (float) $validated['subtotal'],
                isset($validated['delivery_latitude']) ? (float) $validated['delivery_latitude'] : null,
                isset($validated['delivery_longitude']) ? (float) $validated['delivery_longitude'] : null,
            ),
        ]);
    }

    public function paymentOptions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'cash' => true,
                'card' => true,
                'gcash' => $this->paymongoService->isConfigured(),
            ],
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'customer_name' => 'nullable|string|max:150',
            'customer_phone' => 'nullable|string|max:50',
            'payment_method' => 'required|in:cash,card,gcash',
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

        $deliveryRequired = (bool) ($validated['delivery_required'] ?? false);
        if ($deliveryRequired && $branchId < 1) {
            return response()->json(['success' => false, 'message' => 'Select a branch before creating a delivery order.'], 422);
        }

        if ($validated['payment_method'] === 'gcash' && !$this->paymongoService->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'GCash is temporarily unavailable because PayMongo is not configured. Please select Cash or Online Payment.',
            ], 422);
        }

        // Resolve employee id for created_by (InventoryTransaction.created_by references employees.id)
        $creatorEmployeeId = $user->employee?->id
            ?? Employee::where('user_id', $user->id)->value('id')
            ?? config('app.system_employee_id', 1);
        $commissionRate = (float) (Store::query()
            ->with('subscriptionPlan:id,commission_rate')
            ->find($storeId)?->subscriptionPlan?->commission_rate ?? 0);

        $order = DB::transaction(function () use ($validated, $storeId, $branchId, $user, $creatorEmployeeId, $commissionRate, $deliveryRequired) {
            $subtotal = 0.0;
            $tax = 0.0;
            $discount = (float) ($validated['discount_amount'] ?? 0);

            // Resolve employee id for created_by (InventoryTransaction.created_by references employees.id)
            $creatorEmployeeId = $user->employee?->id
                ?? Employee::where('user_id', $user->id)->value('id')
                ?? config('app.system_employee_id', 1);

            $order = SalesOrder::create([
                'store_id' => $storeId,
                'branch_id' => $branchId ?: null,
                'order_number' => $this->nextOrderNumber(),
                'status' => $deliveryRequired ? 'pending' : 'pending_payment',
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'pending',
                'subtotal' => 0,
                'discount_amount' => $discount,
                'tax_amount' => 0,
                'shipping_fee' => 0,
                'commission_rate' => $commissionRate > 0 ? $commissionRate : null,
                'commission_amount' => 0,
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

            if ($deliveryRequired) {
                $delivery = SalesOrderDelivery::query()->create([
                    'sales_order_id' => $order->id,
                    'store_id' => $storeId,
                    'branch_id' => $branchId,
                    'status' => 'pending',
                    'notes' => 'Awaiting Sales approval for dispatch.',
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);

                SalesOrderDeliveryLog::query()->create([
                    'delivery_id' => $delivery->id,
                    'sales_order_id' => $order->id,
                    'store_id' => $storeId,
                    'event_type' => 'created',
                    'status_to' => 'pending',
                    'message' => 'Delivery request created and is pending Sales approval.',
                    'created_by' => $user->id,
                ]);
            }

            foreach ($validated['items'] as $itemRow) {
                $inv = BranchInventory::query()
                    ->with(['product:id,product_name,sku,base_price,discounted_price,product_type'])
                    ->lockForUpdate()
                    ->findOrFail((int) $itemRow['branch_inventory_id']);

                if ((int) $inv->store_id !== $storeId) {
                    abort(response()->json(['success' => false, 'message' => 'Invalid inventory item for this store.'], 422));
                }

                if (($inv->product?->product_type ?? null) !== 'finished_good') {
                    abort(response()->json(['success' => false, 'message' => 'Only finished goods can be sold through POS.'], 422));
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

            $shipping = (bool) ($validated['delivery_required'] ?? false)
                ? $this->calculateShippingFee(
                    $storeId,
                    $branchId,
                    $subtotal,
                    isset($validated['delivery_latitude']) ? (float) $validated['delivery_latitude'] : null,
                    isset($validated['delivery_longitude']) ? (float) $validated['delivery_longitude'] : null,
                )['shipping_fee']
                : 0.0;
            $total = max(0, $subtotal + $tax + $shipping - $discount);
            $commissionAmount = $commissionRate > 0 ? round($subtotal * ($commissionRate / 100), 2) : 0.0;
            $tendered = (float) ($order->amount_tendered ?? 0);
            $change = max(0, $tendered - $total);

            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'shipping_fee' => $shipping,
                'commission_amount' => $commissionAmount,
                'total_amount' => $total,
                'change_amount' => $change,
            ]);

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

        if (in_array($validated['payment_method'], ['cash', 'card'], true)) {
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

        if ($order->delivery && !in_array(strtolower((string) $order->delivery->status), ['pending', 'ready_for_dispatch'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery is already assigned for this order.',
            ], 422);
        }

        if (!$order->branch_id) {
            return response()->json([
                'success' => false,
                'message' => 'A branch must be assigned before sending this order to logistics.',
            ], 422);
        }

        DB::transaction(function () use ($order, $request): void {
            $delivery = $order->delivery;
            if (!$delivery) {
                $delivery = SalesOrderDelivery::query()->create([
                    'sales_order_id' => $order->id,
                    'store_id' => $order->store_id,
                    'branch_id' => $order->branch_id,
                    'status' => 'ready_for_dispatch',
                    'notes' => 'Order is ready for logistics assignment.',
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]);

                SalesOrderDeliveryLog::query()->create([
                    'delivery_id' => $delivery->id,
                    'sales_order_id' => $order->id,
                    'store_id' => $order->store_id,
                    'event_type' => 'created',
                    'status_to' => 'ready_for_dispatch',
                    'message' => 'Order marked ready for dispatch and queued for logistics assignment.',
                    'created_by' => $request->user()->id,
                ]);
            } elseif ((string) $delivery->status !== 'ready_for_dispatch') {
                $previousDeliveryStatus = (string) $delivery->status;
                $delivery->status = 'ready_for_dispatch';
                $delivery->updated_by = $request->user()->id;
                $delivery->save();

                SalesOrderDeliveryLog::query()->create([
                    'delivery_id' => $delivery->id,
                    'sales_order_id' => $order->id,
                    'store_id' => $order->store_id,
                    'event_type' => 'status_updated',
                    'status_from' => $previousDeliveryStatus,
                    'status_to' => 'ready_for_dispatch',
                    'message' => 'Delivery marked ready for dispatch by Sales.',
                    'created_by' => $request->user()->id,
                ]);
            }

            if ((string) $order->status !== 'ready_for_dispatch') {
                $order->status = 'ready_for_dispatch';
                $order->save();
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Order queued for logistics.',
            'data' => $order->fresh(['delivery', 'branch']),
        ]);
    }

    private function salesOrderDisplayStatus(SalesOrder $order): string
    {
        return $this->deliveryDisplayStatus($order->delivery?->status, (string) ($order->status ?: 'completed'));
    }

    private function deliveryDisplayStatus(?string $deliveryStatus, string $fallback): string
    {
        $deliveryStatus = strtolower((string) $deliveryStatus);
        if ($deliveryStatus !== '') {
            return $deliveryStatus;
        }

        return $fallback;
    }

    public function dashboard(Request $request): JsonResponse
    {
        $todayDate = now()->toDateString();
        $yesterdayDate = now()->subDay()->toDateString();
        $monthNow = now()->month;
        $yearNow = now()->year;
        $previousMonthDate = now()->subMonthNoOverflow();

        $query = SalesOrder::query();
        $this->applyStoreScope($request, $query);
        $todaySalesOrders = (clone $query)->whereDate('created_at', $todayDate);
        $yesterdaySalesOrders = (clone $query)->whereDate('created_at', $yesterdayDate);
        $monthSalesOrders = (clone $query)->whereMonth('created_at', $monthNow)->whereYear('created_at', $yearNow);
        $previousMonthSalesOrders = (clone $query)->whereMonth('created_at', $previousMonthDate->month)->whereYear('created_at', $previousMonthDate->year);

        $paymentQuery = SalesPayment::query();
        $this->applyStoreScope($request, $paymentQuery);

        $todayPosOrdersCount = (clone $todaySalesOrders)->count();
        $monthPosOrdersCount = (clone $monthSalesOrders)->count();
        $todayPosSales = (float) (clone $todaySalesOrders)->whereIn('payment_status', ['paid', 'succeeded', 'completed'])->sum('total_amount');
        $yesterdayPosSales = (float) (clone $yesterdaySalesOrders)->whereIn('payment_status', ['paid', 'succeeded', 'completed'])->sum('total_amount');
        $monthPosSales = (float) (clone $monthSalesOrders)->whereIn('payment_status', ['paid', 'succeeded', 'completed'])->sum('total_amount');
        $previousMonthPosSales = (float) (clone $previousMonthSalesOrders)->whereIn('payment_status', ['paid', 'succeeded', 'completed'])->sum('total_amount');

        $todayEcomOrdersCount = 0;
        $monthEcomOrdersCount = 0;
        $todayEcomSales = 0.0;
        $yesterdayEcomSales = 0.0;
        $monthEcomSales = 0.0;
        $previousMonthEcomSales = 0.0;
        $ecomMethods = collect();
        $ecomPendingPayments = 0;

        if (DB::getSchemaBuilder()->hasTable('ecommerce_orders')) {
            $ecomQuery = DB::table('ecommerce_orders');
            $this->applyStoreScope($request, $ecomQuery);

            $todayEcomOrdersCount = (clone $ecomQuery)->whereDate('created_at', $todayDate)->count();
            $yesterdayEcomOrdersCount = (clone $ecomQuery)->whereDate('created_at', $yesterdayDate)->count();
            $monthEcomOrdersCount = (clone $ecomQuery)->whereMonth('created_at', $monthNow)->whereYear('created_at', $yearNow)->count();
            $previousMonthEcomOrdersCount = (clone $ecomQuery)->whereMonth('created_at', $previousMonthDate->month)->whereYear('created_at', $previousMonthDate->year)->count();

            $todayEcomSales = (float) (clone $ecomQuery)
                ->whereDate('created_at', $todayDate)
                ->whereIn(DB::raw('LOWER(COALESCE(payment_status, status, "pending"))'), ['paid', 'succeeded', 'completed'])
                ->sum('total_amount');

            $yesterdayEcomSales = (float) (clone $ecomQuery)
                ->whereDate('created_at', $yesterdayDate)
                ->whereIn(DB::raw('LOWER(COALESCE(payment_status, status, "pending"))'), ['paid', 'succeeded', 'completed'])
                ->sum('total_amount');

            $monthEcomSales = (float) (clone $ecomQuery)
                ->whereMonth('created_at', $monthNow)
                ->whereYear('created_at', $yearNow)
                ->whereIn(DB::raw('LOWER(COALESCE(payment_status, status, "pending"))'), ['paid', 'succeeded', 'completed'])
                ->sum('total_amount');

            $previousMonthEcomSales = (float) (clone $ecomQuery)
                ->whereMonth('created_at', $previousMonthDate->month)
                ->whereYear('created_at', $previousMonthDate->year)
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

        $trendRange = (string) $request->input('trend_range', '7d');
        if (!in_array($trendRange, ['7d', '30d', '6m', '12m'], true)) {
            $trendRange = '7d';
        }
        $monthlyTrend = str_ends_with($trendRange, 'm');
        $periodCount = (int) substr($trendRange, 0, -1);
        $trendStart = $monthlyTrend
            ? now()->startOfMonth()->subMonths($periodCount - 1)
            : now()->startOfDay()->subDays($periodCount - 1);
        $trendFormat = $monthlyTrend ? '%Y-%m' : '%Y-%m-%d';
        $trendRows = (clone $query)
            ->where('created_at', '>=', $trendStart)
            ->whereIn('payment_status', ['paid', 'succeeded', 'completed'])
            ->selectRaw("DATE_FORMAT(created_at, '{$trendFormat}') as period, COUNT(*) as orders, SUM(total_amount) as sales")
            ->groupBy('period')
            ->get()
            ->keyBy('period');

        if (DB::getSchemaBuilder()->hasTable('ecommerce_orders')) {
            $ecomTrendRows = (clone $ecomQuery)
                ->where('created_at', '>=', $trendStart)
                ->whereIn(DB::raw('LOWER(COALESCE(payment_status, status, "pending"))'), ['paid', 'succeeded', 'completed'])
                ->selectRaw("DATE_FORMAT(created_at, '{$trendFormat}') as period, COUNT(*) as orders, SUM(total_amount) as sales")
                ->groupBy('period')
                ->get();

            foreach ($ecomTrendRows as $row) {
                $existing = $trendRows->get($row->period);
                $trendRows->put($row->period, (object) [
                    'period' => $row->period,
                    'orders' => (int) ($existing->orders ?? 0) + (int) $row->orders,
                    'sales' => (float) ($existing->sales ?? 0) + (float) $row->sales,
                ]);
            }
        }

        $salesTrend = collect();
        for ($offset = $periodCount - 1; $offset >= 0; $offset--) {
            $period = $monthlyTrend
                ? now()->startOfMonth()->subMonths($offset)->format('Y-m')
                : now()->startOfDay()->subDays($offset)->format('Y-m-d');
            $row = $trendRows->get($period);
            $salesTrend->push([
                'period' => $period,
                'orders' => (int) ($row->orders ?? 0),
                'sales' => (float) ($row->sales ?? 0),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'today_orders' => $todayPosOrdersCount + $todayEcomOrdersCount,
                'yesterday_orders' => (clone $yesterdaySalesOrders)->count() + ($yesterdayEcomOrdersCount ?? 0),
                'today_sales' => $todayPosSales + $todayEcomSales,
                'yesterday_sales' => $yesterdayPosSales + $yesterdayEcomSales,
                'month_orders' => $monthPosOrdersCount + $monthEcomOrdersCount,
                'previous_month_orders' => (clone $previousMonthSalesOrders)->count() + ($previousMonthEcomOrdersCount ?? 0),
                'month_sales' => $monthPosSales + $monthEcomSales,
                'previous_month_sales' => $previousMonthPosSales + $previousMonthEcomSales,
                'sales_trend' => $salesTrend,
                'today_paid' => $todayPaid,
                'pending_payments' => (clone $paymentQuery)->whereIn('status', ['pending', 'processing', 'awaiting_payment_method'])->count() + $ecomPendingPayments,
                'payments_by_method' => $paymentsByMethod,
                'recent_orders' => (clone $query)->with('branch:id,name')->orderByDesc('created_at')->limit(8)->get(),
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

    private function calculateShippingFee(int $storeId, int $branchId, float $subtotal, ?float $latitude, ?float $longitude): array
    {
        $setting = StoreDeliveryFeeSetting::query()->where('store_id', $storeId)->first()
            ?? new StoreDeliveryFeeSetting([
                'is_active' => true,
                'base_fee' => 100,
                'per_km_fee' => 10,
                'min_delivery_fee' => 80,
                'free_shipping_min_order' => null,
                'max_delivery_distance_km' => null,
            ]);

        if (!(bool) $setting->is_active) {
            return ['shipping_fee' => 0.0, 'distance_km' => 0.0, 'free_shipping_applied' => false];
        }

        $distanceKm = 0.0;
        if ($branchId > 0 && $latitude !== null && $longitude !== null) {
            $branch = Branch::query()->where('store_id', $storeId)->find($branchId);
            if ($branch?->latitude !== null && $branch?->longitude !== null) {
                $distanceKm = $this->distanceKm((float) $branch->latitude, (float) $branch->longitude, $latitude, $longitude);
            }
        }

        if ($setting->max_delivery_distance_km !== null && $distanceKm > (float) $setting->max_delivery_distance_km) {
            abort(response()->json(['success' => false, 'message' => 'The delivery address is outside the store delivery range.'], 422));
        }

        if ($setting->free_shipping_min_order !== null && $subtotal >= (float) $setting->free_shipping_min_order) {
            return ['shipping_fee' => 0.0, 'distance_km' => round($distanceKm, 2), 'free_shipping_applied' => true];
        }

        $fee = max(
            (float) $setting->min_delivery_fee,
            (float) $setting->base_fee + ($distanceKm * (float) $setting->per_km_fee)
        );

        return ['shipping_fee' => round($fee, 2), 'distance_km' => round($distanceKm, 2), 'free_shipping_applied' => false];
    }

    private function distanceKm(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        $latDelta = deg2rad($toLat - $fromLat);
        $lngDelta = deg2rad($toLng - $fromLng);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($lngDelta / 2) ** 2;

        return 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a));
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
