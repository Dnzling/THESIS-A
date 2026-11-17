<?php

namespace App\Services\Store;

use App\Models\Store\Store;

class ReturnResolutionPolicy
{
    private const REFUND_METHODS = ['gcash', 'maya'];

    public function forStore(?Store $store): array
    {
        $settings = is_array($store?->settings) ? $store->settings : [];
        $saved = is_array($settings['return_resolution'] ?? null) ? $settings['return_resolution'] : [];
        $wholesale = is_array($settings['wholesale'] ?? null) ? $settings['wholesale'] : [];

        return [
            'allow_refunds' => (bool) ($saved['allow_refunds'] ?? true),
            'allow_replacements' => true,
            'default_resolution' => ($saved['allow_refunds'] ?? true) && ($saved['default_resolution'] ?? 'replacement') === 'refund'
                ? 'refund'
                : 'replacement',
            'allow_higher_value_replacements' => (bool) ($saved['allow_higher_value_replacements'] ?? true),
            'return_window_days' => min(30, max(1, (int) ($saved['return_window_days'] ?? 7))),
            // These are system-supported payout methods, not store-configurable payment destinations.
            'refund_methods' => self::REFUND_METHODS,
            'wholesale_enabled' => (bool) ($wholesale['enabled'] ?? false),
        ];
    }

    public function refundAllowed(Store $store, ?string $method = null): bool
    {
        $policy = $this->forStore($store);

        return $policy['allow_refunds'];
    }
}
