<?php

namespace App\Services\Payment;

use App\Models\Ecommerce\EcommerceOrder;
use App\Models\PaymongoIntent;

class EcommerceRefundRoutingService
{
    public function originalMethod(EcommerceOrder $order): ?string
    {
        if ($order->payment_method === 'card') {
            return 'card';
        }

        if ($order->payment_method !== 'e_wallet') {
            return null;
        }

        $allowed = PaymongoIntent::query()
            ->where('payable_type', 'ecommerce_order')
            ->where('payable_id', $order->id)
            ->where('status', 'succeeded')
            ->latest('id')
            ->value('payment_method_allowed');

        $method = strtolower(trim(explode(',', (string) $allowed)[0] ?? ''));

        return match ($method) {
            'paymaya', 'maya' => 'maya',
            'gcash' => 'gcash',
            'grab_pay' => 'grab_pay',
            default => null,
        };
    }

    public function canRefundToOriginal(EcommerceOrder $order): bool
    {
        if ($order->payment_status !== 'paid'
            || !str_starts_with((string) config('paymongo.secret'), 'sk_live_')
            || !in_array($order->payment_method, ['card', 'e_wallet'], true)
            || !$this->originalMethod($order)) {
            return false;
        }

        return PaymongoIntent::query()
            ->where('payable_type', 'ecommerce_order')
            ->where('payable_id', $order->id)
            ->where('status', 'succeeded')
            ->exists();
    }
}
