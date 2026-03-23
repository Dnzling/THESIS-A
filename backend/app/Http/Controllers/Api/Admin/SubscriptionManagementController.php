<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store\Store;
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
            $query->where('subscription_tier', (string) $request->input('tier'));
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
        $expiringDate = Carbon::today()->addDays(14);

        $totalStores = Store::query()->count();
        $activePaid = Store::query()
            ->where('subscription_tier', '!=', 'free')
            ->whereDate('subscription_ends_at', '>=', $today)
            ->count();
        $expiringSoon = Store::query()
            ->whereNotNull('subscription_ends_at')
            ->whereDate('subscription_ends_at', '>=', $today)
            ->whereDate('subscription_ends_at', '<=', $expiringDate)
            ->count();
        $expired = Store::query()
            ->whereNotNull('subscription_ends_at')
            ->whereDate('subscription_ends_at', '<', $today)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_stores' => $totalStores,
                'active_paid' => $activePaid,
                'expiring_soon' => $expiringSoon,
                'expired' => $expired,
            ],
        ]);
    }

    public function update(Request $request, Store $store): JsonResponse
    {
        if (!auth()->user()?->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'subscription_tier' => 'required|in:free,basic,premium,enterprise',
            'subscription_ends_at' => 'nullable|date',
            'status' => 'nullable|in:pending,active,inactive,suspended,verified,rejected',
        ]);

        $store->update([
            'subscription_tier' => $validated['subscription_tier'],
            'subscription_ends_at' => $validated['subscription_ends_at'] ?? null,
            'status' => $validated['status'] ?? $store->status,
        ]);

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

        $subscriptionStatus = 'free';
        $daysRemaining = null;
        if ($store->subscription_tier !== 'free') {
            if (!$endsAt) {
                $subscriptionStatus = 'active';
            } else {
                $daysRemaining = $today->diffInDays($endsAt, false);
                $subscriptionStatus = $daysRemaining < 0 ? 'expired' : 'active';
            }
        }

        return [
            'id' => $store->id,
            'store_name' => $store->store_name,
            'email' => $store->email,
            'contact_person' => $store->contact_person,
            'contact_number' => $store->contact_number,
            'status' => $store->status,
            'subscription_tier' => $store->subscription_tier ?? 'free',
            'subscription_ends_at' => optional($endsAt)->toDateString(),
            'subscription_status' => $subscriptionStatus,
            'days_remaining' => $daysRemaining,
            'users_count' => $store->users_count ?? $store->users()->count(),
            'products_count' => $store->products_count ?? $store->products()->count(),
            'created_at' => optional($store->created_at)->toDateTimeString(),
        ];
    }
}

