<?php

namespace App\Services\Store;

use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Builder;

class StoreSalesAccess
{
    public const GRACE_DAYS = 7;

    public function canAcceptNewSales(Store $store): bool
    {
        if (!in_array($store->status, ['active', 'verified'], true)) {
            return false;
        }

        return match ($store->subscription_status) {
            'trial' => $store->trial_ends_at?->isFuture() === true,
            'paid' => $store->subscription_ends_at === null
                || $store->subscription_ends_at->startOfDay()->greaterThanOrEqualTo(now()->startOfDay()->subDays(self::GRACE_DAYS)),
            default => false,
        };
    }

    public function scopeEligible(Builder $query): Builder
    {
        return $query->whereIn('status', ['active', 'verified'])
            ->where(function (Builder $subscription) {
                $subscription->where(function (Builder $trial) {
                    $trial->where('subscription_status', 'trial')
                        ->where('trial_ends_at', '>', now());
                })->orWhere(function (Builder $paid) {
                    $paid->where('subscription_status', 'paid')
                        ->where(function (Builder $term) {
                            $term->whereNull('subscription_ends_at')
                                ->orWhereDate('subscription_ends_at', '>=', now()->subDays(self::GRACE_DAYS)->toDateString());
                        });
                });
            });
    }
}
