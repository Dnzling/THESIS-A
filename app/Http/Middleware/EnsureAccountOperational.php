<?php

namespace App\Http\Middleware;

use App\Models\Procurement\SupplierPortal\SupplierPortal;
use App\Models\Store\Store;
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
            $store = Store::query()->select(['id', 'status'])->find((int) $user->store_id);
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

