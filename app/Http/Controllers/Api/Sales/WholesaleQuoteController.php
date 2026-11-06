<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\CRM\CrmLead;
use App\Models\Inventory\BranchInventory;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesOrderItem;
use App\Models\Sales\SalesPayment;
use App\Models\Sales\WholesaleQuote;
use App\Models\Store\Branch;
use App\Services\Sales\SalesOrderSettlementService;
use App\Services\Sales\WholesaleDeliveryFeeService;
use App\Services\Payment\PaymongoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WholesaleQuoteController extends Controller
{
    public function __construct(
        private readonly SalesOrderSettlementService $settlementService,
        private readonly PaymongoService $paymongoService,
        private readonly WholesaleDeliveryFeeService $deliveryFeeService
    )
    {
    }

    public function index(Request $request): JsonResponse
    {
        $storeId = (int) $request->user()->store_id;
        $rows = WholesaleQuote::query()->where('store_id', $storeId)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));
                $query->where(function ($nested) use ($search) {
                    $nested->where('quote_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhereHas('crmLead', fn ($lead) => $lead->where('full_name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $statuses = [
                    'pending' => ['pending', 'sent'],
                    'approved' => ['approved', 'accepted'],
                    'rejected' => ['rejected', 'declined'],
                ];
                $query->whereIn('status', $statuses[$request->input('status')] ?? [$request->input('status')]);
            })
            ->when($request->filled('date'), fn ($query) => $query->whereDate('created_at', $request->input('date')))
            ->with(['crmLead:id,full_name,email,phone', 'creator:id,fname,lname'])
            ->latest()->paginate(min(50, max(1, (int) $request->input('per_page', 15))));
        $rows->getCollection()->each(function (WholesaleQuote $quote) {
            $this->attachCustomerDisplay($quote);
            $quote->setAttribute('status', $this->normalizedStatus((string) $quote->status));
        });
        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function options(Request $request): JsonResponse
    {
        $storeId = (int) $request->user()->store_id;
        $branches = Branch::query()
            ->where('store_id', $storeId)
            ->where('status', 'active')
            ->whereIn('branch_type', ['storefront', 'wholesale'])
            ->orderByDesc('is_main_branch')
            ->orderBy('name')
            ->get(['id', 'name', 'branch_type'])
            ->map(fn (Branch $branch) => [
                'id' => (int) $branch->id,
                'name' => (string) $branch->name,
                'branch_type' => (string) $branch->branch_type,
            ]);
        $products = BranchInventory::query()->where('store_id', $storeId)
            ->whereHas('product', fn ($query) => $query->where('product_type', 'finished_good')->where('is_active', true))
            ->with([
                'product:id,product_name,sku,base_price',
                'variation:id,variation_name,variation_sku,color,size,material,base_price',
                'branch:id,name',
            ])
            ->get(['id', 'store_id', 'branch_id', 'product_id', 'variation_id', 'quantity_available'])
            ->map(fn ($stock) => [
                'id' => $stock->id, 'branch_id' => $stock->branch_id,
                'branch_name' => $stock->branch?->name,
                'product_name' => $stock->product?->product_name,
                'variant_name' => $stock->variation?->variation_name,
                'variant_specs' => collect([
                    $stock->variation?->color,
                    $stock->variation?->size,
                    $stock->variation?->material,
                ])->filter()->implode(' · '),
                'sku' => $stock->variation?->variation_sku ?: $stock->product?->sku,
                'available' => (int) $stock->quantity_available,
                'base_price' => (float) ($stock->variation?->base_price ?? $stock->product?->base_price ?? 0),
            ]);
        return response()->json(['success' => true, 'data' => ['branches' => $branches, 'products' => $products]]);
    }

    public function deliveryEstimate(Request $request): JsonResponse
    {
        $storeId = (int) $request->user()->store_id;
        $validated = $request->validate([
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('store_id', $storeId)],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'delivery_latitude' => ['required', 'numeric', 'between:-90,90'],
            'delivery_longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);
        $fee = $this->deliveryFeeService->estimate($storeId, (int) $validated['branch_id'], (float) $validated['subtotal'], (float) $validated['delivery_latitude'], (float) $validated['delivery_longitude']);
        return response()->json(['success' => true, 'data' => ['shipping_fee' => $fee]]);
    }

    public function store(Request $request): JsonResponse
    {
        $storeId = (int) $request->user()->store_id;
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'min:2', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'regex:/^09\d{2}[ -]?\d{3}[ -]?\d{4}$/'],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('store_id', $storeId)],
            'valid_until' => ['nullable', 'date', 'after_or_equal:today'],
            'payment_terms' => ['required', Rule::in(['online_payment', 'bank_transfer'])],
            'notes' => ['nullable', 'string', 'max:2000'],
            'submit' => ['sometimes', 'boolean'],
            'delivery_required' => ['sometimes', 'boolean'],
            'delivery_province' => ['required_if:delivery_required,true', 'nullable', 'string', 'max:150'],
            'delivery_city' => ['required_if:delivery_required,true', 'nullable', 'string', 'max:150'],
            'delivery_barangay' => ['required_if:delivery_required,true', 'nullable', 'string', 'max:150'],
            'delivery_address_line' => ['required_if:delivery_required,true', 'nullable', 'string', 'max:255'],
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
            'delivery_latitude' => ['required_if:delivery_required,true', 'nullable', 'numeric', 'between:-90,90'],
            'delivery_longitude' => ['required_if:delivery_required,true', 'nullable', 'numeric', 'between:-180,180'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.branch_inventory_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'between:0,100'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $items = [];
        foreach ($validated['items'] as $row) {
            $stock = BranchInventory::query()->where('store_id', $storeId)
                ->where('branch_id', $validated['branch_id'])
                ->whereKey($row['branch_inventory_id'])
                ->with(['product:id,product_name,sku,product_type,is_active,base_price', 'variation:id,variation_sku,base_price'])->first();
            if (!$stock || $stock->product?->product_type !== 'finished_good' || !$stock->product?->is_active) {
                return response()->json(['success' => false, 'message' => 'Select active finished goods from the chosen branch.'], 422);
            }
            $quantity = (int) $row['quantity'];
            if ($quantity > (int) $stock->quantity_available) {
                return response()->json(['success' => false, 'message' => "Not enough stock for {$stock->product->product_name}."], 422);
            }
            $retailPrice = round((float) ($stock->variation?->base_price ?? $stock->product->base_price ?? 0), 2);
            if ($retailPrice <= 0) {
                return response()->json(['success' => false, 'message' => "{$stock->product->product_name} does not have a valid retail price."], 422);
            }
            $grossTotal = round($quantity * $retailPrice, 2);
            $discountAmount = array_key_exists('discount_amount', $row)
                ? round((float) $row['discount_amount'], 2)
                : round($grossTotal * ((float) ($row['discount_percent'] ?? 0) / 100), 2);
            if ($discountAmount > $grossTotal) {
                return response()->json(['success' => false, 'message' => "The discount for {$stock->product->product_name} cannot exceed its line total."], 422);
            }
            $discountPercent = $grossTotal > 0 ? round($discountAmount / $grossTotal * 100, 2) : 0;
            $lineTotal = round($grossTotal - $discountAmount, 2);
            $price = round($lineTotal / $quantity, 2);
            $items[] = [
                'branch_inventory_id' => $stock->id, 'product_id' => $stock->product_id,
                'variation_id' => $stock->variation_id,
                'product_name' => $stock->product->product_name, 'sku' => $stock->variation?->variation_sku ?: $stock->product->sku,
                'quantity' => $quantity, 'retail_unit_price' => $retailPrice, 'unit_price' => $price,
                'gross_total' => $grossTotal, 'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount, 'line_total' => $lineTotal,
            ];
        }
        $deliveryRequired = (bool) ($validated['delivery_required'] ?? false);
        $deliveryAddress = $deliveryRequired ? implode(', ', array_map('trim', [
            $validated['delivery_address_line'], $validated['delivery_barangay'],
            $validated['delivery_city'], $validated['delivery_province'],
        ])) : null;
        $itemsTotal = round(array_sum(array_column($items, 'line_total')), 2);
        $shippingFee = $deliveryRequired
            ? $this->deliveryFeeService->estimate($storeId, (int) $validated['branch_id'], $itemsTotal, (float) $validated['delivery_latitude'], (float) $validated['delivery_longitude'])
            : 0;
        $submitted = (bool) ($validated['submit'] ?? false);
        $status = $submitted
            ? ($request->user()->hasPermissionTo('sales.wholesale.approve') ? 'approved' : 'pending')
            : 'draft';
        $quote = WholesaleQuote::create([
            'store_id' => $storeId, 'branch_id' => $validated['branch_id'],
            'customer_name' => trim($validated['customer_name']),
            'customer_email' => strtolower(trim($validated['customer_email'])),
            'customer_phone' => preg_replace('/\s+/', ' ', trim($validated['customer_phone'])),
            'quote_number' => 'WQ-' . now()->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4))),
            'status' => $status, 'items' => $items,
            'subtotal' => $itemsTotal,
            'delivery_required' => $deliveryRequired,
            'delivery_address' => $deliveryAddress,
            'delivery_province' => $deliveryRequired ? $validated['delivery_province'] : null,
            'delivery_city' => $deliveryRequired ? $validated['delivery_city'] : null,
            'delivery_barangay' => $deliveryRequired ? $validated['delivery_barangay'] : null,
            'delivery_address_line' => $deliveryRequired ? $validated['delivery_address_line'] : null,
            'delivery_notes' => $deliveryRequired ? ($validated['delivery_notes'] ?? null) : null,
            'delivery_latitude' => $deliveryRequired ? $validated['delivery_latitude'] : null,
            'delivery_longitude' => $deliveryRequired ? $validated['delivery_longitude'] : null,
            'shipping_fee' => $shippingFee,
            'valid_until' => $validated['valid_until'] ?? null,
            'payment_terms' => $validated['payment_terms'] ?? null,
            'notes' => $validated['notes'] ?? null, 'created_by' => $request->user()->id,
        ]);
        return response()->json(['success' => true, 'data' => $quote], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $quote = WholesaleQuote::query()->where('store_id', $request->user()->store_id)
            ->with(['crmLead:id,full_name,email,phone', 'creator:id,fname,lname'])->findOrFail($id);
        $this->attachCustomerDisplay($quote);
        $quote->setAttribute('status', $this->normalizedStatus((string) $quote->status));
        if ($quote->sales_order_id) {
            $quote->setAttribute('order_payment_status', SalesOrder::query()
                ->where('store_id', $quote->store_id)->whereKey($quote->sales_order_id)
                ->value('payment_status'));
            $quote->setAttribute('payment_link', SalesPayment::query()
                ->where('store_id', $quote->store_id)
                ->where('sales_order_id', $quote->sales_order_id)
                ->where('payment_provider', 'paymongo')
                ->latest('id')->value('checkout_url'));
        }
        return response()->json(['success' => true, 'data' => $quote]);
    }

    private function attachCustomerDisplay(WholesaleQuote $quote): void
    {
        if (!$quote->crmLead && $quote->customer_name) {
            $quote->setRelation('crmLead', (object) [
                'full_name' => $quote->customer_name,
                'email' => $quote->customer_email,
                'phone' => $quote->customer_phone,
            ]);
        }
    }

    public function syncPayment(Request $request, int $id): JsonResponse
    {
        $storeId = (int) $request->user()->store_id;
        $quote = WholesaleQuote::query()->where('store_id', $storeId)
            ->where('status', 'converted')->findOrFail($id);
        $order = SalesOrder::query()->where('store_id', $storeId)->findOrFail($quote->sales_order_id);
        $payment = SalesPayment::query()->where('store_id', $storeId)
            ->where('sales_order_id', $order->id)->where('payment_provider', 'paymongo')
            ->latest('id')->first();

        if (!$payment || !str_starts_with((string) $payment->provider_reference, 'cs_')) {
            return response()->json(['success' => false, 'message' => 'No PayMongo checkout is linked to this quotation.'], 422);
        }

        if ($order->payment_status === 'paid') {
            return response()->json(['success' => true, 'data' => ['payment_status' => 'paid']]);
        }

        $session = $this->paymongoService->retrieveCheckoutSession((string) $payment->provider_reference);
        if (!data_get($session, 'data.id')) {
            return response()->json(['success' => false, 'message' => 'Could not check the payment provider. Please try again.'], 502);
        }

        $paid = collect((array) data_get($session, 'data.attributes.payments', []))
            ->contains(fn ($row) => strtolower((string) data_get($row, 'attributes.status', '')) === 'paid');
        if ($paid) {
            $payment->update(['status' => 'paid', 'paid_at' => $payment->paid_at ?? now()]);
            try {
                $this->settlementService->settlePaid($order, (string) $payment->payment_method, (string) $payment->provider_reference, $payment);
            } catch (\Throwable $e) {
                report($e);
                return response()->json(['success' => false, 'message' => 'Payment was captured, but the order could not be settled. Please contact support; do not request another payment.'], 422);
            }
        } else {
            $sessionStatus = strtolower((string) data_get($session, 'data.attributes.status', ''));
            if (in_array($sessionStatus, ['expired', 'cancelled', 'canceled', 'failed'], true)) {
                $payment->update(['status' => $sessionStatus === 'expired' ? 'expired' : $sessionStatus, 'checkout_url' => null]);
                $this->settlementService->markPaymentFailed($order, $payment);
            }
        }

        return response()->json(['success' => true, 'data' => ['payment_status' => $order->fresh()->payment_status]]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate(['status' => ['required', Rule::in(['pending', 'approved', 'rejected'])]]);
        if ($validated['status'] === 'pending' && !$request->user()->hasPermissionTo('sales.wholesale.manage')) {
            abort(403, 'Wholesale management permission is required.');
        }
        if (in_array($validated['status'], ['approved', 'rejected'], true)
            && !$request->user()->hasPermissionTo('sales.wholesale.approve')) {
            abort(403, 'Wholesale approval permission is required.');
        }
        $quote = WholesaleQuote::query()->where('store_id', $request->user()->store_id)->findOrFail($id);
        $allowed = [
            'draft' => ['pending'],
            'pending' => ['approved', 'rejected'],
            'sent' => ['approved', 'rejected'],
        ];
        if (!in_array($validated['status'], $allowed[$quote->status] ?? [], true)) {
            return response()->json(['success' => false, 'message' => 'This quotation cannot move to that status.'], 422);
        }
        $quote->update(['status' => $validated['status']]);
        return response()->json(['success' => true, 'data' => $quote->fresh()]);
    }

    public function convert(Request $request, int $id): JsonResponse
    {
        $result = DB::transaction(function () use ($request, $id) {
            $quote = WholesaleQuote::query()->where('store_id', $request->user()->store_id)
                ->lockForUpdate()->findOrFail($id);
            if (!in_array((string) $quote->status, ['approved', 'accepted'], true) || $quote->sales_order_id) {
                return ['error' => 'Only an approved quotation can be converted once.'];
            }
            if ($quote->valid_until && $quote->valid_until->startOfDay()->lt(today())) {
                return ['error' => 'This quotation has expired.'];
            }
            foreach ($quote->items as $item) {
                $stock = BranchInventory::query()->where('store_id', $quote->store_id)
                    ->where('branch_id', $quote->branch_id)->lockForUpdate()->find($item['branch_inventory_id']);
                if (!$stock || (int) $stock->quantity_available < (int) $item['quantity']) {
                    return ['error' => "Not enough stock for {$item['product_name']}."];
                }
            }
            $legacyLead = $quote->crmLead;
            $grossSubtotal = round(collect($quote->items)->sum(fn ($item) => (float) ($item['gross_total'] ?? ((float) $item['unit_price'] * (int) $item['quantity']))), 2);
            $discountTotal = round(collect($quote->items)->sum(fn ($item) => (float) ($item['discount_amount'] ?? 0)), 2);
            $order = SalesOrder::create([
                'store_id' => $quote->store_id, 'branch_id' => $quote->branch_id,
                'order_number' => 'WHO-' . now()->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4))),
                'status' => 'pending_payment',
                'payment_method' => $quote->payment_terms === 'bank_transfer' ? 'bank_transfer' : 'paymongo',
                'payment_status' => 'pending',
                'customer_name' => $quote->customer_name ?: $legacyLead?->full_name,
                'customer_phone' => $quote->customer_phone ?: $legacyLead?->phone,
                'delivery_email' => $quote->customer_email ?: $legacyLead?->email,
                'subtotal' => $grossSubtotal, 'discount_amount' => $discountTotal,
                'shipping_fee' => $quote->shipping_fee,
                'total_amount' => round((float) $quote->subtotal + (float) $quote->shipping_fee, 2),
                'delivery_required' => (bool) $quote->delivery_required,
                'delivery_address' => $quote->delivery_address,
                'delivery_province' => $quote->delivery_province,
                'delivery_city' => $quote->delivery_city,
                'delivery_barangay' => $quote->delivery_barangay,
                'delivery_address_line' => $quote->delivery_address_line,
                'delivery_notes' => $quote->delivery_notes,
                'delivery_latitude' => $quote->delivery_latitude,
                'delivery_longitude' => $quote->delivery_longitude,
                'notes' => 'Wholesale quote ' . $quote->quote_number . ($quote->payment_terms ? ' | Terms: ' . $quote->payment_terms : '') . ($quote->notes ? ' | ' . $quote->notes : ''),
                'created_by' => $request->user()->id,
            ]);
            foreach ($quote->items as $item) {
                SalesOrderItem::create([
                    'order_id' => $order->id, 'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'], 'branch_inventory_id' => $item['branch_inventory_id'],
                    'product_name' => $item['product_name'], 'sku' => $item['sku'],
                    'quantity' => $item['quantity'], 'unit_price' => $item['retail_unit_price'] ?? $item['unit_price'],
                    'line_discount' => $item['discount_amount'] ?? 0, 'line_tax' => 0, 'line_total' => $item['line_total'],
                ]);
            }
            $quote->update([
                'status' => 'converted', 'sales_order_id' => $order->id,
                'invoice_number' => 'WINV-' . now()->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4))),
            ]);
            return ['quote' => $quote->fresh(), 'order' => $order];
        });
        if (isset($result['error'])) {
            return response()->json(['success' => false, 'message' => $result['error']], 422);
        }
        return response()->json(['success' => true, 'data' => $result]);
    }

    private function normalizedStatus(string $status): string
    {
        return match ($status) {
            'sent' => 'pending',
            'accepted' => 'approved',
            'declined' => 'rejected',
            default => $status,
        };
    }

    public function recordPayment(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => ['required', Rule::in(['bank_transfer'])],
            'payment_reference' => ['required', 'string', 'max:120'],
            'confirmed_received' => ['required', 'accepted'],
        ]);
        $storeId = (int) $request->user()->store_id;
        $quote = WholesaleQuote::query()->where('store_id', $storeId)
            ->where('status', 'converted')->findOrFail($id);
        if ($quote->payment_terms !== 'bank_transfer') {
            return response()->json(['success' => false, 'message' => 'This invoice requires online payment.'], 422);
        }
        try {
            DB::transaction(function () use ($request, $validated, $quote, $storeId) {
                $order = SalesOrder::query()->where('store_id', $storeId)
                    ->whereKey($quote->sales_order_id)->lockForUpdate()->firstOrFail();
                if ($order->payment_status === 'paid') {
                    throw new \RuntimeException('Payment has already been recorded.');
                }
                $reference = trim($validated['payment_reference']);
                if (SalesPayment::query()->where('store_id', $storeId)->where('provider_reference', $reference)->exists()) {
                    throw new \RuntimeException('This bank reference has already been recorded.');
                }
                $payment = SalesPayment::create([
                    'store_id' => $storeId, 'branch_id' => $quote->branch_id,
                    'sales_order_id' => $order->id, 'crm_lead_id' => $quote->crm_lead_id,
                    'payment_provider' => 'manual', 'payment_method' => $validated['payment_method'],
                    'currency' => 'PHP', 'amount' => $order->total_amount,
                    'status' => 'paid', 'provider_reference' => $reference,
                    'paid_at' => now(), 'created_by' => $request->user()->id,
                ]);
                $order->update(['payment_method' => $validated['payment_method']]);
                $this->settlementService->settlePaid($order, $validated['payment_method'], $reference, $payment);
            });
        } catch (\RuntimeException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }
        return response()->json(['success' => true, 'message' => 'Payment recorded and stock updated.']);
    }

    public function createPaymentLink(Request $request, int $id): JsonResponse
    {
        $storeId = (int) $request->user()->store_id;
        $quote = WholesaleQuote::query()->where('store_id', $storeId)
            ->where('status', 'converted')->findOrFail($id);
        if (!in_array($quote->payment_terms, ['online_payment', 'credit_card'], true)) {
            return response()->json(['success' => false, 'message' => 'This invoice uses bank transfer.'], 422);
        }
        $order = SalesOrder::query()->where('store_id', $storeId)->findOrFail($quote->sales_order_id);
        if ($order->payment_status === 'paid') {
            return response()->json(['success' => false, 'message' => 'This invoice is already paid.'], 422);
        }
        if ((float) $order->total_amount <= 0) {
            return response()->json(['success' => false, 'message' => 'This invoice has no payable amount.'], 422);
        }
        $existing = SalesPayment::query()->where('sales_order_id', $order->id)
            ->where('payment_provider', 'paymongo')->whereNotNull('checkout_url')->latest('id')->first();
        if ($existing) {
            $previousSession = $this->paymongoService->retrieveCheckoutSession((string) $existing->provider_reference);
            $previousPayments = (array) data_get($previousSession, 'data.attributes.payments', []);
            if (collect($previousPayments)->contains(fn ($payment) => strtolower((string) data_get($payment, 'attributes.status', '')) === 'paid')) {
                return response()->json(['success' => false, 'message' => 'Payment was received. Refresh the order before creating another link.'], 409);
            }
            if (strtolower((string) data_get($previousSession, 'data.attributes.status', '')) !== 'expired') {
                return response()->json(['success' => true, 'data' => ['checkout_url' => $existing->checkout_url]]);
            }
            $existing->update(['status' => 'expired']);
        }
        if (!$this->paymongoService->isConfigured()) {
            return response()->json(['success' => false, 'message' => 'PayMongo is not configured.'], 503);
        }
        $methods = $this->paymongoService->activePaymentMethods();
        if (!$methods) {
            $methods = ['card', 'gcash'];
        }
        $returnUrl = url('/wholesale-payment/return');
        $payload = $this->paymongoService->createCheckoutSession(['data' => ['attributes' => [
            'cancel_url' => $returnUrl . '?result=cancelled',
            'success_url' => $returnUrl . '?result=returned',
            'payment_method_types' => $methods,
            'line_items' => [[
                'currency' => 'PHP',
                'amount' => max(1, (int) round((float) $order->total_amount * 100)),
                'name' => 'Wholesale invoice ' . ($quote->invoice_number ?: $quote->quote_number),
                'quantity' => 1,
            ]],
            'metadata' => [
                'store_id' => (string) $storeId,
                'payable_type' => 'sales_order',
                'payable_id' => (string) $order->id,
                'wholesale_quote_id' => (string) $quote->id,
            ],
        ]]]);
        $sessionId = (string) data_get($payload, 'data.id', '');
        $checkoutUrl = (string) data_get($payload, 'data.attributes.checkout_url', '');
        if ($sessionId === '' || !str_starts_with($checkoutUrl, 'https://checkout.paymongo.com/')) {
            return response()->json(['success' => false, 'message' => data_get($payload, 'errors.0.detail', 'Unable to create PayMongo payment link.')], 422);
        }
        DB::transaction(function () use ($quote, $order, $storeId, $request, $sessionId, $checkoutUrl, $methods) {
            $this->paymongoService->logIntent([
                'store_id' => $storeId,
                'payment_intent_id' => $sessionId,
                'amount' => (int) round((float) $order->total_amount * 100),
                'currency' => 'PHP',
                'status' => 'pending',
                'description' => 'Wholesale invoice ' . ($quote->invoice_number ?: $quote->quote_number),
                'payment_method_allowed' => implode(',', $methods),
                'metadata' => ['wholesale_quote_id' => (string) $quote->id],
                'payable_type' => 'sales_order',
                'payable_id' => $order->id,
            ]);
            SalesPayment::create([
                'store_id' => $storeId, 'branch_id' => $quote->branch_id,
                'sales_order_id' => $order->id, 'crm_lead_id' => $quote->crm_lead_id,
                'payment_provider' => 'paymongo', 'payment_method' => 'paymongo',
                'currency' => 'PHP', 'amount' => $order->total_amount,
                'status' => 'pending', 'provider_reference' => $sessionId,
                'checkout_url' => $checkoutUrl, 'created_by' => $request->user()->id,
            ]);
        });
        return response()->json(['success' => true, 'data' => ['checkout_url' => $checkoutUrl]]);
    }
}
