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
            'branches' => [$this->capacityCheckForBranchType($storeId, $request->input('branch_type', 'storefront'))],
            'branch_update' => $this->branchUpdateChecks($request, (int) $storeId),
            'warehouse_registration' => DB::table('branches')->where('store_id', $storeId)
                ->where('id', $request->input('branch_id'))->value('branch_type') === 'storefront'
                ? [$this->capacityCheckForBranchType((int) $storeId, 'warehouse')]
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

    private function capacityCheckForBranchType(int $storeId, string $type): array
    {
        $warehouse = $type === 'warehouse';
        return [
            $warehouse ? 'max_warehouses' : 'max_branches',
            DB::table('branches')->where('store_id', $storeId)
                ->where('branch_type', $warehouse ? 'warehouse' : 'storefront')->count(),
            $warehouse ? 'warehouses' : 'branches',
        ];
    }

    private function branchUpdateChecks(Request $request, int $storeId): array
    {
        $currentType = DB::table('branches')->where('store_id', $storeId)
            ->where('id', $request->route('branch'))->value('branch_type');
        $newType = $request->input('branch_type');

        if (!$currentType || !$newType || $currentType === $newType) {
            return [];
        }

        return [$this->capacityCheckForBranchType($storeId, $newType)];
    }
}
