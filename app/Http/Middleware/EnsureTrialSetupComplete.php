<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Store\Store;
use App\Services\Store\StoreTrialService;

class EnsureTrialSetupComplete
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user?->hasRole('owner') && !$user->store_id) {
            return redirect('/store/registration');
        }
        if ($user?->hasRole('owner') && $user->store_id
            && !$request->is('store/positions/setup', 'store/billing')) {
            $store = Store::find((int) $user->store_id);
            if ($store && app(StoreTrialService::class)->needsPositionSetup($store)) {
                return redirect('/store/positions/setup');
            }
        }
        return $next($request);
    }
}
