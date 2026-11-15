<?php

namespace App\Services\Store;

use App\Models\Store\Store;

class ReturnResolutionPolicy
{
    public const REFUND_METHODS = ['gcash', 'maya', 'bank_transfer'];

    public function forStore(?Store $store): array
    {
        $settings = is_array($store?->settings) ? $store->settings : [];
        $saved = is_array($settings['return_resolution'] ?? null) ? $settings['return_resolution'] : [];
        $methods = array_values(array_intersect(
            self::REFUND_METHODS,
            array_map('strval', is_array($saved['refund_methods'] ?? null) ? $saved['refund_methods'] : ['gcash', 'maya'])
        ));

        return [
            'allow_refunds' => (bool) ($saved['allow_refunds'] ?? true),
            'allow_replacements' => true,
            'default_resolution' => ($saved['allow_refunds'] ?? true) && ($saved['default_resolution'] ?? 'replacement') === 'refund'
                ? 'refund'
                : 'replacement',
            'allow_higher_value_replacements' => (bool) ($saved['allow_higher_value_replacements'] ?? true),
            'refund_methods' => $methods ?: ['gcash', 'maya'],
            'return_window_days' => min(30, max(1, (int) ($saved['return_window_days'] ?? 7))),
        ];
    }

    public function refundAllowed(Store $store, string $method): bool
    {
        $policy = $this->forStore($store);

        return $policy['allow_refunds'] && in_array($method, $policy['refund_methods'], true);
    }
}
