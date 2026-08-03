<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\SubscriptionPlan;
use App\Models\Store\Branch;
use App\Models\Store\Store;
use App\Models\Admin\ViolationReport;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!auth()->user()?->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $query = Store::query()->withCount(['users', 'products']);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('store_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tier')) {
            $tier = strtolower(trim((string) $request->input('tier')));
            $planId = SubscriptionPlan::query()->where('plan_key', $tier)->value('id');
            $query->when($planId, fn ($q) => $q->where('subscription_tier', $planId), fn ($q) => $q->whereRaw('1 = 0'));
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        $stores = $query
            ->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 20));

        $stores->getCollection()->transform(function (Store $store) {
            return $this->formatStore($store);
        });

        return response()->json([
            'success' => true,
            'data' => $stores,
        ]);
    }

    public function stats(): JsonResponse
    {
        if (!auth()->user()?->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $today = Carbon::today();

        $totalStores = Store::query()->count();
        $paid = Store::query()
            ->whereNotNull('subscription_tier')
            ->whereHas('subscriptionPlan', function ($planQuery) {
                $planQuery->where('plan_key', '!=', 'free');
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('subscription_ends_at')
                    ->orWhereDate('subscription_ends_at', '>=', $today);
            })
            ->count();
        $overdue = Store::query()
            ->whereNotNull('subscription_tier')
            ->whereHas('subscriptionPlan', function ($planQuery) {
                $planQuery->where('plan_key', '!=', 'free');
            })
            ->whereNotNull('subscription_ends_at')
            ->whereDate('subscription_ends_at', '<', $today)
            ->count();
        $unpaid = Store::query()
            ->where(function ($query) {
                $query->whereNull('subscription_tier')
                    ->orWhereHas('subscriptionPlan', function ($planQuery) {
                        $planQuery->where('plan_key', 'free');
                    });
            })
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_stores' => $totalStores,
                'paid' => $paid,
                'overdue' => $overdue,
                'unpaid' => $unpaid,
            ],
        ]);
    }

    public function update(Request $request, Store $store): JsonResponse
    {
        if (!auth()->user()?->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'subscription_tier' => 'required|string|exists:subscription_plans,plan_key',
            'subscription_ends_at' => 'nullable|date',
            'status' => 'nullable|in:pending,active,inactive,suspended,banned,verified,rejected',
        ]);

        $planId = SubscriptionPlan::query()->where('plan_key', strtolower((string) $validated['subscription_tier']))->value('id');

        $store->update([
            'subscription_tier' => $planId,
            'subscription_ends_at' => $validated['subscription_ends_at'] ?? null,
            'status' => $validated['status'] ?? $store->status,
        ]);

        if (strtolower((string) $validated['subscription_tier']) !== 'free') {
            $this->activatePaidSubscription($store);
        }

        return response()->json([
            'success' => true,
            'message' => 'Subscription updated successfully.',
            'data' => $this->formatStore($store->fresh(['users', 'products'])),
        ]);
    }

    public function extend(Request $request, Store $store): JsonResponse
    {
        if (!auth()->user()?->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'days' => 'nullable|integer|min:1|max:3650',
        ]);

        $days = (int) ($validated['days'] ?? 30);
        $base = $store->subscription_ends_at
            ? Carbon::parse($store->subscription_ends_at)
            : Carbon::today();

        if ($base->lt(Carbon::today())) {
            $base = Carbon::today();
        }

        $store->update([
            'subscription_ends_at' => $base->addDays($days)->toDateString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription extended successfully.',
            'data' => $this->formatStore($store->fresh(['users', 'products'])),
        ]);
    }

    private function formatStore(Store $store): array
    {
        $today = Carbon::today();
        $endsAt = $store->subscription_ends_at ? Carbon::parse($store->subscription_ends_at) : null;
        $rawStatus = strtolower(trim((string) $store->status));
        $displayStatus = $rawStatus === 'pending' ? 'unverified' : $store->status;
        $statusDetails = $this->getStatusDetailsForStore((int) $store->id, (string) $store->status);

        $subscriptionStatus = 'free';
        $daysRemaining = null;
        if (($store->subscriptionPlan?->plan_key ?? 'free') !== 'free') {
            if (!$endsAt) {
                $subscriptionStatus = 'paid';
            } else {
                $daysRemaining = $today->diffInDays($endsAt, false);
                $subscriptionStatus = $daysRemaining < 0 ? 'overdue' : 'paid';
            }
        } else {
            $subscriptionStatus = 'unpaid';
        }

        return [
            'id' => $store->id,
            'store_name' => $store->store_name,
            'email' => $store->email,
            'contact_person' => $store->contact_person,
            'contact_number' => $store->contact_number,
            'status' => $displayStatus,
            'subscription_tier' => $store->subscriptionPlan?->plan_key ?? 'free',
            'subscription_ends_at' => optional($endsAt)->toDateString(),
            'subscription_status' => $subscriptionStatus,
            'days_remaining' => $daysRemaining,
            'status_details' => $statusDetails,
            'users_count' => $store->users_count ?? $store->users()->count(),
            'products_count' => $store->products_count ?? $store->products()->count(),
            'created_at' => optional($store->created_at)->toDateTimeString(),
        ];
    }

    private function getStatusDetailsForStore(int $storeId, string $storeStatus): ?array
    {
        $normalizedStatus = strtolower(trim($storeStatus));
        if (!in_array($normalizedStatus, ['suspended', 'banned'], true)) {
            return null;
        }

        $latestAction = ViolationReport::query()
            ->where('store_id', $storeId)
            ->where('status', 'actioned')
            ->whereIn('action_type', ['suspended', 'banned'])
            ->orderByDesc('actioned_at')
            ->orderByDesc('id')
            ->first(['id', 'action_type', 'action_reason', 'actioned_at']);

        if (!$latestAction) {
            return [
                'action_type' => $normalizedStatus,
                'action_reason' => null,
                'actioned_at' => null,
                'suspension_days' => $normalizedStatus === 'suspended' ? 30 : null,
                'suspension_days_remaining' => $normalizedStatus === 'suspended' ? null : null,
            ];
        }

        $actionedAt = $latestAction->actioned_at ? Carbon::parse($latestAction->actioned_at) : null;
        $remaining = null;
        $suspensionDays = null;
        if (($latestAction->action_type ?? '') === 'suspended' && $actionedAt) {
            $suspensionDays = 30;
            $elapsed = $actionedAt->diffInDays(Carbon::today());
            $remaining = max(0, $suspensionDays - $elapsed);
        }

        return [
            'action_type' => $latestAction->action_type,
            'action_reason' => $latestAction->action_reason,
            'actioned_at' => optional($actionedAt)->toDateTimeString(),
            'suspension_days' => $suspensionDays,
            'suspension_days_remaining' => $remaining,
        ];
    }

    private function activatePaidSubscription(Store $store): void
    {
        $settings = is_array($store->settings) ? $store->settings : [];
        $settings['trial'] = false;
        $settings['requires_verification'] = true;
        $store->settings = $settings;

        if ($store->status !== 'verified') {
            $store->status = 'pending';
        }

        $this->ensureStoreHasMainBranch($store);

        $store->save();
    }

    private function ensureStoreHasMainBranch(Store $store): void
    {
        if ($store->branches()->exists()) {
            return;
        }

        $storeCode = $store->store_code;
        if (!$storeCode) {
            $storeCode = 'STORE-' . str_pad((string) $store->id, 6, '0', STR_PAD_LEFT);
            $store->store_code = $storeCode;
            $store->save();
        }

        $branchCode = $storeCode . '-MAIN';
        if (Branch::query()->where('branch_code', $branchCode)->exists()) {
            $branchCode = $storeCode . '-MAIN-' . str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
        }

        Branch::create([
            'store_id' => $store->id,
            'name' => ($store->name ?: 'Main') . ' - Main',
            'contact_number' => '0000000000',
            'branch_code' => $branchCode,
            'is_main_branch' => true,
            'status' => 'active',
        ]);
    }
}
