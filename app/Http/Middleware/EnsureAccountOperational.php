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
            if ($store && $store->subscription_status === 'expired') {
                if ($request->is('api/auth/*', 'api/payments/*', 'api/public/subscription-plans*', 'api/stores/*/verification/*', 'api/store-verification/*', 'api/user/navigation')) {
                    return $next($request);
                }
                if (!$request->is('api/*') && $request->is('store/billing', 'subscription-plans', 'subscription-checkout', 'store/positions/setup')) {
                    return $next($request);
                }

                return $request->is('api/*')
                    ? response()->json(['success' => false, 'code' => 'TRIAL_EXPIRED', 'message' => 'Your free trial has ended. Upgrade to continue.'], 403)
                    : redirect('/store/billing');
            }
            if ($store && in_array((string) $store->status, ['suspended', 'inactive'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your store account is currently suspended. Please contact platform admin.',
                ], 403);
            }
        }

        // Supplier-side restriction
        $portal = SupplierPortal::query()->with('supplier:id,status')->where('user_id', (int) $user->id)->first();
        if ($portal) {
            if (in_array((string) ($portal->supplier?->status ?? ''), ['inactive', 'blacklisted'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your supplier account is currently suspended. Please contact platform admin.',
                ], 403);
            }
        }

        return $next($request);
    }
}
