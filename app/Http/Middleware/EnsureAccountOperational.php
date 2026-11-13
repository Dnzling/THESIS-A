<?php

namespace App\Http\Middleware;

use App\Models\Procurement\SupplierPortal\SupplierPortal;
use App\Models\Store\Store;
use App\Services\Store\StoreTrialService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountOperational
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        // Store-side restriction
        if (!empty($user->store_id)) {
            $store = Store::query()->find((int) $user->store_id);
            if ($store) {
                app(StoreTrialService::class)->expireIfNeeded($store);
            }
            if ($store && $store->status === 'suspended') {
                return response()->json(['success' => false, 'message' => 'Your store account is suspended. Please contact platform admin.'], 403);
            }
            $legacyExpiredTrial = $store
                && $store->status === 'inactive'
                && $store->subscription_status === 'expired'
                && !$store->deactivated_at
                && !$store->deactivation_reason;
            if ($store && in_array((string) $store->status, ['suspended', 'inactive'], true) && !$legacyExpiredTrial) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your store account is currently suspended. Please contact platform admin.',
                ], 403);
            }
        }

        // Supplier-side restriction
        $portal = SupplierPortal::query()->with('supplier:id,status')->where('user_id', (int) $user->id)->first();
        if ($portal) {
            $supplierStatus = (string) ($portal->supplier?->status ?? '');

            // A supplier is intentionally created as inactive while its portal
            // application is pending. Do not treat that onboarding state as a
            // suspension or the supplier cannot reach the document-upload step.
            // Once approved, however, an inactive supplier is an operationally
            // disabled account and must remain blocked.
            $isBlocked = $supplierStatus === 'blacklisted'
                || ($supplierStatus === 'inactive' && $portal->status === 'approved');

            if ($isBlocked) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your supplier account is currently suspended. Please contact platform admin.',
                ], 403);
            }
        }

        return $next($request);
    }
}
