<?php

namespace App\Http\Controllers\Api\Payments;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\EcommerceOrder;
use App\Models\PlatformRevenue;
use App\Models\PaymongoIntent;
use App\Models\Sales\SalesPayment;
use App\Models\Store\Store;
use App\Models\Hr\Employee;
use App\Models\Store\Branch;
use App\Services\Finance\CashflowService;
use App\Services\Payment\PaymongoService;
use App\Services\Sales\SalesOrderSettlementService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $data = $request->validate([
            'amount' => 'required|integer|min:1',
            'currency' => 'string|min:3|max:3',
            'description' => 'nullable|string',
            'statement_descriptor' => 'nullable|string',
            'payment_method_allowed' => 'required|array',
            'metadata' => 'nullable|array',
            'store_id' => 'nullable|integer|exists:stores,id',
            'payable_type' => 'required|string',
            'payable_id' => 'nullable|integer',
        ]);

        $resolvedStoreId = (int) ($data['store_id'] ?? 0);
        if ($resolvedStoreId <= 0) {
            $resolvedStoreId = $this->resolveStoreIdFromAuthUser();
        }

        if ($resolvedStoreId <= 0) {
            return response()->json([
                'message' => 'Store not found for this account.',
            ], 422);
        }

        if (($data['payable_type'] ?? '') === 'subscription_upgrade' && empty($data['payable_id'])) {
            $data['payable_id'] = $resolvedStoreId;
        }

        if (empty($data['payable_id'])) {
            return response()->json([
                'message' => 'Payable id is required.',
            ], 422);
        }

        $attributes = [
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'PHP',
            'capture_type' => 'automatic',
            'description' => $data['description'] ?? null,
            'statement_descriptor' => $data['statement_descriptor'] ?? null,
            'payment_method_allowed' => $data['payment_method_allowed'],
        ];

        // PayMongo rejects blank metadata, so only send it when it has values.
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

        // Do not persist anything when PayMongo returned an error payload.
        if (!$intentId) {
            $firstError = data_get($intentPayload, 'errors.0.detail');

            return response()->json([
                'message' => $firstError ?: 'PayMongo intent creation failed.',
                'errors' => data_get($intentPayload, 'errors', []),
                'raw' => $intentPayload,
            ], 422);
        }

        $attrs = data_get($intentPayload, 'data.attributes', []);
        $intent = $this->service->logIntent([
            'store_id' => $resolvedStoreId,
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

    public function status(string $paymentIntentId): JsonResponse
    {
        $intent = PaymongoIntent::where('payment_intent_id', $paymentIntentId)->first();
        $payload = $this->service->retrieveIntent($paymentIntentId);

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
            $this->syncSubscriptionFromIntent($freshIntent, $status);
        }

        return response()->json(['data' => $payload]);
    }

    public function latestByPayable(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payable_type' => 'required|string|max:100',
            'payable_id' => 'required|integer|min:1',
        ]);

        $intent = PaymongoIntent::query()
            ->where('payable_type', $validated['payable_type'])
            ->where('payable_id', $validated['payable_id'])
            ->latest('id')
            ->first();

        if (!$intent) {
            return response()->json(['data' => null]);
        }

        return response()->json(['data' => $intent]);
    }

    public function startGcash(Request $request, string $paymentIntentId): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'required|string|max:40',
            'return_url' => 'nullable|url|max:500',
        ]);

        $paymentMethodPayload = [
            'data' => [
                'attributes' => [
                    'type' => 'gcash',
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
                'message' => data_get($paymentMethod, 'errors.0.detail', 'Unable to create GCash payment method.'),
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
                'message' => data_get($attached, 'errors.0.detail', 'Unable to start GCash checkout.'),
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
            'message' => 'GCash checkout initialized.',
            'data' => [
                'redirect_url' => $redirectUrl,
                'payment_intent' => $attached,
            ],
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Paymongo-Signature') ?? $request->header('paymongo-signature');

        if (!$this->service->verifySignature($payload, $signature)) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $event = json_decode($payload, true);
        $intent = $this->service->updateStatusFromWebhook($event);

        if ($intent) {
            $this->syncSalesPaymentFromIntent($intent, (string) $intent->status);
            $this->syncEcommerceOrderFromIntent($intent, (string) $intent->status);
            $this->syncSubscriptionFromIntent($intent, (string) $intent->status);
            return response()->json(['message' => 'Event processed']);
        }

        return response()->json(['message' => 'Ignored event'], 200);
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
                Log::error('Sales order settlement failed after PayMongo paid event.', [
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
            'failed', 'cancelled', 'canceled' => 'failed',
            default => 'unpaid',
        };

        $previousStatus = (string) $order->payment_status;
        if ($order->payment_status !== $nextOrderPaymentStatus) {
            $order->update([
                'payment_status' => $nextOrderPaymentStatus,
            ]);
        }

        if ($previousStatus !== 'paid' && $nextOrderPaymentStatus === 'paid') {
            try {
                $cashflow = new CashflowService();
                $cashflow->credit(
                    (int) $order->store_id,
                    (float) $order->total_amount,
                    'ecommerce_order',
                    (int) $order->id,
                    Auth::id(),
                    'Ecommerce payment ' . ($order->order_number ?? ('#' . $order->id)),
                    'paymongo_gcash',
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

    private function syncSubscriptionFromIntent(PaymongoIntent $intent, string $paymongoStatus): void
    {
        if ($intent->payable_type !== 'subscription_upgrade') {
            return;
        }

        if (strtolower(trim($paymongoStatus)) !== 'succeeded') {
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
        $targetTier = (string) ($metadata['subscription_tier'] ?? 'premium');

        $baseDate = $store->subscription_ends_at
            ? Carbon::parse($store->subscription_ends_at)
            : now();
        if ($baseDate->lt(now())) {
            $baseDate = now();
        }
        $newEndsAt = $baseDate->copy()->addMonths($months);

        $store->update([
            'subscription_tier' => $targetTier,
            'subscription_ends_at' => $newEndsAt,
        ]);

        if (strtolower($targetTier) === 'unlimited') {
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
}
