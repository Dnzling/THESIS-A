<?php

namespace App\Services\Ecommerce;

use App\Models\Store\Store;
use Carbon\CarbonImmutable;

class FulfillmentWindowService
{
    public function settings(Store $store): array
    {
        $saved = $store->settings['fulfillment_windows'] ?? [];
        $defaults = [
            'pickup' => ['days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'], 'start' => '09:00', 'end' => '18:00', 'lead_days' => 1],
            'delivery' => ['days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'], 'start' => '08:00', 'end' => '20:00', 'lead_days' => 2],
        ];

        return [
            'pickup' => array_merge($defaults['pickup'], is_array($saved['pickup'] ?? null) ? $saved['pickup'] : []),
            'delivery' => array_merge($defaults['delivery'], is_array($saved['delivery'] ?? null) ? $saved['delivery'] : []),
        ];
    }

    public function availability(Store $store, string $method): array
    {
        $window = $this->settings($store)[$method];
        $now = CarbonImmutable::now('Asia/Manila');
        $today = $now->startOfDay();
        $dates = [];

        for ($offset = (int) $window['lead_days']; $offset <= 30 && count($dates) < 14; $offset++) {
            $date = $today->addDays($offset);
            if ($offset === 0 && $now->format('H:i') >= $window['end']) {
                continue;
            }
            if (in_array(strtolower($date->englishDayOfWeek), $window['days'], true)) {
                $dates[] = $date->toDateString();
            }
        }

        return ['dates' => $dates, 'window' => $window];
    }

    public function isAvailable(Store $store, string $method, string $date): bool
    {
        return in_array($date, $this->availability($store, $method)['dates'], true);
    }
}
