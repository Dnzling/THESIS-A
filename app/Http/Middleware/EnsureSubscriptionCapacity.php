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
            'suppliers' => [['max_suppliers', DB::table('suppliers')->where('store_id', $storeId)->whereNull('deleted_at')->count(), 'suppliers']],
            'vehicles' => [['max_trucks', DB::table('ecommerce_delivery_vehicles')->where('store_id', $storeId)->count(), 'delivery vehicles']],
            'branches' => array_filter([
                ['max_branches', DB::table('branches')->where('store_id', $storeId)->count(), 'branches'],
                $request->input('branch_type') === 'warehouse'
                    ? ['max_warehouses', DB::table('branches')->where('store_id', $storeId)->where('branch_type', 'warehouse')->count(), 'warehouses']
                    : null,
            ]),
            'branch_update' => $request->input('branch_type') === 'warehouse'
                && DB::table('branches')->where('id', $request->route('branch'))->where('store_id', $storeId)->value('branch_type') === 'storefront'
                ? [['max_warehouses', DB::table('branches')->where('store_id', $storeId)->where('branch_type', 'warehouse')->count(), 'warehouses']]
                : [],
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
}
