<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\CRM\EcommerceOrderReturn;
use App\Models\Ecommerce\EcommerceOrder;
use App\Models\Finance\FinanceCashflowTransaction;
use App\Models\Finance\FinanceRefund;
use App\Models\PaymongoIntent;
use App\Models\Sales\SalesOrder;
use App\Services\Finance\CashflowService;
use App\Services\Finance\ReturnRefundAmountService;
use App\Services\Payment\PaymongoService;
use App\Services\Payment\EcommerceRefundRoutingService;
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
            if ($status === 'awaiting_payout') {
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
        $refundRouting = app(EcommerceRefundRoutingService::class);
        $sandboxRefundSimulationAvailable = $this->canSimulatePaymongoRefund($refund);
        $amountCalculation = null;
        if ($refund->order_type === 'ecommerce_return') {
            $return = EcommerceOrderReturn::query()->where('store_id', $refund->store_id)->find($refund->order_id);
            if ($return) $amountCalculation = app(ReturnRefundAmountService::class)->calculate($return);
        }
        return response()->json([
            'data' => $refund,
            'original_payment_method' => $originalOrder ? $refundRouting->originalMethod($originalOrder) : null,
            'automatic_refund_available' => $originalOrder ? $refundRouting->canRefundToOriginal($originalOrder) : false,
            'paymongo_refunds_available' => str_starts_with((string) config('paymongo.secret'), 'sk_live_'),
            'card_refund_simulation_available' => $sandboxRefundSimulationAvailable,
            'refund_simulation_available' => $sandboxRefundSimulationAvailable,
            'amount_calculation' => $amountCalculation,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_type' => 'required|in:sales,manual', 'order_id' => 'required|integer|min:1',
            'order_number' => 'nullable|string|max:100', 'customer_name' => 'required|string|max:190',
            'reason' => 'required|string|max:2000', 'amount' => 'required|numeric|min:0.01', 'notes' => 'nullable|string|max:2000',
            'refund_method' => 'required|in:gcash,maya,bank_transfer,card',
            'refund_account_name' => 'required|string|max:255',
            'refund_account_number' => 'required|string|max:100',
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
                $originalOrder = $this->ecommerceOrderForRefund($refund);
                $canRefundOriginal = $originalOrder
                    && app(EcommerceRefundRoutingService::class)->canRefundToOriginal($originalOrder);
                if ($originalOrder) $this->ensureRefundAmountAvailable($refund, true);
                if (in_array($refund->order_type, ['ecommerce_return', 'ecommerce_order'], true)
                    && !$canRefundOriginal
                    && !$this->canSimulatePaymongoRefund($refund)
                    && (!$refund->refund_method || !$refund->refund_account_name || !$refund->refund_account_number)) {
                    throw ValidationException::withMessages([
                        'refund_method' => ['The customer must provide GCash or Maya payout details before Finance can approve this refund.'],
                    ]);
                }
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
            ? ($this->canSimulatePaymongoRefund($approvedRefund)
                ? 'Refund approved. You can record a sandbox simulation; it will not issue a payout.'
                : 'Refund approved. Send the payout to the customer, then record its reference.')
            : 'Refund rejected.', 'data' => $approvedRefund]);
    }

    public function markSent(Request $request, FinanceRefund $refund, CashflowService $cashflow, PaymongoService $paymongo): JsonResponse
    {
        $this->authorizeStore($request, $refund);
        $data = $request->validate([
            'payout_reference' => ['nullable', 'string', 'max:190'],
            'provider' => ['nullable', 'in:paymongo,manual,simulation'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (($data['provider'] ?? 'manual') === 'paymongo') {
            return $this->sendPaymongoRefund($request, $refund, $cashflow, $paymongo);
        }

        $linkedReturn = null;
        $cancelledOrder = null;
        $sandboxSimulation = false;
        DB::transaction(function () use ($request, $refund, $cashflow, $data, &$linkedReturn, &$cancelledOrder, &$sandboxSimulation): void {
            $refund = FinanceRefund::query()->lockForUpdate()->findOrFail($refund->id);
            $payoutProvider = (string) ($data['provider'] ?? 'manual');
            $legacyUnpaidRefund = $refund->status === 'sent'
                && in_array($refund->payout_provider, ['simulation', 'paymongo_wallet_test'], true);
            if (!in_array($refund->status, ['approved', 'awaiting_payout'], true) && !$legacyUnpaidRefund) {
                throw ValidationException::withMessages(['status' => ['Only an approved refund awaiting payout can be marked as sent.']]);
            }
            if ($legacyUnpaidRefund) {
                $refund->status = 'awaiting_payout';
            }
            $this->validatePayoutProvider($refund, $payoutProvider);
            if (in_array($refund->order_type, ['ecommerce_return', 'ecommerce_order'], true)
                && in_array($payoutProvider, ['manual', 'simulation'], true)) {
                $this->ensureRefundAmountAvailable($refund, true);
            }
            if ($payoutProvider === 'manual'
                && (!$refund->refund_method || !$refund->refund_account_name || !$refund->refund_account_number)) {
                throw ValidationException::withMessages([
                    'refund_method' => ['The customer refund account details are incomplete.'],
                ]);
            }

            $payoutReference = trim((string) ($data['payout_reference'] ?? ''));
            if ($payoutProvider === 'simulation') {
                if (!str_starts_with((string) config('paymongo.secret'), 'sk_test_')
                    || !in_array($refund->order_type, ['ecommerce_return', 'ecommerce_order'], true)
                    || !$this->hasSuccessfulPaymongoOrderPayment($refund)) {
                    throw ValidationException::withMessages(['provider' => ['Sandbox simulation requires an ecommerce order paid successfully through PayMongo test mode.']]);
                }
                $this->ensureRefundAmountAvailable($refund, true);
                $refund->update([
                    'status' => 'simulated',
                    'payout_reference' => 'TEST-REFUND-' . $refund->id,
                    'payout_provider' => 'simulation',
                    'sent_by' => $request->user()->id,
                    'sent_at' => now(),
                    'notes' => trim("PayMongo sandbox simulation only. No funds moved and no refund was issued.\n" . ($data['notes'] ?? $refund->notes ?? '')),
                ]);
                $sandboxSimulation = true;
                return;
            } elseif ($payoutProvider === 'manual' && $payoutReference === '') {
                throw ValidationException::withMessages(['payout_reference' => ['Enter the manual payout or transaction reference.']]);
            }

            try {
                $cashflow->debit(
                    (int) $refund->store_id,
                    (float) $refund->amount,
                    'finance_refund',
                    (int) $refund->id,
                    $request->user()->id,
                    'Refund sent to ' . $refund->refund_account_name,
                    $refund->refund_method,
                    ['payout_reference' => $payoutReference]
                );
            } catch (\RuntimeException $e) {
                throw ValidationException::withMessages(['amount' => [$e->getMessage()]]);
            }

            $refund->update([
                'status' => 'sent',
                'payout_reference' => $payoutReference,
                'payout_provider' => $payoutProvider,
                'sent_by' => $request->user()->id,
                'sent_at' => now(),
                'notes' => $data['notes'] ?? $refund->notes,
            ]);

            if ($refund->order_type === 'ecommerce_return') {
                $linkedReturn = EcommerceOrderReturn::query()
                    ->where('store_id', $refund->store_id)
                    ->lockForUpdate()
                    ->find($refund->order_id);
                if ($linkedReturn && in_array($linkedReturn->status, ['received', 'refund_pending'], true)) {
                    $linkedReturn->update(['status' => 'refunded', 'resolved_at' => now()]);
                }
            } elseif ($refund->order_type === 'ecommerce_order') {
                $cancelledOrder = EcommerceOrder::query()->where('store_id', $refund->store_id)
                    ->where('status', 'cancelled')->lockForUpdate()->findOrFail($refund->order_id);
            } elseif ($refund->order_type === 'sales') {
                $sale = SalesOrder::query()->where('store_id', $refund->store_id)
                    ->lockForUpdate()->findOrFail($refund->order_id);
                if ((float) $refund->amount >= (float) $sale->total_amount) {
                    $sale->update(['status' => 'refunded']);
                }
            }

            if (in_array($refund->order_type, ['ecommerce_return', 'ecommerce_order'], true)) {
                $order = $this->ecommerceOrderForRefund($refund);
                if ($order && $this->totalSentForEcommerceOrder($order) >= (float) $order->total_amount) {
                    $order->update(['payment_status' => 'refunded']);
                    if ($refund->order_type === 'ecommerce_return') $order->update(['status' => 'refunded']);
                }
            }
        });

        if ($sandboxSimulation) {
            $this->notifyEcommerceRefundStatus($refund->fresh(), 'simulated');
            return response()->json([
                'message' => 'Sandbox refund simulation recorded. No funds moved and the order remains paid.',
                'data' => $refund->fresh(),
            ]);
        }

        $this->notifyEcommerceRefundStatus($refund->fresh(), 'sent');

        return response()->json(['message' => 'Refund marked as sent to the customer.', 'data' => $refund->fresh()]);
    }

    private function sendPaymongoRefund(Request $request, FinanceRefund $refund, CashflowService $cashflow, PaymongoService $paymongo): JsonResponse
    {
        $this->authorizeStore($request, $refund);
        if (!str_starts_with((string) config('paymongo.secret'), 'sk_live_')) {
            throw ValidationException::withMessages(['provider' => ['PayMongo refunds are only issued in live mode. Use the explicit sandbox simulation with test keys.']]);
        }

        $originalOrder = $this->ecommerceOrderForRefund($refund);
        if (!$originalOrder || !app(EcommerceRefundRoutingService::class)->canRefundToOriginal($originalOrder)) {
            throw ValidationException::withMessages(['provider' => ['This order is not eligible for a refund to its original PayMongo payment.']]);
        }
        $this->ensureRefundAmountAvailable($refund);

        $previousRefundId = null;
        if ($refund->status === 'transfer_failed' && $refund->payout_provider === 'paymongo' && $refund->paymongo_refund_id) {
            $previous = $paymongo->retrieveRefund((string) $refund->paymongo_refund_id);
            if (data_get($previous, 'errors')) {
                throw ValidationException::withMessages(['provider' => ['Unable to verify the previous PayMongo refund. Check the dashboard before retrying.']]);
            }
            $previousStatus = strtolower((string) data_get($previous, 'data.attributes.status', ''));
            if ($previousStatus === 'succeeded') {
                return $this->completePaymongoRefund($request, $refund, $cashflow, (string) $refund->paymongo_refund_id);
            }
            if (in_array($previousStatus, ['pending', 'processing'], true)) {
                $previousState = (string) $refund->status;
                $refund->update(['status' => 'processing']);
                if ($previousState !== 'processing') {
                    $this->notifyEcommerceRefundStatus($refund->fresh(), 'processing');
                }
                return response()->json(['message' => 'The previous PayMongo refund is still processing. Check its status instead of submitting another refund.', 'data' => $refund->fresh()]);
            }
            if ($previousStatus !== 'failed') {
                throw ValidationException::withMessages(['provider' => ['The previous PayMongo refund status is unknown. Check the dashboard before retrying.']]);
            }
            $previousRefundId = (string) $refund->paymongo_refund_id;
        }

        $intent = PaymongoIntent::query()->where('payable_type', 'ecommerce_order')
            ->where('payable_id', $originalOrder->id)->where('status', 'succeeded')->latest('id')->first();
        $intentId = (string) ($intent?->payment_intent_id ?? '');
        $intentPayload = $intentId === '' ? [] : (str_starts_with($intentId, 'cs_')
            ? $paymongo->retrieveCheckoutSession($intentId)
            : $paymongo->retrieveIntent($intentId));
        $payment = collect((array) data_get($intentPayload, 'data.attributes.payments', []))->first(fn ($entry) => in_array(
            strtolower((string) data_get($entry, 'attributes.status', '')), ['paid', 'succeeded'], true
        ));
        $paymentId = (string) data_get($payment, 'id', '');
        if ($paymentId === '') {
            throw ValidationException::withMessages(['provider' => ['No successful PayMongo payment was found for this order.']]);
        }

        DB::transaction(function () use ($request, $refund, $previousRefundId): void {
            $locked = FinanceRefund::query()->where('store_id', $request->user()->store_id)
                ->lockForUpdate()->findOrFail($refund->id);
            $retryingKnownFailure = $locked->status === 'transfer_failed'
                && $locked->payout_provider === 'paymongo'
                && $locked->paymongo_refund_id === $previousRefundId;
            if ((!in_array($locked->status, ['approved', 'awaiting_payout'], true) && !$retryingKnownFailure)
                || ($locked->paymongo_refund_id && !$retryingKnownFailure)) {
                throw ValidationException::withMessages(['status' => ['This refund is no longer ready to submit to PayMongo. Refresh its status before retrying.']]);
            }
            $this->ensureRefundAmountAvailable($locked, true);
            $locked->update([
                'status' => 'processing', 'payout_provider' => 'paymongo', 'transfer_error' => null,
                'paymongo_refund_id' => null, 'payout_reference' => null,
                'notes' => $previousRefundId ? trim("Previous PayMongo refund failed: {$previousRefundId}.\n" . ($locked->notes ?? '')) : $locked->notes,
            ]);
        });
        $this->notifyEcommerceRefundStatus($refund->fresh(), 'processing');

        try {
            $response = $paymongo->createRefund($paymentId, (int) round((float) $refund->amount * 100));
        } catch (\Throwable $e) {
            // A timeout can happen after PayMongo accepts a refund. Keep the record
            // locked in processing so Finance checks the provider before retrying.
            FinanceRefund::query()->whereKey($refund->id)->update([
                'transfer_error' => 'PayMongo outcome is unknown. Check the PayMongo dashboard before retrying.',
            ]);
            throw ValidationException::withMessages(['provider' => ['PayMongo outcome is unknown. Check the PayMongo dashboard before retrying; do not submit a second refund yet.']]);
        }
        $refundId = (string) data_get($response, 'data.id', '');
        if ($refundId === '') {
            $httpStatus = (int) ($response['_http_status'] ?? 500);
            $message = (string) data_get($response, 'errors.0.detail', 'PayMongo did not return a refund reference.');
            if ($httpStatus >= 500) {
                FinanceRefund::query()->whereKey($refund->id)->update([
                    'transfer_error' => 'PayMongo outcome is unknown. Check the PayMongo dashboard before retrying. ' . $message,
                ]);
                throw ValidationException::withMessages(['provider' => ['PayMongo outcome is unknown. Check the PayMongo dashboard before retrying; do not submit a second refund yet.']]);
            }
            FinanceRefund::query()->whereKey($refund->id)->update(['status' => 'transfer_failed', 'transfer_error' => $message]);
            $this->notifyEcommerceRefundStatus($refund->fresh(), 'transfer_failed');
            throw ValidationException::withMessages(['provider' => [$message]]);
        }

        $providerStatus = strtolower((string) data_get($response, 'data.attributes.status', 'pending'));
        FinanceRefund::query()->whereKey($refund->id)->update([
            'paymongo_refund_id' => $refundId,
            'payout_reference' => $refundId,
            'transfer_error' => $providerStatus === 'failed' ? (string) data_get($response, 'data.attributes.failure_code', 'PayMongo refund failed.') : null,
        ]);

        if ($providerStatus === 'succeeded') {
            return $this->completePaymongoRefund($request, $refund, $cashflow, $refundId);
        }
        if (in_array($providerStatus, ['failed', 'canceled', 'cancelled'], true)) {
            FinanceRefund::query()->whereKey($refund->id)->update(['status' => 'transfer_failed']);
            $this->notifyEcommerceRefundStatus($refund->fresh(), 'transfer_failed');
            throw ValidationException::withMessages(['provider' => ['PayMongo reported that the refund failed. Review the provider response before taking further action.']]);
        }
        if (!in_array($providerStatus, ['pending', 'processing', 'succeeded'], true)) {
            FinanceRefund::query()->whereKey($refund->id)->update([
                'transfer_error' => 'PayMongo returned an unknown status. Verify the provider dashboard before proceeding.',
            ]);
            throw ValidationException::withMessages(['provider' => ['PayMongo returned an unknown refund status. Verify the provider dashboard before proceeding.']]);
        }

        return response()->json([
            'message' => 'PayMongo accepted the refund request. The refund remains processing until PayMongo confirms success.',
            'data' => $refund->fresh(),
        ]);
    }

    public function refreshPaymongoRefund(Request $request, FinanceRefund $refund, CashflowService $cashflow, PaymongoService $paymongo): JsonResponse
    {
        $this->authorizeStore($request, $refund);
        if ($refund->status !== 'processing' || $refund->payout_provider !== 'paymongo' || !$refund->paymongo_refund_id) {
            throw ValidationException::withMessages(['status' => ['This refund has no PayMongo refund awaiting a status check.']]);
        }

        $response = $paymongo->retrieveRefund((string) $refund->paymongo_refund_id);
        if (data_get($response, 'errors')) {
            throw ValidationException::withMessages(['provider' => [(string) data_get($response, 'errors.0.detail', 'Unable to check the PayMongo refund.')]]);
        }
        $providerStatus = strtolower((string) data_get($response, 'data.attributes.status', ''));
        if ($providerStatus === 'succeeded') {
            return $this->completePaymongoRefund($request, $refund, $cashflow, (string) $refund->paymongo_refund_id);
        }
        if (in_array($providerStatus, ['failed', 'canceled', 'cancelled'], true)) {
            $refund->update([
                'status' => 'transfer_failed',
                'transfer_error' => (string) data_get($response, 'data.attributes.failure_code', 'PayMongo reported that the refund failed.'),
            ]);
            $this->notifyEcommerceRefundStatus($refund->fresh(), 'transfer_failed');
            return response()->json(['message' => 'PayMongo reported that the refund failed.', 'data' => $refund->fresh()]);
        }
        if (!in_array($providerStatus, ['pending', 'processing'], true)) {
            throw ValidationException::withMessages(['provider' => ['PayMongo returned an unknown refund status. Check the PayMongo dashboard before taking action.']]);
        }

        return response()->json(['message' => 'The PayMongo refund is still processing.', 'data' => $refund->fresh()]);
    }

    private function completePaymongoRefund(Request $request, FinanceRefund $refund, CashflowService $cashflow, string $refundId): JsonResponse
    {
        $result = DB::transaction(function () use ($request, $refund, $cashflow, $refundId): array {
            $locked = FinanceRefund::query()->where('store_id', $request->user()->store_id)
                ->lockForUpdate()->findOrFail($refund->id);
            if ($locked->status === 'sent' && $locked->paymongo_refund_id === $refundId) {
                return ['completed' => false, 'refund' => $locked];
            }
            if ($locked->status !== 'processing' || $locked->payout_provider !== 'paymongo'
                || $locked->paymongo_refund_id !== $refundId) {
                throw ValidationException::withMessages(['status' => ['Refund state changed while confirming the PayMongo result.']]);
            }

            // The provider has confirmed the outgoing refund, so record it even if concurrent spending made the local balance stale.
            $alreadyRecorded = FinanceCashflowTransaction::query()->where('store_id', $locked->store_id)
                ->where('reference_type', 'finance_refund')->where('reference_id', $locked->id)
                ->where('direction', 'out')->where('payment_method', 'paymongo')
                ->whereJsonContains('meta->payout_reference', $refundId)->exists();
            if (!$alreadyRecorded) {
                $cashflow->debit(
                    (int) $locked->store_id, (float) $locked->amount, 'finance_refund', (int) $locked->id,
                    $request->user()->id, 'Refund to original PayMongo payment', 'paymongo',
                    ['payout_reference' => $refundId], true
                );
            }
            $locked->update([
                'status' => 'sent', 'payout_reference' => $refundId, 'sent_by' => $request->user()->id,
                'sent_at' => now(), 'transfer_error' => null,
            ]);

            $linkedReturn = null;
            $order = $this->ecommerceOrderForRefund($locked);
            if ($locked->order_type === 'ecommerce_return') {
                $linkedReturn = EcommerceOrderReturn::query()->where('store_id', $locked->store_id)
                    ->lockForUpdate()->find($locked->order_id);
                if ($linkedReturn && in_array($linkedReturn->status, ['received', 'refund_pending'], true)) {
                    $linkedReturn->update(['status' => 'refunded', 'resolved_at' => now()]);
                }
            }
            if ($order) {
                if ($this->totalSentForEcommerceOrder($order) >= (float) $order->total_amount) {
                    $order->update(['payment_status' => 'refunded']);
                    if ($locked->order_type === 'ecommerce_return') $order->update(['status' => 'refunded']);
                }
            }

            return ['completed' => true, 'refund' => $locked->fresh(), 'return' => $linkedReturn, 'order' => $order];
        });

        if (!$result['completed']) {
            return response()->json(['message' => 'PayMongo refund was already recorded as complete.', 'data' => $result['refund']]);
        }
        $this->notifyEcommerceRefundStatus($result['refund'], 'sent');

        return response()->json(['message' => 'PayMongo confirmed the refund. The finance payout has been recorded.', 'data' => $result['refund']]);
    }

    private function hasSuccessfulPaymongoOrderPayment(FinanceRefund $refund): bool
    {
        $order = $this->ecommerceOrderForRefund($refund);
        return $order !== null && $order->payment_status === 'paid'
            && in_array($order->payment_method, ['card', 'e_wallet'], true)
            && PaymongoIntent::query()->where('payable_type', 'ecommerce_order')
                ->where('payable_id', $order->id)->where('status', 'succeeded')->exists();
    }

    private function canSimulatePaymongoRefund(FinanceRefund $refund): bool
    {
        if (!str_starts_with((string) config('paymongo.secret'), 'sk_test_')
            || !in_array($refund->order_type, ['ecommerce_return', 'ecommerce_order'], true)
            || !$this->hasSuccessfulPaymongoOrderPayment($refund)) {
            return false;
        }
        $order = $this->ecommerceOrderForRefund($refund);
        return $order !== null && app(EcommerceRefundRoutingService::class)->originalMethod($order) !== null;
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
        $available = max(0, (float) $order->total_amount - (float) $previouslyReserved);
        if ((float) $refund->amount > $available) {
            throw ValidationException::withMessages(['amount' => ['Refund amount exceeds the remaining refundable order balance of PHP ' . number_format($available, 2) . '.']]);
        }
    }

    private function totalSentForEcommerceOrder(EcommerceOrder $order): float
    {
        $returnIds = $order->returnRequests()->pluck('id');
        return (float) FinanceRefund::query()->where('store_id', $order->store_id)
            ->where('status', 'sent')->where(function ($query) use ($order, $returnIds): void {
                $query->where(fn ($q) => $q->where('order_type', 'ecommerce_order')->where('order_id', $order->id));
                if ($returnIds->isNotEmpty()) $query->orWhere(fn ($q) => $q->where('order_type', 'ecommerce_return')->whereIn('order_id', $returnIds));
            })->sum('amount');
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
                $refund->payout_provider === 'paymongo'
                    ? 'Your refund has been processed to the original payment method.'
                    : 'Your refund has been sent to the selected refund account.',
                'success',
                'refund_sent',
            ],
            'simulated' => ['Refund completed', 'Your refund has been processed.', 'success', 'refund_sent'],
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
                'status' => $status === 'simulated' ? 'refunded' : $status,
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

    private function validatePayoutProvider(FinanceRefund $refund, string $provider): void
    {
        if (!in_array($refund->order_type, ['ecommerce_return', 'ecommerce_order'], true)) {
            return;
        }

        $originalOrder = $refund->order_id ? $this->ecommerceOrderForRefund($refund) : null;
        $refundRouting = app(EcommerceRefundRoutingService::class);
        if ($refund->refund_method === 'card' && !in_array($provider, ['paymongo', 'simulation'], true)) {
            throw ValidationException::withMessages(['provider' => ['Card refunds must return to the original payment method.']]);
        }
        if ($provider === 'manual' && $originalOrder && $refundRouting->canRefundToOriginal($originalOrder)) {
            throw ValidationException::withMessages(['provider' => ['This order must be refunded through its original payment method.']]);
        }
        if ($provider === 'paymongo' && (!$originalOrder || !$refundRouting->canRefundToOriginal($originalOrder))) {
            throw ValidationException::withMessages(['provider' => ['This refund is not eligible for return to its original payment method.']]);
        }
    }
}
