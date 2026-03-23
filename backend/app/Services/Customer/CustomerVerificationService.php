<?php

namespace App\Services\Customer;

use App\Models\Customer\Customer;
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

        $customer = Customer::firstOrCreate(
            ['user_id' => $user->id],
            ['verification_status' => 'unverified']
        );

        if ($customer->verification_status !== 'unverified') {
            return false;
        }

        $customer->update([
            'verification_required' => true,
            'verification_status' => 'pending',
            'verification_trigger_amount' => $orderTotal,
            'verification_triggered_at' => now(),
        ]);

        return true;
    }
}
