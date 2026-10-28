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
use App\Services\Payment\PaymongoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WholesaleQuoteController extends Controller
{
    public function __construct(
        private readonly SalesOrderSettlementService $settlementService,
        private readonly PaymongoService $paymongoService
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
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('date'), fn ($query) => $query->whereDate('created_at', $request->input('date')))
            ->with('crmLead:id,full_name,email,phone')
            ->latest()->paginate(min(50, max(1, (int) $request->input('per_page', 15))));
        $rows->getCollection()->each(fn (WholesaleQuote $quote) => $this->attachCustomerDisplay($quote));
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
            ->with(['product:id,product_name,sku,base_price', 'branch:id,name'])
            ->get(['id', 'store_id', 'branch_id', 'product_id', 'variation_id', 'quantity_available'])
            ->map(fn ($stock) => [
                'id' => $stock->id, 'branch_id' => $stock->branch_id,
                'branch_name' => $stock->branch?->name,
                'product_name' => $stock->product?->product_name,
                'sku' => $stock->product?->sku,
                'available' => (int) $stock->quantity_available,
                'base_price' => (float) ($stock->product?->base_price ?? 0),
            ]);
        return response()->json(['success' => true, 'data' => ['branches' => $branches, 'products' => $products]]);
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.branch_inventory_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'gt:0'],
        ]);

        $items = [];
        foreach ($validated['items'] as $row) {
            $stock = BranchInventory::query()->where('store_id', $storeId)
                ->where('branch_id', $validated['branch_id'])
                ->whereKey($row['branch_inventory_id'])
                ->with('product:id,product_name,sku,product_type,is_active')->first();
            if (!$stock || $stock->product?->product_type !== 'finished_good' || !$stock->product?->is_active) {
                return response()->json(['success' => false, 'message' => 'Select active finished goods from the chosen branch.'], 422);
            }
            $quantity = (int) $row['quantity'];
            $price = round((float) $row['unit_price'], 2);
            $items[] = [
                'branch_inventory_id' => $stock->id, 'product_id' => $stock->product_id,
                'variation_id' => $stock->variation_id,
                'product_name' => $stock->product->product_name, 'sku' => $stock->product->sku,
                'quantity' => $quantity, 'unit_price' => $price,
                'line_total' => round($quantity * $price, 2),
            ];
        }
        $quote = WholesaleQuote::create([
            'store_id' => $storeId, 'branch_id' => $validated['branch_id'],
            'customer_name' => trim($validated['customer_name']),
            'customer_email' => strtolower(trim($validated['customer_email'])),
            'customer_phone' => preg_replace('/\s+/', ' ', trim($validated['customer_phone'])),
            'quote_number' => 'WQ-' . now()->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4))),
            'status' => 'draft', 'items' => $items,
            'subtotal' => round(array_sum(array_column($items, 'line_total')), 2),
            'valid_until' => $validated['valid_until'] ?? null,
            'payment_terms' => $validated['payment_terms'] ?? null,
            'notes' => $validated['notes'] ?? null, 'created_by' => $request->user()->id,
        ]);
        return response()->json(['success' => true, 'data' => $quote], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $quote = WholesaleQuote::query()->where('store_id', $request->user()->store_id)
            ->with('crmLead:id,full_name,email,phone')->findOrFail($id);
        $this->attachCustomerDisplay($quote);
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

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate(['status' => ['required', Rule::in(['sent', 'accepted', 'declined'])]]);
        $quote = WholesaleQuote::query()->where('store_id', $request->user()->store_id)->findOrFail($id);
        $allowed = ['draft' => ['sent'], 'sent' => ['accepted', 'declined']];
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
            if ($quote->status !== 'accepted' || $quote->sales_order_id) {
                return ['error' => 'Only an accepted quotation can be converted once.'];
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
            $order = SalesOrder::create([
                'store_id' => $quote->store_id, 'branch_id' => $quote->branch_id,
                'order_number' => 'WHO-' . now()->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4))),
                'status' => 'pending_payment',
                'payment_method' => $quote->payment_terms === 'bank_transfer' ? 'bank_transfer' : 'paymongo',
                'payment_status' => 'pending',
                'customer_name' => $quote->customer_name ?: $legacyLead?->full_name,
                'customer_phone' => $quote->customer_phone ?: $legacyLead?->phone,
                'delivery_email' => $quote->customer_email ?: $legacyLead?->email,
                'subtotal' => $quote->subtotal, 'total_amount' => $quote->subtotal,
                'notes' => 'Wholesale quote ' . $quote->quote_number . ($quote->payment_terms ? ' | Terms: ' . $quote->payment_terms : '') . ($quote->notes ? ' | ' . $quote->notes : ''),
                'created_by' => $request->user()->id,
            ]);
            foreach ($quote->items as $item) {
                SalesOrderItem::create([
                    'order_id' => $order->id, 'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'], 'branch_inventory_id' => $item['branch_inventory_id'],
                    'product_name' => $item['product_name'], 'sku' => $item['sku'],
                    'quantity' => $item['quantity'], 'unit_price' => $item['unit_price'],
                    'line_discount' => 0, 'line_tax' => 0, 'line_total' => $item['line_total'],
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
