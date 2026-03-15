<?php

namespace App\Services\Customer;

use App\Models\Core\User;

class CustomerVerificationService
{
    /**
     * Call this from Sales/Order creation once Sales module exists.
     */
    public function flagForOrder(User $user, float $orderTotal): bool
    {
        return $this->flagIfThresholdExceeded($user, $orderTotal);
    }

    public function flagIfThresholdExceeded(User $user, float $orderTotal): bool
    {
        $threshold = (float) (config('customer_verification.threshold', 50000));

        if ($orderTotal < $threshold) {
            return false;
        }

        if ($user->customer_verification_status !== 'unverified') {
            return false;
        }

        $user->update([
            'customer_verification_required' => true,
            'customer_verification_status' => 'pending',
            'customer_verification_trigger_amount' => $orderTotal,
            'customer_verification_triggered_at' => now(),
        ]);

        return true;
    }
}
