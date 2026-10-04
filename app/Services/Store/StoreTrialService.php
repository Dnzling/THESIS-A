<?php

namespace App\Services\Store;

use App\Models\Store\Store;

class StoreTrialService
{
    public function expireIfNeeded(Store $store): bool
    {
        if ($store->subscription_status !== 'trial' || !$store->trial_ends_at || $store->trial_ends_at->isFuture()) {
            return false;
        }

        $store->forceFill([
            'status' => $store->status === 'active' ? 'inactive' : $store->status,
            'subscription_status' => 'expired',
        ])->save();

        return true;
    }

    public function daysRemaining(Store $store): int
    {
        return $store->subscription_status === 'trial' && $store->trial_ends_at
            ? max(0, now()->startOfDay()->diffInDays($store->trial_ends_at->startOfDay(), false))
            : 0;
    }

    public function needsPositionSetup(Store $store): bool
    {
        return $store->trial_started_at !== null
            && $store->subscription_status === 'paid'
            && $store->position_setup_completed_at === null;
    }
}
