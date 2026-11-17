<?php

namespace App\Http\Middleware;

use App\Models\Admin\SubscriptionPlan;
use App\Models\Hr\Employee;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionCapacity
{
    public function handle(Request $request, Closure $next, string $resource): Response
    {
        $user = $request->user();
        $storeId = $user?->store_id ?: ($user
            ? Employee::query()->where('user_id', $user->id)->value('store_id')
            : null);

        if (!$storeId) {
            return $next($request);
        }

        if (($resource === 'branches' || $resource === 'branch_update')
            && $request->input('branch_type') === 'warehouse') {
            return $next($request);
        }

        $planId = DB::table('stores')->where('id', $storeId)->value('subscription_tier');
        $plan = SubscriptionPlan::query()->find($planId);
        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'The store subscription plan is not configured.',
            ], 422);
        }

        $checks = match ($resource) {
            'users' => [['max_user_accounts', DB::table('users')->where('store_id', $storeId)->whereNull('deleted_at')->count(), 'user accounts']],
            'products' => [['max_products', DB::table('products')->where('store_id', $storeId)->whereNull('deleted_at')->count(), 'products']],
            'branches' => $request->input('branch_type', 'storefront') === 'warehouse'
                ? []
                : [$this->capacityCheckForStorefront((int) $storeId)],
            'branch_update' => $this->branchUpdateChecks($request, (int) $storeId),
            default => [],
        };

        foreach ($checks as [$column, $used, $label]) {
            $limit = $plan->{$column};
            if ($limit !== null && $used >= $limit) {
                return response()->json([
                    'success' => false,
                    'message' => "Your {$plan->name} plan allows up to {$limit} {$label}. Upgrade your subscription to add more.",
                    'errors' => ['subscription' => ["The {$label} limit has been reached."]],
                ], 422);
            }
        }

        return $next($request);
    }

    private function capacityCheckForStorefront(int $storeId): array
    {
        return [
            'max_branches',
            DB::table('branches')->where('store_id', $storeId)
                ->where('branch_type', 'storefront')->count(),
            'branches',
        ];
    }

    private function branchUpdateChecks(Request $request, int $storeId): array
    {
        $currentType = DB::table('branches')->where('store_id', $storeId)
            ->where('id', $request->route('branch'))->value('branch_type');
        $newType = $request->input('branch_type');

        if (!$currentType || $newType !== 'storefront' || $currentType === $newType) {
            return [];
        }

        return [$this->capacityCheckForStorefront($storeId)];
    }
}
