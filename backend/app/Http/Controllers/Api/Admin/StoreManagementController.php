<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class StoreManagementController extends Controller
{
    private ?array $storeColumns = null;

    public function index(Request $request): JsonResponse
    {
        if (!auth()->user()?->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $query = Store::query()
            ->withCount([
                'users',
                'products',
                'users as active_users_count' => fn($q) => $q->where('is_active', true),
                'products as active_products_count' => fn($q) => $q->where('is_active', true),
            ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $first = true;
                foreach ($this->searchableColumns() as $column) {
                    if ($first) {
                        $q->where($column, 'like', "%{$search}%");
                        $first = false;
                    } else {
                        $q->orWhere($column, 'like', "%{$search}%");
                    }
                }
            });
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        $stores = $query
            ->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 20));

        $stores->getCollection()->transform(fn(Store $store) => $this->formatStore($store));

        return response()->json([
            'success' => true,
            'data' => $stores,
        ]);
    }

    public function show(Store $store): JsonResponse
    {
        if (!auth()->user()?->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $store->load([
            'users:id,fname,lname,email,role_id,is_active,store_id,created_at',
            'users.role:id,name,display_name',
            'products:id,store_id,sku,product_name,is_active,cost_price,tax_rate,created_at',
        ]);

        $store->loadCount([
            'users',
            'products',
            'users as active_users_count' => fn($q) => $q->where('is_active', true),
            'products as active_products_count' => fn($q) => $q->where('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'store' => $this->formatStore($store),
                'users' => $store->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')),
                        'email' => $user->email,
                        'role' => $user->role?->display_name ?? $user->role?->name ?? 'N/A',
                        'is_active' => (bool) $user->is_active,
                        'created_at' => optional($user->created_at)->toDateTimeString(),
                    ];
                })->values(),
                'products' => $store->products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'sku' => $product->sku,
                        'product_name' => $product->product_name,
                        'is_active' => (bool) $product->is_active,
                        'cost_price' => (float) ($product->cost_price ?? 0),
                        'tax_rate' => (float) ($product->tax_rate ?? 0),
                        'created_at' => optional($product->created_at)->toDateTimeString(),
                    ];
                })->values(),
                'performance' => [
                    'users_total' => (int) $store->users_count,
                    'users_active' => (int) $store->active_users_count,
                    'products_total' => (int) $store->products_count,
                    'products_active' => (int) $store->active_products_count,
                    'age_days' => $this->storeAgeDays($store),
                ],
            ],
        ]);
    }

    private function formatStore(Store $store): array
    {
        $storeName = $this->read($store, ['store_name', 'name']) ?? 'Unnamed Store';
        $contactPerson = $this->read($store, ['contact_person', 'owner_name', 'contact_name']);
        $email = $this->read($store, ['email', 'contact_email']);
        $contactNumber = $this->read($store, ['contact_number', 'phone', 'mobile']);
        $city = $this->read($store, ['city', 'municipality']);
        $address = $this->read($store, ['address', 'street_address']);

        return [
            'id' => $store->id,
            'store_name' => $storeName,
            'email' => $email,
            'contact_person' => $contactPerson,
            'contact_number' => $contactNumber,
            'city' => $city,
            'address' => $address,
            'status' => $store->status,
            'subscription_tier' => $store->subscription_tier ?? 'free',
            'subscription_ends_at' => optional($store->subscription_ends_at)->toDateString(),
            'users_count' => (int) ($store->users_count ?? 0),
            'products_count' => (int) ($store->products_count ?? 0),
            'active_users_count' => (int) ($store->active_users_count ?? 0),
            'active_products_count' => (int) ($store->active_products_count ?? 0),
            'created_at' => optional($store->created_at)->toDateTimeString(),
        ];
    }

    private function searchableColumns(): array
    {
        $preferred = [
            ['store_name', 'name'],
            ['contact_person', 'owner_name', 'contact_name'],
            ['email', 'contact_email'],
            ['city', 'municipality'],
        ];

        $result = [];
        foreach ($preferred as $group) {
            foreach ($group as $candidate) {
                if ($this->hasColumn($candidate)) {
                    $result[] = $candidate;
                    break;
                }
            }
        }

        return $result;
    }

    private function hasColumn(string $column): bool
    {
        if ($this->storeColumns === null) {
            $this->storeColumns = Schema::getColumnListing('stores');
        }

        return in_array($column, $this->storeColumns, true);
    }

    private function read(Store $store, array $candidates): mixed
    {
        foreach ($candidates as $column) {
            if ($this->hasColumn($column)) {
                return $store->getAttribute($column);
            }
        }

        return null;
    }

    private function storeAgeDays(Store $store): int
    {
        if (!$store->created_at) {
            return 0;
        }

        return (int) max(
            0,
            now()->startOfDay()->diffInDays($store->created_at->copy()->startOfDay(), true)
        );
    }
}
