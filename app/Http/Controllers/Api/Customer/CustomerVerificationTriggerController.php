<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Core\User;
use App\Services\Customer\CustomerVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerVerificationTriggerController extends Controller
{
    public function trigger(Request $request, CustomerVerificationService $service): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'order_total' => 'required|numeric|min:0',
        ]);

        $user = User::findOrFail($validated['user_id']);

        $triggered = $service->flagIfThresholdExceeded($user, (float) $validated['order_total']);

        return response()->json([
            'success' => true,
            'triggered' => $triggered,
            'threshold' => (float) config('customer_verification.threshold', 50000),
            'data' => $user->fresh('customer'),
        ]);
    }
}
