<?php

namespace App\Http\Controllers\Api\Payments;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\EcommerceCartItem;
use App\Models\Ecommerce\EcommerceOrder;
use App\Models\Finance\FinanceCashflowTransaction;
use App\Models\PlatformRevenue;
use App\Models\PaymongoIntent;
use App\Models\Procurement\Invoice\Invoice;
use App\Models\Procurement\Supplier\Supplier;
use App\Models\Procurement\SupplierPortal\SupplierPortal;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;
use App\Models\Sales\SalesPayment;
use App\Models\Sales\SalesOrder;
use App\Models\Store\Store;
use App\Models\Inventory\BranchInventory;
use App\Models\Hr\Employee;
use App\Models\Store\Branch;
use App\Services\Finance\CashflowService;
use App\Services\Finance\FinanceExpenseService;
use App\Services\Payment\PaymongoService;
use App\Services\Sales\SalesOrderSettlementService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymongoController extends Controller
{
    private const ALL_STORE_MODULES = [
        'inventory',
        'procurement',
        'sales',
        'hr',
        'logistics',
        'finance',
        'supplier',
        'ecommerce',
    ];

    public function __construct(
        private PaymongoService $service,
        private SalesOrderSettlementService $salesSettlementService
    )
    {
    }

    public function create(Request $request): JsonResponse
    {
        if (!$this->service->isConfigured()) {
            return response()->json([
                'message' => 'Online Payment is not configured. Add the secret and public API keys to the server environment, then clear the configuration cache.',
                'code' => 'ONLINE_PAYMENT_NOT_CONFIGURED',
            ], 503);
        }

        $data = $request->validate([
            'amount' => 'required|integer|min:1',
            'currency' => 'string|min:3|max:3',
            'description' => 'nullable|string',
            'statement_descriptor' => 'nullable|string',
            'payment_method_allowed' => 'nullable|array',
            'metadata' => 'nullable|array',
            'store_id' => 'nullable|integer|exists:stores,id',
            'payable_type' => 'required|string',
            'payable_id' => 'nullable|integer',
        ]);

        $isSignupFlow = strtolower((string) ($data['metadata']['checkout_source'] ?? '')) === 'subscription_signup';
        $resolvedStoreId = (int) ($data['store_id'] ?? 0);
        if ($resolvedStoreId <= 0 && !$isSignupFlow) {
            $resolvedStoreId = $this->resolveStoreIdFromAuthUser();
        }

        if ($resolvedStoreId <= 0 && !$isSignupFlow) {
            return response()->json([
                'message' => 'Store not found for this account.',
            ], 422);
        }

        $isSignupFlow = strtolower((string) ($data['metadata']['checkout_source'] ?? '')) === 'subscription_signup';
        if (($data['payable_type'] ?? '') === 'subscription_upgrade' && empty($data['payable_id']) && !$isSignupFlow) {
            $data['payable_id'] = $resolvedStoreId;
        }

        if (empty($data['payable_id']) && !$isSignupFlow) {
            return response()->json([
                'message' => 'Payable id is required.',
            ], 422);
        }

        if (($data['payable_type'] ?? '') === 'invoice') {
            $invoice = Invoice::query()
                ->where('store_id', $resolvedStoreId)
                ->find($data['payable_id']);

            if (!$invoice) {
                return response()->json([
                    'message' => 'Invoice not found for this store.',
                ], 422);
            }

            if ((string) $invoice->status !== 'approved') {
                return response()->json([
                    'message' => 'Online payment can only be created after invoice is approved.',
                ], 422);
            }

            if (!$this->hasCompleteSupplierPaymentAccount((int) $invoice->supplier_id)) {
                return response()->json([
                    'message' => 'Supplier payment account is incomplete. Please ask supplier to set bank details before payment.',
                ], 422);
            }
        }

        $store = $resolvedStoreId > 0 ? Store::query()->find($resolvedStoreId) : null;
        $storeAllowedMethods = $store ? $this->resolveStorePaymongoMethods($store) : ['card', 'gcash'];
        $requestedAllowed = $data['payment_method_allowed'] ?? null;
        if (is_array($requestedAllowed) && count($requestedAllowed) > 0) {
            $normalized = array_values(array_unique(array_map(fn($m) => strtolower(trim((string) $m)), $requestedAllowed)));
            $allowed = array_values(array_intersect($normalized, $storeAllowedMethods));
            $data['payment_method_allowed'] = count($allowed) ? $allowed : $storeAllowedMethods;
        } else {
            $data['payment_method_allowed'] = $storeAllowedMethods;
        }

        $attributes = [
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'PHP',
            'capture_type' => 'automatic',
            'description' => $data['description'] ?? null,
            'statement_descriptor' => $data['statement_descriptor'] ?? null,
            'payment_method_allowed' => $data['payment_method_allowed'],
        ];

        // Online Payment rejects blank metadata, so only send it when it has values.
        $normalizedMetadata = $this->normalizeMetadata($data['metadata'] ?? null);
        if (!empty($normalizedMetadata)) {
            $attributes['metadata'] = $normalizedMetadata;
        }

        $payload = [
            'data' => [
                'attributes' => $attributes,
            ],
        ];

        $intentPayload = $this->service->createIntent($payload);
        $intentId = data_get($intentPayload, 'data.id');

        // Do not persist anything when Online Payment returned an error payload.
        if (!$intentId) {
            $firstError = data_get($intentPayload, 'errors.0.detail');

            return response()->json([
                'message' => $firstError ?: 'Online Payment intent creation failed.',
                'errors' => data_get($intentPayload, 'errors', []),
                'raw' => $intentPayload,
            ], 422);
        }

        $attrs = data_get($intentPayload, 'data.attributes', []);
        $intent = $this->service->logIntent([
            'store_id' => $resolvedStoreId > 0 ? $resolvedStoreId : null,
            'payment_intent_id' => $intentId,
            'amount' => data_get($attrs, 'amount'),
            'currency' => data_get($attrs, 'currency'),
            'status' => data_get($attrs, 'status'),
            'client_key' => data_get($attrs, 'client_key'),
            'description' => data_get($attrs, 'description'),
            'statement_descriptor' => data_get($attrs, 'statement_descriptor'),
            'payment_method_allowed' => implode(',', $data['payment_method_allowed']),
            'metadata' => data_get($attrs, 'metadata', $normalizedMetadata),
            'payable_type' => $data['payable_type'],
            'payable_id' => $data['payable_id'],
        ]);

        return response()->json([
            'message' => 'Payment intent created',
            'data' => $intentPayload,
            'intent' => $intent,
        ]);
    }

    /**
     * Create a hosted checkout session (supports cards + wallets without collecting card data on our site).
     * POST /api/payments/paymongo/checkout-session
     */
    public function createCheckoutSession(Request $request): JsonResponse
    {
        if (!$this->service->isConfigured()) {
            return response()->json(['message' => 'Online Payment is not configured.'], 503);
        }

        $data = $request->validate([
            'amount' => 'required|integer|min:1',
            'currency' => 'string|min:3|max:3',
            'description' => 'nullable|string|max:255',
            'payment_method_allowed' => 'nullable|array',
            'metadata' => 'nullable|array',
            'store_id' => 'nullable|integer|exists:stores,id',
            'payable_type' => 'required|string|max:120',
            'payable_id' => 'nullable|integer',
            'success_url' => 'required|url|max:500',
            'cancel_url' => 'required|url|max:500',
        ]);

        if (($data['payable_type'] ?? '') === 'ecommerce_order'
            && !empty($data['payable_id'])
            && !$request->attributes->get('ecommerce_payment_lock_acquired')) {
            $lock = Cache::lock('ecommerce-payment-order:'.(int) $data['payable_id'], 60);

            try {
                return $lock->block(10, function () use ($request) {
                    $request->attributes->set('ecommerce_payment_lock_acquired', true);
                    return $this->createCheckoutSession($request);
                });
            } catch (LockTimeoutException) {
                return response()->json([
                    'message' => 'A payment session is already being prepared for this order. Please try again shortly.',
                ], 409);
            }
        }

        $resolvedStoreId = (int) ($data['store_id'] ?? 0);
        if ($resolvedStoreId <= 0) {
            $resolvedStoreId = $this->resolveStoreIdFromAuthUser();
        }

        if ($resolvedStoreId <= 0) {
            return response()->json([
                'message' => 'Store not found for this account.',
            ], 422);
        }

        $isSignupFlow = strtolower((string) ($data['metadata']['checkout_source'] ?? '')) === 'subscription_signup';
        if (($data['payable_type'] ?? '') === 'subscription_upgrade' && empty($data['payable_id']) && !$isSignupFlow) {
            $data['payable_id'] = $resolvedStoreId;
        }

        if (empty($data['payable_id']) && !$isSignupFlow) {
            return response()->json([
                'message' => 'Payable id is required.',
            ], 422);
        }

        if (($data['payable_type'] ?? '') === 'ecommerce_order') {
            $order = EcommerceOrder::query()
                ->whereKey((int) $data['payable_id'])
                ->where('user_id', $request->user()?->id)
                ->first();
            if (!$order || (int) $order->store_id !== $resolvedStoreId || $order->payment_status === 'paid'
                || in_array($order->status, ['cancelled', 'canceled'], true)
                || empty(((array) ($order->pending_snapshot ?? []))['items'])) {
                return response()->json(['message' => 'This order is not available for online payment.'], 422);
            }
            $data['amount'] = max(1, (int) round((float) $order->total_amount * 100));
        }

        $salesOrder = null;
        if (($data['payable_type'] ?? '') === 'sales_order') {
            $salesOrder = SalesOrder::query()
                ->whereKey((int) $data['payable_id'])
                ->where('store_id', $resolvedStoreId)
                ->first();
            $salesPayment = $salesOrder ? SalesPayment::query()
                ->where('sales_order_id', $salesOrder->id)
                ->where('payment_provider', 'paymongo')
                ->whereIn('status', ['pending', 'processing', 'awaiting_payment_method', 'failed', 'cancelled'])
                ->latest('id')
                ->first() : null;

            if (!$salesOrder || !$salesPayment || in_array(strtolower((string) $salesOrder->payment_status), ['paid', 'cancelled'], true)) {
                return response()->json(['message' => 'This POS order is not available for online payment.'], 422);
            }
            $data['amount'] = max(1, (int) round((float) $salesOrder->total_amount * 100));
        }

        if (($data['payable_type'] ?? '') === 'invoice') {
            $invoice = Invoice::query()
                ->where('store_id', $resolvedStoreId)
                ->find($data['payable_id']);

            if (!$invoice) {
                return response()->json([
                    'message' => 'Invoice not found for this store.',
                ], 422);
            }

            if ((string) $invoice->status !== 'approved') {
                return response()->json([
                    'message' => 'Online payment can only be created after invoice is approved.',
                ], 422);
            }

            if (!$this->hasCompleteSupplierPaymentAccount((int) $invoice->supplier_id)) {
                return response()->json([
                    'message' => 'Supplier payment account is incomplete. Please ask supplier to set bank details before payment.',
                ], 422);
            }
            if (strtolower((string) $invoice->payment_status) === 'paid') {
                return response()->json(['message' => 'This invoice is already paid.'], 422);
            }
            $data['amount'] = max(1, (int) round((float) ($invoice->net_amount ?: $invoice->invoice_amount) * 100));
        }

        $store = Store::query()->find($resolvedStoreId);
        $isEcommerceOrder = ($data['payable_type'] ?? '') === 'ecommerce_order';
        $isSalesOrder = ($data['payable_type'] ?? '') === 'sales_order';
        $isHostedPayable = in_array($data['payable_type'] ?? '', ['ecommerce_order', 'subscription_upgrade', 'invoice', 'sales_order'], true);
        if ($isHostedPayable && !$isEcommerceOrder && (int) ($request->user()?->store_id ?? 0) !== $resolvedStoreId
            && (int) ($store?->owner_id ?? 0) !== (int) ($request->user()?->id ?? 0)) {
            return response()->json(['message' => 'This payment does not belong to your store.'], 403);
        }
        if (($data['payable_type'] ?? '') === 'subscription_upgrade') {
            if ((int) $data['payable_id'] !== $resolvedStoreId) {
                return response()->json(['message' => 'Invalid subscription store.'], 422);
            }
            $planKey = strtolower(trim((string) ($data['metadata']['subscription_tier'] ?? '')));
            $plan = \App\Models\Admin\SubscriptionPlan::query()->where('plan_key', $planKey)->where('is_active', true)->first();
            $cycle = strtolower((string) ($data['metadata']['billing_cycle'] ?? 'monthly'));
            if (!$plan || !in_array($cycle, ['monthly', 'yearly'], true) || $planKey === 'free') {
                return response()->json(['message' => 'Select an active paid subscription plan.'], 422);
            }
            $price = (float) ($cycle === 'yearly' ? $plan->yearly_price : $plan->monthly_price);
            if ($price <= 0) {
                return response()->json(['message' => 'This plan has no valid payment price.'], 422);
            }
            $data['amount'] = (int) round($price * 100);
            $data['metadata']['subscription_tier'] = $plan->plan_key;
            $data['metadata']['months'] = $cycle === 'yearly' ? 12 : 1;
            $data['metadata']['amount_php'] = $price;
        }
        $accountMethods = $isHostedPayable && !str_starts_with((string) config('paymongo.secret'), 'sk_test_')
            ? $this->service->activePaymentMethods()
            : [];
        $storeAllowedMethods = $isHostedPayable
            ? $this->availableHostedMethods($store, $accountMethods)
            : $this->resolveStorePaymongoMethods($store);
        $requestedAllowed = $data['payment_method_allowed'] ?? null;
        if (is_array($requestedAllowed) && count($requestedAllowed) > 0) {
            $normalized = array_values(array_unique(array_map(fn($m) => strtolower(trim((string) $m)), $requestedAllowed)));
            if ($isSalesOrder && !in_array(strtolower((string) $salesOrder?->payment_method), $normalized, true)) {
                return response()->json(['message' => 'The selected payment method does not match this POS order.'], 422);
            }
            $allowed = array_values(array_intersect($normalized, $storeAllowedMethods));
            if (($isEcommerceOrder || $isSalesOrder) && count($allowed) !== count($normalized)) {
                return response()->json(['message' => 'This payment method is not available for this store. Please choose another method.'], 422);
            }
            $data['payment_method_allowed'] = count($allowed) ? $allowed : $storeAllowedMethods;
        } else {
            $data['payment_method_allowed'] = $storeAllowedMethods;
        }

        $supportedForCheckout = $isHostedPayable
            ? $storeAllowedMethods
            : ['card', 'gcash', 'grab_pay', 'paymaya'];
        $methodTypes = array_values(array_intersect($data['payment_method_allowed'], $supportedForCheckout));
        if (($isEcommerceOrder || $isSalesOrder) && is_array($requestedAllowed) && count($methodTypes) !== count(array_unique($requestedAllowed))) {
            return response()->json(['message' => 'This payment method is not available for checkout. Please choose another method.'], 422);
        }
        if (count($methodTypes) === 0) {
            if ($isHostedPayable) {
                return response()->json(['message' => 'No online payment methods are enabled for this store.'], 422);
            }
            $methodTypes = ['gcash'];
        }

        $normalizedMetadata = $this->normalizeMetadata($data['metadata'] ?? null);
        $metadata = array_merge($normalizedMetadata, [
            'store_id' => (string) $resolvedStoreId,
            'payable_type' => (string) $data['payable_type'],
        ]);
        if (!empty($data['payable_id'])) {
            $metadata['payable_id'] = (string) $data['payable_id'];
        }

        $payload = [
            'data' => [
                'attributes' => [
                    'cancel_url' => $data['cancel_url'],
                    'success_url' => $data['success_url'],
                    'payment_method_types' => $methodTypes,
                    'line_items' => [[
                        'currency' => $data['currency'] ?? 'PHP',
                        'amount' => (int) $data['amount'],
                        'name' => $data['description'] ?? 'Payment',
                        'quantity' => 1,
                    ]],
                    'metadata' => $metadata,
                ],
            ],
        ];

        if ($isEcommerceOrder && isset($order)) {
            $payload['data']['attributes']['billing'] = [
                'name' => $order->billing_name ?: $order->shipping_name,
                'email' => $order->shipping_email,
                'phone' => $order->shipping_phone,
                'address' => [
                    'line1' => $order->billing_address ?: $order->shipping_address,
                    'country' => 'PH',
                ],
            ];
        }

        if ($isEcommerceOrder && isset($order)) {
            $latest = PaymongoIntent::query()
                ->where('payable_type', 'ecommerce_order')
                ->where('payable_id', $order->id)
                ->latest('id')
                ->first();

            if ($latest && str_starts_with((string) $latest->payment_intent_id, 'cs_')) {
                $session = $this->service->retrieveCheckoutSession((string) $latest->payment_intent_id);
                if (data_get($session, 'errors.0.detail') || !data_get($session, 'data.id')) {
                    return response()->json(['message' => 'Unable to verify the previous payment attempt. Please try again shortly.'], 502);
                }

                $payments = (array) data_get($session, 'data.attributes.payments', []);
                $paid = collect($payments)->contains(fn ($payment) => strtolower((string) data_get($payment, 'attributes.status', '')) === 'paid');
                if ($paid) {
                    $latest->update(['status' => 'succeeded']);
                    $this->syncHostedCheckoutPayment($latest->fresh());
                    return response()->json(['message' => 'This order has already been paid.'], 422);
                }

                $sessionStatus = strtolower((string) data_get($session, 'data.attributes.status', ''));
                if (!in_array($sessionStatus, ['expired', 'cancelled', 'canceled', 'failed'], true)) {
                    if (!in_array($methodTypes[0] ?? '', explode(',', (string) $latest->payment_method_allowed), true)) {
                        $expired = $this->service->expireCheckoutSession((string) $latest->payment_intent_id);
                        $sessionStatus = strtolower((string) data_get($expired, 'data.attributes.status', ''));
                        if ($sessionStatus !== 'expired') {
                            $verified = $this->service->retrieveCheckoutSession((string) $latest->payment_intent_id);
                            $verifiedPayments = (array) data_get($verified, 'data.attributes.payments', []);
                            $verifiedPaid = collect($verifiedPayments)->contains(fn ($payment) => strtolower((string) data_get($payment, 'attributes.status', '')) === 'paid');
                            if ($verifiedPaid) {
                                $latest->update(['status' => 'succeeded']);
                                $this->syncHostedCheckoutPayment($latest->fresh());
                                return response()->json(['message' => 'This order has already been paid.'], 422);
                            }
                            $sessionStatus = strtolower((string) data_get($verified, 'data.attributes.status', ''));
                            if ($sessionStatus !== 'expired') {
                                return response()->json(['message' => 'Your previous payment is still processing. Please wait before choosing another method.'], 409);
                            }
                        }
                    } else {
                        $existingUrlRaw = data_get($session, 'data.attributes.checkout_url.url')
                            ?? data_get($session, 'data.attributes.checkout_url');
                        $existingUrl = is_string($existingUrlRaw) ? trim($existingUrlRaw) : '';
                        if (!str_starts_with($existingUrl, 'https://checkout.paymongo.com/')) {
                            $existingUrl = 'https://checkout.paymongo.com/'.$latest->payment_intent_id.'#'.base64_encode((string) config('paymongo.public'));
                        }

                        return response()->json(['message' => 'Continue the existing payment attempt.', 'data' => [
                            'checkout_url' => $existingUrl,
                            'session_id' => $latest->payment_intent_id,
                        ]]);
                    }
                }

                $latest->update(['status' => $sessionStatus]);
                $this->syncEcommerceOrderFromIntent($latest->fresh(), $sessionStatus);
            }
        }

        $sessionPayload = $this->service->createCheckoutSession($payload);
        $checkoutUrlRaw = data_get($sessionPayload, 'data.attributes.checkout_url')
            ?? data_get($sessionPayload, 'data.attributes.checkout_url.url');
        $sessionId = (string) data_get($sessionPayload, 'data.id', '');
        $publicKey = (string) config('paymongo.public');

        $checkoutUrl = is_string($checkoutUrlRaw) ? trim($checkoutUrlRaw) : '';

        // Defensive: some responses include a "client_key" shaped value (cs_..._client_...) which is NOT a checkout URL.
        // The actual hosted page URL format is: https://checkout.paymongo.com/{session_id}#{base64(public_key)}
        if ($sessionId && (!$checkoutUrl || str_contains($checkoutUrl, '_client_') || !str_contains($checkoutUrl, 'checkout.paymongo.com'))) {
            if ($publicKey !== '') {
                $checkoutUrl = "https://checkout.paymongo.com/{$sessionId}#" . base64_encode($publicKey);
            }
        }

        if (!$checkoutUrl) {
            return response()->json([
                'message' => data_get($sessionPayload, 'errors.0.detail', 'Unable to create Online Payment checkout session.'),
                'errors' => data_get($sessionPayload, 'errors', []),
                'raw' => $sessionPayload,
            ], 422);
        }

        if ($isHostedPayable && !$sessionId) {
            return response()->json(['message' => 'PayMongo did not return a checkout session ID.'], 502);
        }

        if ($isHostedPayable) {
            $loggedIntent = $this->service->logIntent([
                'store_id' => $resolvedStoreId,
                'payment_intent_id' => $sessionId,
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'PHP',
                'status' => 'pending',
                'description' => $data['description'] ?? 'Hosted checkout',
                'payment_method_allowed' => implode(',', $methodTypes),
                'metadata' => $metadata,
                'payable_type' => $data['payable_type'],
                'payable_id' => (int) $data['payable_id'],
            ]);
            if ($isSalesOrder) {
                $salesPayment = SalesPayment::query()
                    ->where('sales_order_id', (int) $data['payable_id'])
                    ->where('payment_provider', 'paymongo')
                    ->whereIn('status', ['pending', 'processing', 'awaiting_payment_method', 'failed', 'cancelled'])
                    ->latest('id')
                    ->first();
                if ($salesPayment) {
                    $salesPayment->update([
                        'provider_reference' => $sessionId,
                        'checkout_url' => $checkoutUrl,
                        'status' => 'pending',
                        'metadata' => array_merge((array) $salesPayment->metadata, [
                            'source' => 'sales_pos',
                            'payment_session_id' => $loggedIntent->payment_intent_id,
                        ]),
                    ]);
                }
            }
            if ($isEcommerceOrder && isset($order) && $order->payment_status === 'failed') {
                $order->update(['payment_status' => 'unpaid']);
            }
        }

        // Debugging guard: immediately retrieve the session to ensure it exists and inspect expiry/status.
        $retrieved = null;
        $retrievedStatus = null;
        $retrievedExpiresAt = null;
        if ($sessionId) {
            $retrieved = $this->service->retrieveCheckoutSession($sessionId);
            $retrievedStatus = data_get($retrieved, 'data.attributes.status');
            $retrievedExpiresAt = data_get($retrieved, 'data.attributes.expires_at') ?? data_get($retrieved, 'data.attributes.expiry');
        }

        \Log::info('Online Payment checkout session created', [
            'store_id' => $resolvedStoreId,
            'payable_type' => $data['payable_type'],
            'payable_id' => $data['payable_id'],
            'checkout_nonce' => $metadata['checkout_nonce'] ?? null,
            'success_url' => $data['success_url'],
            'cancel_url' => $data['cancel_url'],
            'session_id' => $sessionId ?: null,
            'payment_method_types' => $methodTypes,
            'retrieved_status' => $retrievedStatus,
            'retrieved_expires_at' => $retrievedExpiresAt,
            'checkout_url_raw' => is_string($checkoutUrlRaw) ? $checkoutUrlRaw : gettype($checkoutUrlRaw),
            'checkout_url' => $checkoutUrl,
        ]);

        return response()->json([
            'message' => 'Checkout session created.',
            'data' => [
                'checkout_url' => $checkoutUrl,
                'session_id' => $sessionId ?: null,
                'session' => $sessionPayload,
                'retrieved' => $retrieved,
            ],
        ]);
    }

    public function retrieveCheckoutSession(string $sessionId): JsonResponse
    {
        $payload = $this->service->retrieveCheckoutSession($sessionId);
        return response()->json(['data' => $payload]);
    }

    public function publicKey(): JsonResponse
    {
        return response()->json([
            'data' => [
                'public_key' => (string) config('paymongo.public'),
                'endpoint' => (string) config('paymongo.endpoint'),
            ],
        ]);
    }

    public function status(string $paymentIntentId): JsonResponse
    {
        $intent = PaymongoIntent::where('payment_intent_id', $paymentIntentId)->first();
        $payload = $this->service->retrieveIntent($paymentIntentId);

        if (data_get($payload, 'errors.0.detail')) {
            return response()->json([
                'message' => data_get($payload, 'errors.0.detail', 'Unable to retrieve Online Payment payment intent.'),
                'errors' => data_get($payload, 'errors', []),
                'data' => $payload,
            ], 502);
        }

        if ($intent) {
            $attrs = data_get($payload, 'data.attributes', []);
            $status = (string) data_get($attrs, 'status', $intent->status);
            $intent->update([
                'status' => $status,
                'client_key' => data_get($attrs, 'client_key', $intent->client_key),
                'metadata' => data_get($attrs, 'metadata', $intent->metadata),
            ]);
            $freshIntent = $intent->fresh();
            $this->syncSalesPaymentFromIntent($freshIntent, $status);
            $this->syncEcommerceOrderFromIntent($freshIntent, $status);
            $this->syncInvoiceFromIntent($freshIntent, $status);
            $this->syncCashflowTopUpFromIntent($freshIntent, $status);
            $this->syncSubscriptionFromIntent($freshIntent, $status);
        }

        return response()->json(['data' => $payload]);
    }

    public function latestByPayable(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payable_type' => 'required|string|max:100',
            'payable_id' => 'required|integer|min:1',
            'sync' => 'nullable|boolean',
        ]);

        if ($validated['payable_type'] === 'ecommerce_order') {
            $ownsOrder = EcommerceOrder::query()
                ->whereKey((int) $validated['payable_id'])
                ->where('user_id', $request->user()?->id)
                ->exists();
            if (!$ownsOrder) {
                return response()->json(['message' => 'Order not found.'], 404);
            }
        }

        $intent = PaymongoIntent::query()
            ->where('payable_type', $validated['payable_type'])
            ->where('payable_id', $validated['payable_id'])
            ->latest('id')
            ->first();

        if (!$intent) {
            return response()->json(['data' => null]);
        }

        if (!empty($validated['sync'])) {
            try {
                $this->refreshIntentFromPaymongo($intent);
                $intent = $intent->fresh();
            } catch (\Throwable $e) {
                Log::warning('Online Payment latest sync failed.', [
                    'intent_id' => $intent->payment_intent_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json(['data' => $intent]);
    }

    private function refreshIntentFromPaymongo(PaymongoIntent $intent): void
    {
        $paymentIntentId = (string) $intent->payment_intent_id;
        if ($paymentIntentId === '') {
            return;
        }

        if (str_starts_with($paymentIntentId, 'cs_')) {
            $payload = $this->service->retrieveCheckoutSession($paymentIntentId);
            if (data_get($payload, 'errors.0.detail')) {
                throw new \RuntimeException('Unable to verify the hosted checkout session.');
            }
            $payments = (array) data_get($payload, 'data.attributes.payments', []);
            $paid = collect($payments)->contains(fn ($payment) => strtolower((string) data_get($payment, 'attributes.status', '')) === 'paid');
            if ($paid) {
                if ($intent->status !== 'succeeded') {
                    $intent->update(['status' => 'succeeded']);
                }
                $this->syncHostedCheckoutPayment($intent->fresh());
            } else {
                $status = strtolower((string) data_get($payload, 'data.attributes.status', ''));
                if (in_array($status, ['expired', 'cancelled', 'canceled', 'failed'], true)) {
                    $intent->update(['status' => $status]);
                    $this->syncEcommerceOrderFromIntent($intent->fresh(), $status);
                }
            }
            return;
        }

        $payload = $this->service->retrieveIntent($paymentIntentId);

        $attrs = data_get($payload, 'data.attributes', []);
        $status = (string) data_get($attrs, 'status', $intent->status);

        $intent->update([
            'status' => $status,
            'client_key' => data_get($attrs, 'client_key', $intent->client_key),
            'metadata' => data_get($attrs, 'metadata', $intent->metadata),
        ]);

        $freshIntent = $intent->fresh();
        if ($freshIntent) {
            $this->syncSalesPaymentFromIntent($freshIntent, $status);
            $this->syncEcommerceOrderFromIntent($freshIntent, $status);
            $this->syncInvoiceFromIntent($freshIntent, $status);
            $this->syncCashflowTopUpFromIntent($freshIntent, $status);
            $this->syncSubscriptionFromIntent($freshIntent, $status);
        }
    }

    public function startGcash(Request $request, string $paymentIntentId): JsonResponse
    {
        return $this->startWallet($request, $paymentIntentId, 'gcash');
    }

    public function startWallet(Request $request, string $paymentIntentId, string $walletType): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'required|string|max:40',
            'return_url' => 'nullable|url|max:500',
        ]);

        $walletType = strtolower(trim($walletType));
        if (!in_array($walletType, ['gcash', 'grab_pay', 'paymaya'], true)) {
            return response()->json([
                'message' => 'Unsupported wallet type.',
            ], 422);
        }

        $paymentMethodPayload = [
            'data' => [
                'attributes' => [
                    'type' => $walletType,
                    'billing' => [
                        'name' => $validated['name'],
                        'email' => $validated['email'],
                        'phone' => $validated['phone'],
                    ],
                ],
            ],
        ];

        $paymentMethod = $this->service->createPaymentMethod($paymentMethodPayload);
        $paymentMethodId = data_get($paymentMethod, 'data.id');
        if (!$paymentMethodId) {
            return response()->json([
                'message' => data_get($paymentMethod, 'errors.0.detail', 'Unable to create wallet payment method.'),
                'errors' => data_get($paymentMethod, 'errors', []),
                'raw' => $paymentMethod,
            ], 422);
        }

        $attachPayload = [
            'data' => [
                'attributes' => [
                    'payment_method' => $paymentMethodId,
                    'return_url' => $validated['return_url'] ?? rtrim(config('app.url'), '/') . '/shop/orders',
                ],
            ],
        ];
        $attached = $this->service->attachIntent($paymentIntentId, $attachPayload);

        $redirectUrl = data_get($attached, 'data.attributes.next_action.redirect.url');
        if (!$redirectUrl) {
            return response()->json([
                'message' => data_get($attached, 'errors.0.detail', 'Unable to start wallet checkout.'),
                'errors' => data_get($attached, 'errors', []),
                'raw' => $attached,
            ], 422);
        }

        PaymongoIntent::where('payment_intent_id', $paymentIntentId)->update([
            'payment_method_id' => $paymentMethodId,
            'status' => data_get($attached, 'data.attributes.status', 'processing'),
            'webhook_payload' => $attached,
        ]);

        return response()->json([
            'message' => 'Wallet checkout initialized.',
            'data' => [
                'redirect_url' => $redirectUrl,
                'payment_intent' => $attached,
            ],
        ]);
    }

    /**
     * Start a card payment by creating a card PaymentMethod and attaching it to a PaymentIntent.
     *
     * NOTE: This collects card data on our server and should only be used as a fallback for development/testing.
     * Prefer Online Payment hosted checkout sessions for production to avoid handling card data.
     */
    public function startCard(Request $request, string $paymentIntentId): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'nullable|string|max:40',
            'return_url' => 'nullable|url|max:500',
            'card_number' => 'required|string|max:32',
            'exp_month' => 'required|integer|min:1|max:12',
            'exp_year' => 'required|integer|min:2000|max:2100',
            'cvc' => 'required|string|max:4',
        ]);

        $paymentMethodPayload = [
            'data' => [
                'attributes' => [
                    'type' => 'card',
                    'details' => [
                        'card_number' => preg_replace('/\D+/', '', (string) $validated['card_number']),
                        'exp_month' => (int) $validated['exp_month'],
                        'exp_year' => (int) $validated['exp_year'],
                        'cvc' => (string) $validated['cvc'],
                    ],
                    'billing' => [
                        'name' => $validated['name'],
                        'email' => $validated['email'],
                        'phone' => $validated['phone'] ?? null,
                    ],
                ],
            ],
        ];

        $paymentMethod = $this->service->createPaymentMethod($paymentMethodPayload);
        $paymentMethodId = data_get($paymentMethod, 'data.id');
        if (!$paymentMethodId) {
            return response()->json([
                'message' => data_get($paymentMethod, 'errors.0.detail', 'Unable to create card payment method.'),
                'errors' => data_get($paymentMethod, 'errors', []),
            ], 422);
        }

        $attachPayload = [
            'data' => [
                'attributes' => [
                    'payment_method' => $paymentMethodId,
                    'return_url' => $validated['return_url'] ?? rtrim(config('app.url'), '/') . '/shop/orders',
                ],
            ],
        ];
        $attached = $this->service->attachIntent($paymentIntentId, $attachPayload);

        $redirectUrl = data_get($attached, 'data.attributes.next_action.redirect.url');
        if (!$redirectUrl) {
            return response()->json([
                'message' => data_get($attached, 'errors.0.detail', 'Unable to start card checkout.'),
                'errors' => data_get($attached, 'errors', []),
            ], 422);
        }

        PaymongoIntent::where('payment_intent_id', $paymentIntentId)->update([
            'payment_method_id' => $paymentMethodId,
            'status' => data_get($attached, 'data.attributes.status', 'processing'),
            'webhook_payload' => $attached,
        ]);

        return response()->json([
            'message' => 'Card checkout initialized.',
            'data' => [
                'redirect_url' => $redirectUrl,
                'payment_intent' => $attached,
            ],
        ]);
    }

    private function resolveStorePaymongoMethods(?Store $store): array
    {
        $methods = data_get($store?->settings, 'payments.paymongo.payment_method_allowed');
        if (!is_array($methods) || count($methods) === 0) {
            return ['gcash'];
        }
        $normalized = array_values(array_unique(array_map(fn($m) => strtolower(trim((string) $m)), $methods)));
        $supported = ['gcash', 'grab_pay', 'paymaya', 'card'];
        $allowed = array_values(array_intersect($normalized, $supported));
        return count($allowed) ? $allowed : ['gcash'];
    }

    public function availableCheckoutMethods(Request $request): JsonResponse
    {
        $validated = $request->validate(['store_id' => ['required', 'integer', 'exists:stores,id']]);
        if (!$this->service->isConfigured()) {
            return response()->json(['message' => 'Online payment is not configured.'], 503);
        }

        $store = Store::query()->findOrFail((int) $validated['store_id']);
        return response()->json([
            'data' => [
                'methods' => $this->availableHostedMethods(
                    $store,
                    str_starts_with((string) config('paymongo.secret'), 'sk_test_') ? [] : $this->service->activePaymentMethods()
                ),
            ],
        ]);
    }

    private function availableHostedMethods(?Store $store, array $accountMethods): array
    {
        if (str_starts_with((string) config('paymongo.secret'), 'sk_test_')) {
            return ['gcash', 'card', 'paymaya'];
        }

        $supported = ['gcash', 'card', 'paymaya', 'grab_pay'];
        $methods = count($accountMethods) ? $accountMethods : $this->resolveStorePaymongoMethods($store);

        return array_values(array_intersect($supported, $methods));
    }

    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Paymongo-Signature') ?? $request->header('paymongo-signature');

        if (!$this->service->verifySignature($payload, $signature)) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $event = json_decode($payload, true);
        $eventType = (string) (data_get($event, 'data.attributes.type') ?? data_get($event, 'data.type'));
        if ($eventType === 'checkout_session.payment.paid') {
            $session = data_get($event, 'data.attributes.data') ?? data_get($event, 'data.data');
            $sessionId = (string) data_get($session, 'id', '');
            $intent = PaymongoIntent::query()->where('payment_intent_id', $sessionId)
                ->whereIn('payable_type', ['ecommerce_order', 'subscription_upgrade', 'invoice', 'sales_order'])->first();
            // The signed paid event is authoritative even when PayMongo omits payment details.
            if ($intent) {
                if ($intent->status !== 'succeeded') {
                    $intent->update(['status' => 'succeeded', 'webhook_payload' => $event]);
                }
                $this->syncHostedCheckoutPayment($intent->fresh());
            }
            return response()->json(['message' => $intent ? 'Event processed' : 'Ignored event']);
        }
        $intent = $this->service->updateStatusFromWebhook($event);

        if ($intent) {
            $this->syncSalesPaymentFromIntent($intent, (string) $intent->status);
            $this->syncEcommerceOrderFromIntent($intent, (string) $intent->status);
            $this->syncInvoiceFromIntent($intent, (string) $intent->status);
            $this->syncCashflowTopUpFromIntent($intent, (string) $intent->status);
            $this->syncSubscriptionFromIntent($intent, (string) $intent->status);
            return response()->json(['message' => 'Event processed']);
        }

        return response()->json(['message' => 'Ignored event'], 200);
    }

    private function syncHostedCheckoutPayment(PaymongoIntent $intent): void
    {
        $this->syncSalesPaymentFromIntent($intent, 'succeeded');
        $this->syncEcommerceOrderFromIntent($intent, 'succeeded');
        $this->syncInvoiceFromIntent($intent, 'succeeded');
        $this->syncSubscriptionFromIntent($intent, 'succeeded');
    }

    private function syncSalesPaymentFromIntent(PaymongoIntent $intent, string $paymongoStatus): void
    {
        if ($intent->payable_type !== 'sales_order') {
            return;
        }

        $payment = SalesPayment::query()
            ->where('sales_order_id', (int) $intent->payable_id)
            ->where('provider_reference', $intent->payment_intent_id)
            ->latest('id')
            ->first();

        if (!$payment) {
            return;
        }

        $statusMap = [
            'awaiting_payment_method' => 'awaiting_payment_method',
            'awaiting_next_action' => 'processing',
            'processing' => 'processing',
            'succeeded' => 'paid',
            'failed' => 'failed',
            'cancelled' => 'cancelled',
        ];

        $nextPaymentStatus = $statusMap[$paymongoStatus] ?? $payment->status;
        $payment->update([
            'status' => $nextPaymentStatus,
            'metadata' => array_merge((array) $payment->metadata, ['paymongo_status' => $paymongoStatus]),
            'paid_at' => $nextPaymentStatus === 'paid' ? now() : $payment->paid_at,
        ]);

        if ($nextPaymentStatus === 'paid' && $payment->order) {
            try {
                $this->salesSettlementService->settlePaid(
                    $payment->order,
                    (string) $payment->payment_method,
                    (string) $intent->payment_intent_id,
                    $payment
                );
            } catch (\Throwable $e) {
                Log::error('Sales order settlement failed after Online Payment paid event.', [
                    'order_id' => $payment->sales_order_id,
                    'intent' => $intent->payment_intent_id,
                    'error' => $e->getMessage(),
                ]);
                $this->salesSettlementService->markPaymentFailed($payment->order, $payment);
            }
        }

        if ($nextPaymentStatus === 'failed' && $payment->order) {
            $this->salesSettlementService->markPaymentFailed($payment->order, $payment);
        }
    }

    private function syncEcommerceOrderFromIntent(PaymongoIntent $intent, string $paymongoStatus): void
    {
        if ($intent->payable_type !== 'ecommerce_order') {
            return;
        }

        $order = EcommerceOrder::query()->find((int) $intent->payable_id);
        if (!$order) {
            return;
        }

        $normalizedStatus = strtolower(trim($paymongoStatus));
        $nextOrderPaymentStatus = match ($normalizedStatus) {
            'succeeded' => 'paid',
            'failed', 'cancelled', 'canceled', 'expired' => 'failed',
            default => 'unpaid',
        };

        $previousStatus = (string) $order->payment_status;
        if ($previousStatus === 'paid' && $nextOrderPaymentStatus !== 'paid') {
            return;
        }
        if ($order->payment_status !== $nextOrderPaymentStatus) {
            $order->update([
                'payment_status' => $nextOrderPaymentStatus,
            ]);
        }

        if ($nextOrderPaymentStatus === 'paid' && !in_array($order->status, ['cancelled', 'canceled'], true)
            && ($previousStatus !== 'paid' || !empty(((array) ($order->pending_snapshot ?? []))['items']))) {
            // If the order was created as a Online Payment "pending snapshot" (no order items yet),
            // finalize the order items + inventory only after successful payment.
            try {
                $this->finalizeEcommerceOrderFromSnapshot($order);
            } catch (\Throwable $e) {
                Log::error('Failed to finalize ecommerce order snapshot after payment success.', [
                    'order_id' => $order->id,
                    'intent' => $intent->payment_intent_id,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                $cashflow = new CashflowService();
                $cashflow->credit(
                    (int) $order->store_id,
                    (float) $order->total_amount,
                    'ecommerce_order',
                    (int) $order->id,
                    Auth::id(),
                    'Ecommerce payment ' . ($order->order_number ?? ('#' . $order->id)),
                    'paymongo',
                    [
                        'payment_intent_id' => $intent->payment_intent_id,
                    ]
                );
            } catch (\Throwable $e) {
                Log::error('Failed to record ecommerce cashflow after payment success.', [
                    'order_id' => $order->id,
                    'intent' => $intent->payment_intent_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function finalizeEcommerceOrderFromSnapshot(EcommerceOrder $order): void
    {
        $snapshot = (array) ($order->pending_snapshot ?? []);
        $items = $snapshot['items'] ?? null;
        if (!is_array($items) || count($items) === 0) {
            return;
        }

        DB::transaction(function () use ($order, $items) {
            // Avoid double-finalization
            $fresh = EcommerceOrder::query()->lockForUpdate()->find($order->id);
            if (!$fresh) {
                return;
            }
            $snapshotFresh = (array) ($fresh->pending_snapshot ?? []);
            $itemsFresh = $snapshotFresh['items'] ?? null;
            if (!is_array($itemsFresh) || count($itemsFresh) === 0) {
                return;
            }

            $branchId = (int) ($fresh->assigned_branch_id ?? 0);
            if ($branchId <= 0 || !Branch::query()->whereKey($branchId)
                ->where('store_id', $fresh->store_id)
                ->where('branch_type', 'storefront')
                ->where('status', 'active')->exists()) {
                throw new \RuntimeException("Order {$fresh->id} has no active storefront fulfillment branch.");
            }

            foreach ($itemsFresh as $row) {
                $productId = (int) ($row['product_id'] ?? 0);
                $variationId = $row['variation_id'] ?? null;
                $quantity = (int) ($row['quantity'] ?? 0);
                $unitPrice = (float) ($row['unit_price'] ?? 0);
                $taxRate = 12.0;
                $variationName = $row['variation_name'] ?? null;

                if ($productId <= 0 || $quantity <= 0) {
                    continue;
                }

                $inventory = BranchInventory::query()
                    ->where('store_id', $fresh->store_id)
                    ->where('branch_id', $branchId)
                    ->where('product_id', $productId)
                    ->when($variationId, fn($q) => $q->where('variation_id', (int) $variationId), fn($q) => $q->whereNull('variation_id'))
                    ->when(!empty($snapshotFresh['stock_reserved']), fn($q) => $q->whereKey((int) ($row['branch_inventory_id'] ?? 0)))
                    ->when(empty($snapshotFresh['stock_reserved']), fn($q) => $q->where('quantity_available', '>=', $quantity))
                    ->orderByDesc('quantity_available')
                    ->lockForUpdate()
                    ->first();

                if (!$inventory) {
                    throw new \RuntimeException("Insufficient stock for product_id={$productId} (variation_id={$variationId})");
                }

                $product = Product::query()->find($productId);
                $variation = $variationId ? ProductVariation::query()->find((int) $variationId) : null;

                $lineSubtotal = $unitPrice * $quantity;
                $lineTax = round($lineSubtotal - ($lineSubtotal / 1.12), 2);
                $fresh->items()->create([
                    'product_id' => $productId,
                    'branch_inventory_id' => $inventory->id,
                    'product_name' => $variationName
                        ? (($product?->product_name ?? 'Product') . ' - ' . $variationName)
                        : ($product?->product_name ?? 'Product'),
                    'sku' => $variation?->variation_sku ?? $product?->sku,
                    'customization_request' => $row['customization_request'] ?? null,
                    'customization_status' => !empty($row['customization_request']) ? 'pending' : null,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'original_unit_price' => isset($row['original_unit_price'])
                        ? (float) $row['original_unit_price'] : null,
                    'tax_rate' => $taxRate,
                    'line_subtotal' => round($lineSubtotal, 2),
                    'line_tax' => $lineTax,
                    'line_total' => round($lineSubtotal, 2),
                ]);

                if (!empty($snapshotFresh['stock_reserved']) && (int) $inventory->quantity_reserved < $quantity) {
                    throw new \RuntimeException("Reserved stock is missing for product_id={$productId}");
                }
                if (!empty($snapshotFresh['stock_reserved'])) {
                    $inventory->quantity_reserved -= $quantity;
                } else {
                    $inventory->quantity_available -= $quantity;
                }
                $inventory->quantity_on_hand = max(0, (int) $inventory->quantity_on_hand - $quantity);
                $inventory->updateStockStatus();
            }

            // Remove cart items now that the order has been finalized.
            $pendingCartId = (int) ($fresh->pending_cart_id ?? 0);
            $cartItemIds = collect($itemsFresh)->pluck('cart_item_id')->filter()->map(fn($v) => (int) $v)->values();
            if ($pendingCartId > 0 && $cartItemIds->count() > 0) {
                EcommerceCartItem::query()
                    ->where('cart_id', $pendingCartId)
                    ->whereIn('id', $cartItemIds->all())
                    ->delete();
            }

            $vatInclusiveProductTotal = max(
                0,
                (float) $fresh->subtotal - (float) $fresh->discount_amount
            );
            $taxAmount = round(
                $vatInclusiveProductTotal - ($vatInclusiveProductTotal / 1.12),
                2
            );

            $fresh->update([
                'tax_amount' => $taxAmount,
                'pending_snapshot' => null,
                'pending_cart_id' => null,
            ]);
        });
    }

    private function syncSubscriptionFromIntent(PaymongoIntent $intent, string $paymongoStatus): void
    {
        if ($intent->payable_type !== 'subscription_upgrade') {
            return;
        }

        if (strtolower(trim($paymongoStatus)) !== 'succeeded') {
            return;
        }

        // A successful intent may be synchronized by both the webhook and the
        // checkout return page. The revenue reference is unique and records
        // that this exact payment has already extended the subscription.
        if (PlatformRevenue::query()->where('reference', $intent->payment_intent_id)->exists()) {
            return;
        }

        $metadata = is_array($intent->metadata) ? $intent->metadata : [];
        $storeId = (int) ($intent->store_id ?: ($metadata['store_id'] ?? $intent->payable_id));
        if ($storeId <= 0) {
            return;
        }

        $store = Store::find($storeId);
        if (!$store) {
            return;
        }

        $months = max(1, (int) ($metadata['months'] ?? 1));
        $targetPlanKey = strtolower(trim((string) ($metadata['subscription_tier'] ?? 'premium')));
        $targetPlan = \App\Models\Admin\SubscriptionPlan::query()
            ->where('plan_key', $targetPlanKey)
            ->where('is_active', true)
            ->first();
        if (!$targetPlan) {
            Log::warning('Paid subscription references an unknown plan.', ['plan_key' => $targetPlanKey]);
            return;
        }

        $baseDate = $store->subscriptionPlan?->plan_key !== 'free' && $store->subscription_ends_at
            ? Carbon::parse($store->subscription_ends_at)
            : now();
        if ($baseDate->lt(now())) {
            $baseDate = now();
        }
        $newEndsAt = $baseDate->copy()->addMonths($months);

        $store->update([
            'subscription_tier' => $targetPlan->id,
            'subscription_ends_at' => $newEndsAt->toDateString(),
            'subscription_status' => 'paid',
            'status' => $store->subscription_status === 'expired' && $store->status === 'inactive' ? 'active' : $store->status,
        ]);
        app(\App\Services\Modules\ModuleAccessService::class)->syncStoreModulesFromPlan((int) $store->id);
        app(\App\Services\Core\PermissionService::class)->clearStoreCache((int) $store->id);

        if ($targetPlanKey === 'unlimited') {
            $moduleIds = \DB::table('modules')
                ->whereIn('key', self::ALL_STORE_MODULES)
                ->pluck('id')
                ->all();

            foreach ($moduleIds as $moduleId) {
                \DB::table('store_modules')->updateOrInsert(
                    ['store_id' => $store->id, 'module_id' => $moduleId],
                    [
                        'status' => 'enabled',
                        'source' => 'manual',
                        'enabled_at' => now(),
                        'enabled_by' => $store->owner_id ?? null,
                    ]
                );
            }
        }

        try {
            PlatformRevenue::firstOrCreate(
                ['reference' => $intent->payment_intent_id],
                [
                    'store_id' => $store->id,
                    'source' => 'subscription_upgrade',
                    'amount' => ((float) $intent->amount) / 100,
                    'currency' => $intent->currency ?: 'PHP',
                    'metadata' => array_merge($metadata, [
                        'paymongo_status' => $paymongoStatus,
                        'payable_type' => $intent->payable_type,
                    ]),
                    'paid_at' => now(),
                ]
            );
        } catch (\Throwable $e) {
            // Revenue recording must not block payment status synchronization.
            Log::error('Failed to record platform revenue for subscription upgrade.', [
                'intent_id' => $intent->payment_intent_id,
                'store_id' => $store->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function syncInvoiceFromIntent(PaymongoIntent $intent, string $paymongoStatus): void
    {
        if ($intent->payable_type !== 'invoice') {
            return;
        }

        $invoice = Invoice::query()->find((int) $intent->payable_id);
        if (!$invoice) {
            return;
        }

        $normalizedStatus = strtolower(trim($paymongoStatus));
        if (!in_array($normalizedStatus, ['succeeded', 'paid'], true)) {
            return;
        }

        // Prevent duplicate accounting updates when invoice was already settled manually.
        if (strtolower((string) $invoice->payment_status) === 'paid') {
            return;
        }

        $paymentAmount = (float) ($invoice->net_amount ?: $invoice->invoice_amount ?: 0);

        DB::transaction(function () use ($invoice, $intent, $paymentAmount) {
            // Keep invoice state in sync with successful Online Payment payment.
            $invoice->update([
                'payment_status' => 'paid',
                'payment_method' => 'paymongo',
                'payment_amount' => $paymentAmount,
                'payment_date' => now()->toDateString(),
                'status' => 'paid',
                'paid_to_bank_name' => $invoice->supplier?->bank_name,
                'paid_to_account_name' => $invoice->supplier?->bank_account_name,
                'paid_to_account_number_masked' => $this->maskAccountNumber($invoice->supplier?->bank_account_number),
                'paid_to_account_type' => $invoice->supplier?->bank_account_type,
                'paid_to_bank_branch' => $invoice->supplier?->bank_branch,
            ]);

            if ($invoice->purchaseOrder) {
                $invoice->purchaseOrder->update(['payment_status' => 'paid']);
            }

            if ($invoice->supplier_id) {
                \App\Models\Procurement\Supplier\Supplier::where('id', (int) $invoice->supplier_id)
                    ->update([
                        'current_balance' => DB::raw('GREATEST(COALESCE(current_balance, 0), 0) + ' . ((float) $paymentAmount))
                    ]);
            }

            // Ensure linked finance expense also reflects paid state.
            $financeService = new FinanceExpenseService();
            $expense = $financeService->ensureExpense([
                'store_id' => $invoice->store_id,
                'department' => 'procurement',
                'category' => 'supplier_invoice',
                'amount' => $paymentAmount,
                'expense_date' => $invoice->invoice_date,
                'status' => 'pending_approval',
                'reference_number' => $invoice->invoice_number,
                'reference_type' => 'invoice',
                'reference_id' => $invoice->id,
                'currency' => $invoice->currency ?? 'PHP',
                'description' => "Supplier invoice {$invoice->invoice_number}",
                'notes' => $invoice->remarks,
                'requested_by' => auth()->id(),
            ], true, auth()->id());

            if ($expense->status !== 'paid') {
                $expense->update([
                    'status' => 'paid',
                    'payment_method' => 'paymongo',
                    'payment_date' => now()->toDateString(),
                    'paid_by' => auth()->id(),
                    'paid_at' => now(),
                ]);
            }

            // Record cashflow outflow once per invoice.
            $alreadyRecorded = DB::table('finance_cashflow_transactions')
                ->where('store_id', (int) $invoice->store_id)
                ->where('direction', 'out')
                ->where('reference_type', 'invoice')
                ->where('reference_id', (int) $invoice->id)
                ->exists();

            if (!$alreadyRecorded && $paymentAmount > 0) {
                $cashflow = new CashflowService();
                $cashflow->debit(
                    (int) $invoice->store_id,
                    $paymentAmount,
                    'invoice',
                    (int) $invoice->id,
                    auth()->id(),
                    'Invoice payment ' . ($invoice->invoice_number ?? ('#' . $invoice->id)) . ' via Online Payment',
                    'paymongo',
                    [
                        'payment_intent_id' => $intent->payment_intent_id,
                    ]
                );
            }
        });

        $supplier = $invoice->supplier;
        $supplierEmail = strtolower(trim((string) ($supplier?->email ?? '')));
        if ($supplierEmail !== '') {
            $portalUserIds = SupplierPortal::query()
                ->whereHas('supplier', function ($q) use ($supplierEmail) {
                    $q->whereRaw('LOWER(email) = ?', [$supplierEmail]);
                })
                ->pluck('user_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            foreach ($portalUserIds as $userId) {
                $this->notify((int) $userId, [
                    'store_id' => (int) $invoice->store_id,
                    'module' => 'supplier',
                    'entity_type' => 'invoice',
                    'entity_id' => (int) $invoice->id,
                    'action' => 'invoice_paid',
                    'title' => 'Invoice Paid',
                    'message' => "Your invoice {$invoice->invoice_number} has been paid by Finance.",
                    'severity' => 'success',
                    'link' => "/supplier-portal/pos/{$invoice->purchase_order_id}/invoice-view",
                    'data' => [
                        'invoice_id' => (int) $invoice->id,
                        'invoice_number' => (string) $invoice->invoice_number,
                        'payment_amount' => $paymentAmount,
                        'payment_date' => now()->toDateString(),
                    ],
                ]);
            }
        }
    }

    private function syncCashflowTopUpFromIntent(PaymongoIntent $intent, string $paymongoStatus): void
    {
        if ($intent->payable_type !== 'cashflow_topup') {
            return;
        }

        $normalizedStatus = strtolower(trim($paymongoStatus));
        if (!in_array($normalizedStatus, ['succeeded', 'paid'], true)) {
            return;
        }

        $meta = is_array($intent->metadata) ? $intent->metadata : [];
        $storeId = (int) ($intent->store_id ?: ($meta['store_id'] ?? $intent->payable_id));
        if ($storeId <= 0) {
            return;
        }

        $amount = round(((float) ($intent->amount ?? 0)) / 100, 2);
        if ($amount <= 0) {
            return;
        }

        $alreadyRecorded = FinanceCashflowTransaction::query()
            ->where('store_id', $storeId)
            ->where('direction', 'in')
            ->where('reference_type', 'cashflow_topup')
            ->where('reference_id', (int) $intent->id)
            ->exists();

        if ($alreadyRecorded) {
            return;
        }

        $cashflow = new CashflowService();
        $cashflow->credit(
            $storeId,
            $amount,
            'cashflow_topup',
            (int) $intent->id,
            Auth::id(),
            'Cashflow top-up via Online Payment',
            'paymongo_gcash',
            [
                'payment_intent_id' => $intent->payment_intent_id,
            ]
        );
    }

    private function normalizeMetadata(?array $metadata): array
    {
        if (!$metadata) {
            return [];
        }

        $normalized = [];
        foreach ($metadata as $key => $value) {
            $metaKey = trim((string) $key);
            if ($metaKey === '') {
                continue;
            }

            if (is_array($value) || is_object($value)) {
                $encoded = json_encode($value);
                if ($encoded === false) {
                    continue;
                }
                $normalized[$metaKey] = $encoded;
                continue;
            }

            if (is_bool($value)) {
                $normalized[$metaKey] = $value ? 'true' : 'false';
                continue;
            }

            if (is_scalar($value)) {
                $normalized[$metaKey] = (string) $value;
            }
        }

        return $normalized;
    }

    private function resolveStoreIdFromAuthUser(): int
    {
        $user = Auth::user();
        if (!$user) {
            return 0;
        }

        if (!empty($user->store_id)) {
            return (int) $user->store_id;
        }

        $employee = Employee::query()
            ->where('user_id', $user->id)
            ->first(['store_id', 'branch_id']);

        if (!empty($employee?->store_id)) {
            return (int) $employee->store_id;
        }

        if (!empty($employee?->branch_id)) {
            $branchStoreId = Branch::query()
                ->where('id', (int) $employee->branch_id)
                ->value('store_id');

            return (int) ($branchStoreId ?? 0);
        }

        return 0;
    }

    private function hasCompleteSupplierPaymentAccount(int $supplierId): bool
    {
        if ($supplierId <= 0) {
            return false;
        }

        $supplier = Supplier::query()->find($supplierId);
        if (!$supplier) {
            return false;
        }

        return filled($supplier->bank_name)
            && filled($supplier->bank_account_name)
            && filled($supplier->bank_account_number);
    }

    private function maskAccountNumber(?string $accountNumber): ?string
    {
        $raw = trim((string) $accountNumber);
        if ($raw === '') {
            return null;
        }
        $len = strlen($raw);
        if ($len <= 4) {
            return str_repeat('*', max(0, $len - 1)) . substr($raw, -1);
        }
        return str_repeat('*', $len - 4) . substr($raw, -4);
    }
}
