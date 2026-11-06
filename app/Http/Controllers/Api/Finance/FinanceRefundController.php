<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\CRM\EcommerceOrderReturn;
use App\Models\Ecommerce\EcommerceOrder;
use App\Models\Finance\FinanceRefund;
use App\Models\PaymongoIntent;
use App\Models\Sales\SalesOrder;
use App\Services\Finance\CashflowService;
use App\Services\Payment\PaymongoService;
use App\Services\Payment\EcommerceRefundRoutingService;
use App\Services\Payment\PaymongoTestWalletTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinanceRefundController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = FinanceRefund::query()->with(['branch:id,name', 'requester:id,fname,lname'])
            ->where('store_id', $request->user()->store_id);

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

        return response()->json(['data' => $query->latest()->get()]);
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
                'pickup:id,return_id,status,destination_branch_id,delivered_at',
                'pickup.destinationBranch:id,name,address',
            ]),
        ]);
        if ($refund->order_type !== 'ecommerce_return') {
            $refund->setRelation('ecommerceReturn', null);
        }
        $originalOrder = $this->ecommerceOrderForRefund($refund);
        $refundRouting = app(EcommerceRefundRoutingService::class);
        return response()->json([
            'data' => $refund,
            'original_payment_method' => $originalOrder ? $refundRouting->originalMethod($originalOrder) : null,
            'automatic_refund_available' => $originalOrder ? $refundRouting->canRefundToOriginal($originalOrder) : false,
            'paymongo_refunds_available' => str_starts_with((string) config('paymongo.secret'), 'sk_live_'),
            'card_refund_simulation_available' => str_starts_with((string) config('paymongo.secret'), 'sk_test_'),
            'refund_simulation_available' => str_starts_with((string) config('paymongo.secret'), 'sk_test_'),
            'test_wallet_transfer_available' => app(PaymongoTestWalletTransferService::class)->isAvailable(),
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
        $data = $request->validate(['status' => 'required|in:approved,rejected', 'notes' => 'nullable|string|max:2000']);
        $linkedReturn = null;

        DB::transaction(function () use ($request, $refund, $data, &$linkedReturn) {
            $refund = FinanceRefund::query()->lockForUpdate()->findOrFail($refund->id);
            if ($refund->status !== 'pending') throw ValidationException::withMessages(['status' => ['Only a pending refund can be processed.']]);
            if ($data['status'] === 'approved') {
                $originalOrder = $this->ecommerceOrderForRefund($refund);
                $canRefundOriginal = $originalOrder
                    && app(EcommerceRefundRoutingService::class)->canRefundToOriginal($originalOrder);
                if (in_array($refund->order_type, ['ecommerce_return', 'ecommerce_order'], true)
                    && !$canRefundOriginal
                    && (!$refund->refund_method || !$refund->refund_account_name || !$refund->refund_account_number)) {
                    throw ValidationException::withMessages([
                        'refund_method' => ['The customer must provide GCash or Maya payout details before Finance can approve this refund.'],
                    ]);
                }
            }
            $refund->update(['status' => $data['status'], 'notes' => $data['notes'] ?? $refund->notes,
                'processed_by' => $request->user()->id, 'processed_at' => now()]);
            if ($data['status'] === 'rejected' && $refund->order_type === 'ecommerce_return') {
                $linkedReturn = EcommerceOrderReturn::query()->where('store_id', $refund->store_id)->lockForUpdate()->find($refund->order_id);
                if ($linkedReturn && $linkedReturn->status === 'refund_pending') {
                    $linkedReturn->update(['status' => 'received']);
                    $linkedReturn->order()->update(['status' => 'return_received']);
                }
            }
        });

        $approvedRefund = $refund->fresh();
        return response()->json(['message' => $data['status'] === 'approved'
            ? (in_array($approvedRefund->order_type, ['ecommerce_return', 'ecommerce_order'], true)
                && in_array($approvedRefund->refund_method, ['card', 'gcash', 'maya'], true)
                && str_starts_with((string) config('paymongo.secret'), 'sk_test_')
                    ? 'Refund approved. Continue with payout verification.'
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

        $linkedReturn = null;
        $cancelledOrder = null;
        $simulationPending = false;
        DB::transaction(function () use ($request, $refund, $cashflow, $paymongo, $data, &$linkedReturn, &$cancelledOrder, &$simulationPending): void {
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
            if (($payoutProvider === 'manual' || ($payoutProvider === 'simulation' && $refund->refund_method !== 'card'))
                && (!$refund->refund_method || !$refund->refund_account_name || !$refund->refund_account_number)) {
                throw ValidationException::withMessages([
                    'refund_method' => ['The customer refund account details are incomplete.'],
                ]);
            }

            $payoutReference = trim((string) ($data['payout_reference'] ?? ''));
            $paymongoRefundId = null;

            if ($payoutProvider === 'simulation') {
                if (!str_starts_with((string) config('paymongo.secret'), 'sk_test_')
                    || !in_array($refund->refund_method, ['card', 'gcash', 'maya'], true)
                    || !in_array($refund->order_type, ['ecommerce_return', 'ecommerce_order'], true)) {
                    throw ValidationException::withMessages(['provider' => ['Payout verification is not available for this refund.']]);
                }
                if ($refund->order_type === 'ecommerce_return') {
                    $linkedReturn = EcommerceOrderReturn::query()->where('store_id', $refund->store_id)
                        ->lockForUpdate()->findOrFail($refund->order_id);
                    $orderId = $linkedReturn->order_id;
                } else {
                    $cancelledOrder = EcommerceOrder::query()->where('store_id', $refund->store_id)
                        ->where('status', 'cancelled')->lockForUpdate()->findOrFail($refund->order_id);
                    $orderId = $cancelledOrder->id;
                }
                $originalOrder = EcommerceOrder::query()->where('store_id', $refund->store_id)->findOrFail($orderId);
                if (($refund->refund_method === 'card' && $originalOrder->payment_method !== 'card')
                    || $originalOrder->payment_status !== 'paid'
                    || !PaymongoIntent::query()->where('payable_type', 'ecommerce_order')
                        ->where('payable_id', $orderId)->where('status', 'succeeded')->exists()) {
                    throw ValidationException::withMessages(['provider' => ['A successful payment is required before verifying this refund.']]);
                }
                $refund->update([
                    'status' => 'awaiting_payout',
                    'payout_reference' => null,
                    'payout_provider' => 'simulation',
                    'sent_by' => null,
                    'sent_at' => null,
                    'notes' => trim("Awaiting payout.\n" . ($data['notes'] ?? $refund->notes ?? '')),
                ]);
                $simulationPending = true;
                return;
            } elseif ($payoutProvider === 'paymongo') {
                if (!str_starts_with((string) config('paymongo.secret'), 'sk_live_')) {
                    throw ValidationException::withMessages(['provider' => ['PayMongo card refunds require a live payment and live key.']]);
                }
                if ($refund->order_type === 'ecommerce_return') {
                    $linkedReturn = EcommerceOrderReturn::query()->where('store_id', $refund->store_id)
                        ->lockForUpdate()->find($refund->order_id);
                    $orderId = $linkedReturn?->order_id;
                } elseif ($refund->order_type === 'ecommerce_order') {
                    $cancelledOrder = EcommerceOrder::query()->where('store_id', $refund->store_id)
                        ->where('status', 'cancelled')->lockForUpdate()->find($refund->order_id);
                    $orderId = $cancelledOrder?->id;
                } else {
                    throw ValidationException::withMessages(['provider' => ['PayMongo is only available for ecommerce order payments.']]);
                }
                $originalOrder = $this->ecommerceOrderForRefund($refund);
                if (!$originalOrder || !app(EcommerceRefundRoutingService::class)->canRefundToOriginal($originalOrder)) {
                    throw ValidationException::withMessages(['provider' => ['This order cannot be refunded through its original payment method. Use the customer payout details.']]);
                }
                $intent = $orderId
                    ? PaymongoIntent::query()->where('payable_type', 'ecommerce_order')
                        ->where('payable_id', $orderId)
                        ->where('status', 'succeeded')->latest('id')->first()
                    : null;
                $intentId = (string) ($intent?->payment_intent_id ?? '');
                $intentPayload = $intentId === '' ? [] : (str_starts_with($intentId, 'cs_')
                    ? $paymongo->retrieveCheckoutSession($intentId)
                    : $paymongo->retrieveIntent($intentId));
                $payments = (array) data_get($intentPayload, 'data.attributes.payments', []);
                $payment = collect($payments)->first(fn ($entry) => in_array(
                    strtolower((string) data_get($entry, 'attributes.status', '')),
                    ['paid', 'succeeded'], true
                ));
                $paymentId = data_get($payment, 'id');
                if (!$paymentId) {
                    throw ValidationException::withMessages([
                        'provider' => ['This order has no successful PayMongo payment to refund. Use Manual payout and enter the payout reference.'],
                    ]);
                }
                if ($cashflow->getAvailableBalance((int) $refund->store_id) < (float) $refund->amount) {
                    throw ValidationException::withMessages(['amount' => ['Insufficient operating balance to post this refund.']]);
                }
                $paymongoResponse = $paymongo->createRefund((string) $paymentId, (int) round((float) $refund->amount * 100));
                if (data_get($paymongoResponse, 'errors') || !data_get($paymongoResponse, 'data.id')) {
                    throw ValidationException::withMessages([
                        'provider' => [data_get($paymongoResponse, 'errors.0.detail', 'PayMongo rejected the refund.')],
                    ]);
                }
                $paymongoRefundId = data_get($paymongoResponse, 'data.id');
                $payoutReference = (string) $paymongoRefundId;
            } elseif ($payoutProvider === 'manual' && $payoutReference === '') {
                throw ValidationException::withMessages(['payout_reference' => ['Enter the manual payout or transaction reference.']]);
            }

            try {
                if ($payoutProvider !== 'simulation') $cashflow->debit(
                    (int) $refund->store_id,
                    (float) $refund->amount,
                    'finance_refund',
                    (int) $refund->id,
                    $request->user()->id,
                    $payoutProvider === 'paymongo' ? 'Refund to original PayMongo payment' : 'Refund sent to ' . $refund->refund_account_name,
                    $payoutProvider === 'paymongo' ? 'paymongo' : $refund->refund_method,
                    ['payout_reference' => $payoutReference]
                );
            } catch (\RuntimeException $e) {
                throw ValidationException::withMessages(['amount' => [$e->getMessage()]]);
            }

            $refund->update([
                'status' => 'sent',
                'payout_reference' => $payoutReference,
                'payout_provider' => $payoutProvider,
                'paymongo_refund_id' => $paymongoRefundId,
                'sent_by' => $request->user()->id,
                'sent_at' => now(),
                'notes' => $payoutProvider === 'simulation'
                    ? trim("Awaiting payout.\n" . ($data['notes'] ?? $refund->notes ?? ''))
                    : ($data['notes'] ?? $refund->notes),
            ]);

            if ($refund->order_type === 'ecommerce_return') {
                $linkedReturn = EcommerceOrderReturn::query()
                    ->where('store_id', $refund->store_id)
                    ->lockForUpdate()
                    ->find($refund->order_id);
                if ($linkedReturn && in_array($linkedReturn->status, ['received', 'refund_pending'], true)) {
                    $linkedReturn->update(['status' => 'refunded', 'resolved_at' => now()]);
                    $order = $linkedReturn->order()->first();
                    if ($order && (float) $refund->amount >= (float) $order->total_amount) {
                        $order->update(['status' => 'refunded', 'payment_status' => 'refunded']);
                    }
                }
            } elseif ($refund->order_type === 'ecommerce_order') {
                $cancelledOrder ??= EcommerceOrder::query()->where('store_id', $refund->store_id)
                    ->where('status', 'cancelled')->lockForUpdate()->findOrFail($refund->order_id);
                $cancelledOrder->update(['payment_status' => 'refunded']);
            } elseif ($refund->order_type === 'sales') {
                $sale = SalesOrder::query()->where('store_id', $refund->store_id)
                    ->lockForUpdate()->findOrFail($refund->order_id);
                if ((float) $refund->amount >= (float) $sale->total_amount) {
                    $sale->update(['status' => 'refunded']);
                }
            }
        });

        if ($simulationPending) {
            return response()->json([
                'message' => 'Refund is awaiting payout.',
                'data' => $refund->fresh(),
            ]);
        }

        if ($linkedReturn?->user_id) {
            $this->notify((int) $linkedReturn->user_id, [
                'module' => 'ecommerce',
                'entity_type' => 'ecommerce_order_return',
                'entity_id' => (int) $linkedReturn->id,
                'action' => 'refund_sent',
                'title' => 'Refund sent',
                'message' => 'Finance sent your refund to your selected ' . strtoupper((string) $refund->refund_method) . ' account.',
                'severity' => 'success',
                'store_id' => (int) $refund->store_id,
                'link' => '/orders/' . (int) $linkedReturn->order_id,
                'data' => ['payout_reference' => $refund->fresh()->payout_reference],
            ]);
        }

        if ($cancelledOrder?->user_id) {
            $this->notify((int) $cancelledOrder->user_id, [
                'module' => 'ecommerce', 'entity_type' => 'ecommerce_order',
                'entity_id' => (int) $cancelledOrder->id, 'action' => 'refund_sent',
                'title' => 'Refund sent',
                'message' => 'Your cancelled order refund has been sent.',
                'severity' => 'success', 'store_id' => (int) $refund->store_id,
                'link' => '/orders/' . (int) $cancelledOrder->id,
            ]);
        }

        return response()->json(['message' => 'Refund marked as sent to the customer.', 'data' => $refund->fresh()]);
    }

    public function startTestWalletTransfer(Request $request, FinanceRefund $refund, PaymongoTestWalletTransferService $wallet): JsonResponse
    {
        $this->authorizeStore($request, $refund);
        if (!$wallet->isAvailable()) {
            throw ValidationException::withMessages(['provider' => ['Wallet transfer verification is currently unavailable.']]);
        }

        DB::transaction(function () use ($request, $refund): void {
            $refund = FinanceRefund::query()->where('store_id', $request->user()->store_id)
                ->lockForUpdate()->findOrFail($refund->id);
            if ($refund->status !== 'approved' || !in_array($refund->order_type, ['ecommerce_return', 'ecommerce_order'], true)
                || !in_array($refund->refund_method, ['gcash', 'maya'], true)
                || !$refund->refund_account_name || !$refund->refund_account_number) {
                throw ValidationException::withMessages(['status' => ['Only an approved ecommerce GCash or Maya refund with customer account details can start transfer verification.']]);
            }
            if ((float) $refund->amount <= 0 || (float) $refund->amount > 50000) {
                throw ValidationException::withMessages(['amount' => ['Transfers through InstaPay must be between PHP 0.01 and PHP 50,000.']]);
            }
            $orderId = $refund->order_type === 'ecommerce_return'
                ? EcommerceOrderReturn::query()->where('store_id', $refund->store_id)->findOrFail($refund->order_id)->order_id
                : EcommerceOrder::query()->where('store_id', $refund->store_id)->where('status', 'cancelled')->findOrFail($refund->order_id)->id;
            $order = EcommerceOrder::query()->where('store_id', $refund->store_id)->findOrFail($orderId);
            if ($order->payment_status !== 'paid' || !PaymongoIntent::query()
                ->where('payable_type', 'ecommerce_order')->where('payable_id', $orderId)
                ->where('status', 'succeeded')->exists()) {
                throw ValidationException::withMessages(['provider' => ['A successful order payment is required before transfer verification.']]);
            }
            $refund->update(['status' => 'processing', 'payout_provider' => 'paymongo_wallet_test', 'transfer_error' => null]);
        });

        try {
            $transfer = $wallet->create($refund);
        } catch (\Throwable $e) {
            report($e);
            $refund->update(['transfer_error' => 'Transfer verification could not be completed. Review the provider status before retrying.']);
            throw ValidationException::withMessages(['provider' => ['Transfer verification could not be completed. Review the provider status before retrying.']]);
        }

        $refund->update([
            'paymongo_transfer_id' => $transfer['transfer_id'],
            'paymongo_batch_transfer_id' => $transfer['batch_id'] ?: null,
        ]);

        return response()->json(['message' => 'Transfer verification started. Check the status before recording the payout.', 'data' => $refund->fresh()]);
    }

    public function refreshTestWalletTransfer(Request $request, FinanceRefund $refund, PaymongoTestWalletTransferService $wallet): JsonResponse
    {
        $this->authorizeStore($request, $refund);
        if ($refund->status !== 'processing' || $refund->payout_provider !== 'paymongo_wallet_test'
            || !$refund->paymongo_transfer_id) {
            throw ValidationException::withMessages(['status' => ['This refund has no active transfer verification to check.']]);
        }

        try {
            $transfer = $wallet->retrieve((string) $refund->paymongo_transfer_id);
        } catch (\Throwable $e) {
            report($e);
            throw ValidationException::withMessages(['provider' => ['Unable to verify the transfer status. Please retry shortly.']]);
        }

        if (!in_array($transfer['status'], ['pending', 'succeeded', 'failed'], true)) {
            throw ValidationException::withMessages(['provider' => ['PayMongo returned an unknown transfer status. Check the PayMongo dashboard before taking action.']]);
        }

        if ($transfer['status'] === 'succeeded') {
            FinanceRefund::query()->where('id', $refund->id)->where('store_id', $refund->store_id)
                ->where('status', 'processing')->update([
                    'status' => 'awaiting_payout',
                    'payout_reference' => null,
                    'transfer_error' => null,
                    'notes' => trim("Transfer verification complete. Awaiting actual payout.\n" . ($refund->notes ?? '')),
                ]);
        } elseif ($transfer['status'] === 'failed') {
            FinanceRefund::query()->where('id', $refund->id)->where('store_id', $refund->store_id)
                ->where('status', 'processing')->update([
                    'status' => 'transfer_failed',
                    'transfer_error' => 'The transfer could not be verified. Review the payout details before retrying.',
                ]);
        }

        return response()->json(['message' => match ($transfer['status']) {
            'succeeded' => 'Transfer verification complete. Refund is awaiting an actual payout.',
            'failed' => 'The transfer could not be verified.',
            default => 'Transfer verification is still processing.',
        }, 'data' => $refund->fresh()]);
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
        if ($refund->refund_method === 'card' && $provider !== 'paymongo') {
            throw ValidationException::withMessages(['provider' => ['Card refunds must return to the original payment method.']]);
        }
        if ($provider === 'manual' && $originalOrder && $refundRouting->canRefundToOriginal($originalOrder)) {
            throw ValidationException::withMessages(['provider' => ['This order must be refunded through its original payment method.']]);
        }
        if ($provider === 'paymongo' && (!$originalOrder || !$refundRouting->canRefundToOriginal($originalOrder))) {
            throw ValidationException::withMessages(['provider' => ['This refund is not eligible for return to its original payment method.']]);
        }
        if ($provider === 'simulation' && in_array($refund->refund_method, ['gcash', 'maya'], true)) {
            throw ValidationException::withMessages(['provider' => ['Use wallet transfer verification for GCash or Maya refunds.']]);
        }
        if ($provider === 'paymongo' && $refund->refund_method !== 'card') {
            throw ValidationException::withMessages(['provider' => ['The customer selected a manual refund destination. Use Manual payout for that account.']]);
        }
    }
}
