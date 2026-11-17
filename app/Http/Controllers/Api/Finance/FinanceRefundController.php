<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\CRM\EcommerceOrderReturn;
use App\Models\Ecommerce\EcommerceOrder;
use App\Models\Finance\FinanceRefund;
use App\Models\PaymongoIntent;
use App\Models\Sales\SalesOrder;
use App\Services\Finance\ReturnRefundAmountService;
use App\Services\Payment\PaymongoService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinanceRefundController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $storeId = (int) $request->user()->store_id;
        $query = FinanceRefund::query()->with(['branch:id,name', 'requester:id,fname,lname'])
            ->where('store_id', $storeId);

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            if ($status === 'refunded') {
                $query->whereIn('status', ['sent', 'simulated']);
            } elseif ($status === 'awaiting_payout') {
                $query->where(fn ($q) => $q->where('status', 'awaiting_payout')
                    ->orWhere(fn ($legacy) => $legacy->where('status', 'sent')
                        ->whereIn('payout_provider', ['simulation', 'paymongo_wallet_test'])));
            } else {
                $query->where('status', $status);
            }
        }
        if ($request->filled('source') && $request->string('source')->toString() !== 'all') $query->where('order_type', $request->string('source'));
        if ($request->filled('search')) {
            $term = trim($request->string('search')->toString());
            $query->where(fn ($q) => $q->where('order_number', 'like', "%{$term}%")
                ->orWhere('customer_name', 'like', "%{$term}%")->orWhere('reason', 'like', "%{$term}%"));
        }

        $periodEnd = now()->endOfDay();
        $periodStart = $periodEnd->copy()->subDays(27)->startOfDay();
        $previousEnd = $periodStart->copy()->subDay()->endOfDay();
        $previousStart = $previousEnd->copy()->subDays(27)->startOfDay();
        $summarizePeriod = static function (Carbon $start, Carbon $end) use ($storeId): array {
            $periodQuery = FinanceRefund::query()
                ->where('store_id', $storeId)
                ->whereBetween('created_at', [$start, $end]);
            $inspection = (clone $periodQuery)->where('status', 'pending_inspection')->count();
            $pending = (clone $periodQuery)->where('status', 'pending')->count();
            $awaitingPayout = (clone $periodQuery)->where(function ($query) {
                $query->whereIn('status', ['approved', 'awaiting_payout'])
                    ->orWhere(function ($legacy) {
                        $legacy->where('status', 'sent')->whereIn('payout_provider', ['simulation', 'paymongo_wallet_test']);
                    });
            });
            $amountToSend = (clone $periodQuery)->where(function ($query) {
                $query->whereIn('status', ['pending', 'approved', 'awaiting_payout'])
                    ->orWhere(function ($legacy) {
                        $legacy->where('status', 'sent')->whereIn('payout_provider', ['simulation', 'paymongo_wallet_test']);
                    });
            });
            $simulated = (clone $periodQuery)->where('status', 'simulated')->count();

            return [
                'inspection' => $inspection,
                'pending' => $pending,
                'awaiting_payout' => (clone $awaitingPayout)->count(),
                'simulated' => $simulated,
                'amount_to_send' => (float) $amountToSend->sum('amount'),
            ];
        };

        return response()->json([
            'data' => $query->latest()->get(),
            'summary' => [
                'current' => $summarizePeriod($periodStart, $periodEnd),
                'previous' => $summarizePeriod($previousStart, $previousEnd),
                'period' => [
                    'start' => $periodStart->toDateString(),
                    'end' => $periodEnd->toDateString(),
                    'previous_start' => $previousStart->toDateString(),
                    'previous_end' => $previousEnd->toDateString(),
                ],
            ],
        ]);
    }

    public function show(Request $request, FinanceRefund $refund): JsonResponse
    {
        $this->authorizeStore($request, $refund);
        $refund->load([
            'branch:id,name,address,contact_number', 'requester:id,fname,lname,email',
            'processor:id,fname,lname,email', 'sender:id,fname,lname,email',
            'ecommerceReturn' => fn ($q) => $q->with([
                'order:id,order_number,shipping_name,shipping_phone,shipping_email,shipping_address,total_amount,status',
                'orderItem:id,order_id,product_id,product_name,sku,quantity,unit_price',
                'salesOrder:id,order_number,branch_id,status,customer_name,customer_phone,delivery_email,delivery_address',
                'salesOrderItem:id,product_id,variation_id,branch_inventory_id,product_name,sku,quantity,unit_price',
                'salesOrderItem.product:id,product_name,sku',
                'pickup:id,return_id,status,destination_branch_id,delivered_at',
                'pickup.destinationBranch:id,name,address',
            ]),
        ]);
        $refund->ecommerceReturn?->normalizeSourceRelationsForDisplay();
        if ($refund->order_type !== 'ecommerce_return') {
            $refund->setRelation('ecommerceReturn', null);
        }
        $originalOrder = $this->ecommerceOrderForRefund($refund);
        $amountCalculation = null;
        if ($refund->order_type === 'ecommerce_return') {
            $return = EcommerceOrderReturn::query()->where('store_id', $refund->store_id)->find($refund->order_id);
            if ($return) $amountCalculation = app(ReturnRefundAmountService::class)->calculate($return);
        }
        $refundDestinationRequired = !$this->hasPayoutRecipient($refund);
        $refund->setAttribute('refund_destination_required', $refundDestinationRequired);
        return response()->json([
            'data' => $refund,
            'refund_destination_required' => $refundDestinationRequired,
            'amount_calculation' => $amountCalculation,
            'shipping_fee_excluded' => $refund->order_type === 'ecommerce_order' ? (float) ($originalOrder?->shipping_fee ?? 0) : null,
            'payable_refund_amount' => $refund->order_type === 'ecommerce_order' && $originalOrder
                ? min((float) $refund->amount, $this->refundableOrderTotal($originalOrder))
                : (float) $refund->amount,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_type' => 'required|in:sales,manual', 'order_id' => 'required|integer|min:1',
            'order_number' => 'nullable|string|max:100', 'customer_name' => 'required|string|max:190',
            'reason' => 'required|string|max:2000', 'amount' => 'required|numeric|min:0.01', 'notes' => 'nullable|string|max:2000',
            'refund_method' => 'required|in:gcash,maya',
            'refund_account_name' => 'required|string|max:255',
            'refund_account_number' => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
        ]);
        if ($data['order_type'] === 'sales') {
            $sale = SalesOrder::query()->where('store_id', $request->user()->store_id)
                ->where('payment_status', 'paid')->find($data['order_id']);
            if (!$sale || (float) $data['amount'] > (float) $sale->total_amount) {
                throw ValidationException::withMessages(['order_id' => ['Select a paid sale from this store with enough refundable value.']]);
            }
            if (FinanceRefund::query()->where('store_id', $sale->store_id)->where('order_type', 'sales')
                ->where('order_id', $sale->id)->whereNotIn('status', ['rejected'])->exists()) {
                throw ValidationException::withMessages(['order_id' => ['A refund request already exists for this sale.']]);
            }
            $data['order_number'] = $sale->order_number;
            $data['branch_id'] = $sale->branch_id;
        }
        $refund = FinanceRefund::create([...$data, 'store_id' => $request->user()->store_id,
            'branch_id' => $data['branch_id'] ?? $request->user()->employee?->branch_id,
            'status' => 'pending', 'requested_by' => $request->user()->id]);
        return response()->json(['message' => 'Refund request created.', 'data' => $refund], 201);
    }

    public function updateStatus(Request $request, FinanceRefund $refund): JsonResponse
    {
        $this->authorizeStore($request, $refund);
        $data = $request->validate([
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|string|max:2000',
            'amount' => 'nullable|numeric|min:0.01',
            'adjustment_reason' => 'nullable|string|max:1000',
        ]);
        $linkedReturn = null;

        DB::transaction(function () use ($request, $refund, $data, &$linkedReturn) {
            $refund = FinanceRefund::query()->lockForUpdate()->findOrFail($refund->id);
            if ($refund->status !== 'pending') throw ValidationException::withMessages(['status' => ['Only a pending refund can be processed.']]);
            if ($data['status'] === 'approved') {
                $this->normalizeCancelledOrderRefundAmount($refund);
                if ($refund->order_type === 'ecommerce_return') {
                    $return = EcommerceOrderReturn::query()->where('store_id', $refund->store_id)
                        ->lockForUpdate()->findOrFail($refund->order_id);
                    $amountCalculation = app(ReturnRefundAmountService::class)->calculate($return);
                    $approvedAmount = round((float) ($data['amount'] ?? $amountCalculation['amount']), 2);
                    $isAdjusted = abs($approvedAmount - (float) $amountCalculation['amount']) >= 0.01;
                    if ($approvedAmount > (float) $amountCalculation['available_paid_balance']) {
                        throw ValidationException::withMessages(['amount' => ['Refund amount cannot exceed the remaining amount paid on the order: PHP ' . number_format($amountCalculation['available_paid_balance'], 2) . '.']]);
                    }
                    if ($approvedAmount <= 0) {
                        throw ValidationException::withMessages(['amount' => ['There is no remaining paid amount available to refund for this order.']]);
                    }
                    if ($approvedAmount > 0 && $isAdjusted && trim((string) ($data['adjustment_reason'] ?? '')) === '') {
                        throw ValidationException::withMessages(['adjustment_reason' => ['Explain why the refund amount differs from the inspected-item calculation.']]);
                    }
                    $refund->amount = $approvedAmount;
                    if ($isAdjusted) {
                        $data['notes'] = trim(((string) ($data['notes'] ?? $refund->notes)) . "\n"
                            . 'Finance amount adjustment: PHP ' . number_format((float) $amountCalculation['amount'], 2)
                            . ' calculated; PHP ' . number_format($approvedAmount, 2) . ' approved. Reason: '
                            . trim((string) $data['adjustment_reason']));
                    }
                }
                if (!$this->hasPayoutRecipient($refund)) {
                    throw ValidationException::withMessages([
                        'refund_method' => ['Add a valid GCash or Maya recipient payout destination before approving this refund.'],
                    ]);
                }
                if ((float) $refund->amount <= 0) {
                    throw ValidationException::withMessages(['amount' => ['The approved payout amount must be greater than zero.']]);
                }
                $this->ensureRefundAmountAvailable($refund, true);
            }
            $refund->update(['status' => $data['status'], 'amount' => $refund->amount, 'notes' => $data['notes'] ?? $refund->notes,
                'processed_by' => $request->user()->id, 'processed_at' => now()]);
            if ($data['status'] === 'rejected' && $refund->order_type === 'ecommerce_return') {
                $linkedReturn = EcommerceOrderReturn::query()->where('store_id', $refund->store_id)->lockForUpdate()->find($refund->order_id);
                if ($linkedReturn && $linkedReturn->status === 'refund_pending') {
                    $linkedReturn->update(['status' => 'received']);
                    if ($linkedReturn->source_type !== 'pos') {
                        $linkedReturn->order()->update(['status' => 'return_received']);
                    }
                }
            }
        });

        $approvedRefund = $refund->fresh();
        $this->notifyEcommerceRefundStatus($approvedRefund, (string) $data['status']);
        return response()->json(['message' => $data['status'] === 'approved'
            ? 'Refund approved. Finance can now process the payout to the recipient.'
            : 'Refund rejected.', 'data' => $approvedRefund]);
    }

    public function createPayoutTestCheckout(Request $request, FinanceRefund $refund, PaymongoService $paymongo): JsonResponse
    {
        $this->authorizeStore($request, $refund);
        $data = $request->validate(['success_url' => ['required', 'url', 'max:500'], 'cancel_url' => ['required', 'url', 'max:500']]);
        if (!str_starts_with((string) config('paymongo.secret'), 'sk_test_')
            || !str_starts_with((string) config('paymongo.public'), 'pk_test_')) {
            throw ValidationException::withMessages(['provider' => ['Refund authorization requires PayMongo test keys.']]);
        }
        $expectedHost = strtolower((string) (parse_url((string) config('app.url'), PHP_URL_HOST) ?: $request->getHost()));
        foreach (['success_url', 'cancel_url'] as $returnField) {
            if (strtolower((string) parse_url($data[$returnField], PHP_URL_HOST)) !== $expectedHost) {
                throw ValidationException::withMessages([$returnField => ['The PayMongo return page must use this application domain.']]);
            }
        }
        if (!in_array($refund->status, ['approved', 'awaiting_payout'], true) || !$this->hasPayoutRecipient($refund)) {
            throw ValidationException::withMessages(['status' => ['Approve the refund and add valid recipient details before payout.']]);
        }
        $this->normalizeCancelledOrderRefundAmount($refund);
        if ((float) $refund->amount <= 0) {
            throw ValidationException::withMessages(['amount' => ['The refund amount must be greater than zero.']]);
        }

        $latest = PaymongoIntent::query()->where('store_id', $refund->store_id)
            ->where('payable_type', 'finance_refund')->where('payable_id', $refund->id)->latest('id')->first();
        if ($latest && data_get($latest->metadata, 'checkout_source') === 'finance_refund_payout') {
            $session = $paymongo->retrieveCheckoutSession((string) $latest->payment_intent_id);
            if (data_get($session, 'data.id')) {
                if ($this->checkoutHasPaid($session)) {
                    $latest->update(['status' => 'succeeded', 'webhook_payload' => $session]);
                    $completed = $this->completeTestPayout($request, $refund, $latest);
                    return response()->json(['message' => 'Refund payout authorized.', 'data' => $completed]);
                }
                $sessionStatus = strtolower((string) data_get($session, 'data.attributes.status', ''));
                if (!in_array($sessionStatus, ['expired', 'failed', 'canceled', 'cancelled'], true)) {
                    $activeCheckoutUrl = (string) (data_get($session, 'data.attributes.checkout_url') ?: data_get($session, 'data.attributes.url') ?: '');
                    if ($activeCheckoutUrl === '') {
                        throw ValidationException::withMessages(['provider' => ['Unable to retrieve the PayMongo authorization page.']]);
                    }
                    return response()->json(['data' => [
                        'checkout_url' => $activeCheckoutUrl,
                        'session_id' => $latest->payment_intent_id,
                    ]]);
                }
                $latest->update(['status' => $sessionStatus]);
            }
        }

        $metadata = [
            'store_id' => (string) $refund->store_id,
            'payable_type' => 'finance_refund',
            'payable_id' => (string) $refund->id,
            'checkout_source' => 'finance_refund_payout',
        ];
        $session = $paymongo->createCheckoutSession(['data' => ['attributes' => [
            'cancel_url' => $data['cancel_url'],
            'success_url' => $data['success_url'],
            'payment_method_types' => ['qrph'],
            'line_items' => [[
                'currency' => 'PHP',
                'amount' => (int) round((float) $refund->amount * 100),
                'name' => 'Refund payout ' . ($refund->order_number ?: ('#' . $refund->id)),
                'quantity' => 1,
            ]],
            'metadata' => $metadata,
        ]]]);
        $sessionId = (string) data_get($session, 'data.id', '');
        $sessionCheckoutUrl = (string) (data_get($session, 'data.attributes.checkout_url') ?: data_get($session, 'data.attributes.url') ?: '');
        if ($sessionId === '' || $sessionCheckoutUrl === '') {
            throw ValidationException::withMessages(['provider' => [(string) data_get($session, 'errors.0.detail', 'Unable to open PayMongo test authorization.')]]);
        }

        app(PaymongoService::class)->logIntent([
            'store_id' => (int) $refund->store_id,
            'payment_intent_id' => $sessionId,
            'amount' => (int) round((float) $refund->amount * 100),
            'currency' => 'PHP',
            'status' => 'pending',
            'description' => 'Refund payout ' . ($refund->order_number ?: ('#' . $refund->id)),
            'payment_method_allowed' => 'qrph',
            'metadata' => $metadata,
            'payable_type' => 'finance_refund',
            'payable_id' => (int) $refund->id,
        ]);

        return response()->json(['data' => [
            'checkout_url' => $sessionCheckoutUrl,
            'session_id' => $sessionId,
        ]]);
    }

    public function confirmPayoutTestCheckout(Request $request, FinanceRefund $refund, PaymongoService $paymongo): JsonResponse
    {
        $this->authorizeStore($request, $refund);
        if (!str_starts_with((string) config('paymongo.secret'), 'sk_test_')) {
            throw ValidationException::withMessages(['provider' => ['Refund test authorization is unavailable outside PayMongo test mode.']]);
        }
        $intent = PaymongoIntent::query()->where('store_id', $refund->store_id)
            ->where('payable_type', 'finance_refund')->where('payable_id', $refund->id)->latest('id')->first();
        if (!$intent || data_get($intent->metadata, 'checkout_source') !== 'finance_refund_payout') {
            throw ValidationException::withMessages(['status' => ['No PayMongo payout authorization is awaiting confirmation.']]);
        }
        $session = $paymongo->retrieveCheckoutSession((string) $intent->payment_intent_id);
        if (data_get($session, 'errors.0.detail')) {
            throw ValidationException::withMessages(['provider' => [(string) data_get($session, 'errors.0.detail')]]);
        }
        if (!$this->checkoutHasPaid($session)) {
            return response()->json(['message' => 'PayMongo has not authorized this payment yet.', 'data' => $refund], 202);
        }

        $intent->update(['status' => 'succeeded', 'webhook_payload' => $session]);
        $completed = $this->completeTestPayout($request, $refund, $intent);
        return response()->json(['message' => 'Refund payout authorized.', 'data' => $completed]);
    }

    private function checkoutHasPaid(array $session): bool
    {
        return collect((array) data_get($session, 'data.attributes.payments', []))
            ->contains(fn ($payment) => strtolower((string) data_get($payment, 'attributes.status', '')) === 'paid');
    }

    private function completeTestPayout(Request $request, FinanceRefund $refund, PaymongoIntent $intent): FinanceRefund
    {
        $wasSent = $refund->status === 'sent' && $refund->payout_reference === $intent->payment_intent_id;
        $completed = DB::transaction(function () use ($request, $refund, $intent): FinanceRefund {
            $locked = FinanceRefund::query()->where('store_id', $request->user()->store_id)
                ->lockForUpdate()->findOrFail($refund->id);
            if ($locked->status === 'sent' && $locked->payout_reference === $intent->payment_intent_id) return $locked;
            if (!in_array($locked->status, ['approved', 'awaiting_payout'], true)
                || (int) $intent->amount !== (int) round((float) $locked->amount * 100)) {
                throw ValidationException::withMessages(['status' => ['Refund details changed before the payout was authorized.']]);
            }
            $locked->update([
                'status' => 'sent',
                'payout_provider' => 'paymongo_test',
                'payout_reference' => (string) $intent->payment_intent_id,
                'sent_by' => $request->user()->id,
                'sent_at' => now(),
                'notes' => trim("Refund processed to the selected recipient account.\n" . (string) ($locked->notes ?? '')),
            ]);
            if ($locked->order_type === 'ecommerce_return') {
                $return = EcommerceOrderReturn::query()->where('store_id', $locked->store_id)
                    ->lockForUpdate()->find($locked->order_id);
                if ($return && in_array($return->status, ['received', 'refund_pending'], true)) {
                    $return->update(['status' => 'refunded', 'resolved_at' => now()]);
                }
            }
            return $locked->fresh();
        });
        if (!$wasSent) {
            $this->notifyEcommerceRefundStatus($completed, 'sent');
        }
        return $completed;
    }

    private function salesOrderForRefund(FinanceRefund $refund): ?SalesOrder
    {
        if ($refund->order_type === 'sales') {
            return SalesOrder::query()->where('store_id', $refund->store_id)->find($refund->order_id);
        }
        if ($refund->order_type === 'ecommerce_return') {
            $return = EcommerceOrderReturn::query()->where('store_id', $refund->store_id)->find($refund->order_id);
            if ($return?->source_type === 'pos' && $return->sales_order_id) {
                return SalesOrder::query()->where('store_id', $refund->store_id)->find($return->sales_order_id);
            }
        }
        return null;
    }

    private function hasPayoutRecipient(FinanceRefund $refund): bool
    {
        return in_array($refund->refund_method, ['gcash', 'maya'], true)
            && trim((string) $refund->refund_account_name) !== ''
            && preg_match('/^09[0-9]{9}$/', (string) $refund->refund_account_number) === 1;
    }

    private function ensureRefundAmountAvailable(FinanceRefund $refund, bool $lockOrder = false): void
    {
        if ($refund->order_type === 'ecommerce_return') {
            $return = EcommerceOrderReturn::query()->where('store_id', $refund->store_id)->find($refund->order_id);
            if (!$return) {
                throw ValidationException::withMessages(['order_id' => ['The linked return could not be found.']]);
            }
            $calculation = app(ReturnRefundAmountService::class)->calculate($return);
            if ((float) $refund->amount > (float) $calculation['available_paid_balance']) {
                throw ValidationException::withMessages(['amount' => ['Refund amount exceeds the remaining paid order balance of PHP ' . number_format($calculation['available_paid_balance'], 2) . '.']]);
            }
            return;
        }

        if ($refund->order_type === 'sales') {
            $sale = SalesOrder::query()->where('store_id', $refund->store_id)
                ->when($lockOrder, fn ($query) => $query->lockForUpdate())
                ->find($refund->order_id);
            if (!$sale || $sale->payment_status !== 'paid') {
                throw ValidationException::withMessages(['order_id' => ['The refund is not linked to a paid POS order.']]);
            }
            $reserved = FinanceRefund::query()->where('store_id', $refund->store_id)->where('id', '!=', $refund->id)
                ->where('order_type', 'sales')->where('order_id', $sale->id)
                ->whereNotIn('status', ['rejected', 'simulated', 'transfer_failed'])->sum('amount');
            $available = max(0, (float) $sale->total_amount - (float) $reserved);
            if ((float) $refund->amount > $available) {
                throw ValidationException::withMessages(['amount' => ['Refund amount exceeds the remaining paid POS balance of PHP ' . number_format($available, 2) . '.']]);
            }
            return;
        }

        $order = $this->ecommerceOrderForRefund($refund);
        if (!$order) {
            throw ValidationException::withMessages(['order_id' => ['The refund is not linked to a paid ecommerce order.']]);
        }
        if ($lockOrder) {
            $order = EcommerceOrder::query()->where('store_id', $refund->store_id)
                ->lockForUpdate()->findOrFail($order->id);
        }
        $returnIds = $order->returnRequests()->pluck('id');
        $previouslyReserved = FinanceRefund::query()->where('store_id', $refund->store_id)
            ->where('id', '!=', $refund->id)->whereNotIn('status', ['rejected', 'simulated', 'transfer_failed'])
            ->where(function ($query) use ($order, $returnIds): void {
                $query->where(fn ($q) => $q->where('order_type', 'ecommerce_order')->where('order_id', $order->id));
                if ($returnIds->isNotEmpty()) $query->orWhere(fn ($q) => $q->where('order_type', 'ecommerce_return')->whereIn('order_id', $returnIds));
            })->sum('amount');
        $available = max(0, $this->refundableOrderTotal($order) - (float) $previouslyReserved);
        if ((float) $refund->amount > $available) {
            throw ValidationException::withMessages(['amount' => ['Refund amount exceeds the remaining refundable order balance of PHP ' . number_format($available, 2) . '.']]);
        }
    }

    private function refundableOrderTotal(EcommerceOrder $order): float
    {
        // Shipping is not refundable when a whole Ecommerce order is cancelled.
        return max(0, round((float) $order->total_amount - (float) $order->shipping_fee, 2));
    }

    private function normalizeCancelledOrderRefundAmount(FinanceRefund $refund): void
    {
        if ($refund->order_type !== 'ecommerce_order') {
            return;
        }

        $order = $this->ecommerceOrderForRefund($refund);
        if (!$order) {
            return;
        }

        $refundableAmount = $this->refundableOrderTotal($order);
        if ((float) $refund->amount <= $refundableAmount) {
            return;
        }

        $refund->amount = $refundableAmount;
        $refund->notes = trim((string) $refund->notes . "\nShipping fee excluded from the approved refund amount.");
        $refund->save();
    }

    private function notifyEcommerceRefundStatus(FinanceRefund $refund, string $status): void
    {
        $customerId = null;
        $entityType = null;
        $entityId = null;
        $orderId = null;

        if ($refund->order_type === 'ecommerce_return') {
            $return = EcommerceOrderReturn::query()
                ->where('store_id', (int) $refund->store_id)
                ->find((int) $refund->order_id);
            if (!$return || $return->source_type === 'pos') {
                return;
            }

            $customerId = (int) ($return->user_id ?: 0);
            $entityType = 'ecommerce_order_return';
            $entityId = (int) $return->id;
            $orderId = (int) $return->order_id;
        } elseif ($refund->order_type === 'ecommerce_order') {
            $order = EcommerceOrder::query()
                ->where('store_id', (int) $refund->store_id)
                ->find((int) $refund->order_id);
            if (!$order) {
                return;
            }

            $customerId = (int) ($order->user_id ?: 0);
            $entityType = 'ecommerce_order';
            $entityId = (int) $order->id;
            $orderId = (int) $order->id;
        } else {
            return;
        }

        if ($customerId <= 0 || !$orderId) {
            return;
        }

        $notification = match ($status) {
            'approved' => ['Refund approved', 'Your refund request has been approved and is being prepared.', 'success', 'refund_approved'],
            'rejected' => ['Refund not approved', 'Your refund request could not be approved. Review the return details or contact the store for help.', 'warn', 'refund_rejected'],
            'processing' => ['Refund processing', 'Your refund is being processed. We will update you when it is complete.', 'info', 'refund_processing'],
            'transfer_failed' => ['Refund needs attention', 'The refund could not be completed yet. The store has been notified and will review it.', 'warn', 'refund_attention'],
            'sent' => [
                'Refund sent',
                'Your refund has been processed to the selected recipient account.',
                'success',
                'refund_sent',
            ],
            'simulated' => ['Refund processed', 'Your refund has been processed to the selected account.', 'success', 'refund_sent'],
            default => null,
        };

        if (!$notification) {
            return;
        }

        [$title, $message, $severity, $action] = $notification;
        $this->notify($customerId, [
            'module' => 'ecommerce',
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'title' => $title,
            'message' => $message,
            'severity' => $severity,
            'store_id' => (int) $refund->store_id,
            'link' => '/orders/' . $orderId,
            'data' => [
                'status' => $status === 'simulated' ? 'sent' : $status,
                'payout_reference' => $refund->payout_reference,
            ],
        ]);
    }

    private function authorizeStore(Request $request, FinanceRefund $refund): void
    {
        abort_unless((int) $refund->store_id === (int) $request->user()->store_id, 403, 'Unauthorized access to refund.');
    }

    private function ecommerceOrderForRefund(FinanceRefund $refund): ?EcommerceOrder
    {
        if ($refund->order_type === 'ecommerce_order') {
            return EcommerceOrder::query()->where('store_id', $refund->store_id)->find($refund->order_id);
        }

        if ($refund->order_type === 'ecommerce_return') {
            return EcommerceOrderReturn::query()->where('store_id', $refund->store_id)
                ->with('order')->find($refund->order_id)?->order;
        }

        return null;
    }

}
