<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StoreManagementController extends Controller
{
    private ?array $storeColumns = null;

    public function usersIndex(Request $request): JsonResponse
    {
        if (!auth()->user()?->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 20)));
        $search = trim((string) $request->input('search', ''));
        $isActive = $request->input('is_active', null);

        $query = DB::table('users')
            ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
            ->leftJoin('stores', 'stores.id', '=', 'users.store_id')
            ->selectRaw("
                users.id,
                users.fname,
                users.lname,
                users.email,
                users.is_active,
                users.created_at,
                COALESCE(roles.display_name, roles.name, 'N/A') as display_role,
                COALESCE(stores.name, 'No Store') as store_name
            ");

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('users.fname', 'like', "%{$search}%")
                    ->orWhere('users.lname', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%");
            });
        }

        if ($isActive !== null && $isActive !== '') {
            $activeFlag = filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($activeFlag !== null) {
                $query->where('users.is_active', $activeFlag ? 1 : 0);
            }
        }

        $users = $query
            ->orderByDesc('users.created_at')
            ->paginate($perPage);

        $users->getCollection()->transform(function ($row) {
            return [
                'id' => (int) $row->id,
                'full_name' => trim(($row->fname ?? '') . ' ' . ($row->lname ?? '')),
                'email' => $row->email,
                'display_role' => $row->display_role,
                'store' => ['name' => $row->store_name ?: 'No Store'],
                'is_active' => (bool) ($row->is_active ?? false),
                'created_at' => $row->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

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

        $storeIds = $stores->getCollection()->pluck('id')->map(fn($id) => (int) $id)->values()->all();
        $customersByStore = $this->customerCountsByStore($storeIds);

        $stores->getCollection()->transform(
            fn(Store $store) => $this->formatStore($store, (int) ($customersByStore[$store->id] ?? 0))
        );

        return response()->json([
            'success' => true,
            'data' => $stores,
        ]);
    }

    public function show(string $store): JsonResponse
    {
        if (!auth()->user()?->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $store = Store::withTrashed()->findOrFail((int) $store);

        $store->load([
            'products:id,store_id,sku,product_name,is_active,cost_price,created_at',
        ]);

        $store->loadCount([
            'users',
            'products',
            'users as active_users_count' => fn($q) => $q->where('is_active', true),
            'products as active_products_count' => fn($q) => $q->where('is_active', true),
        ]);

        $users = $this->usersForStore((int) $store->id);

        return response()->json([
            'success' => true,
            'data' => [
                'store' => $this->formatStore($store, $this->customerCountForStore((int) $store->id)),
                'users' => $users,
                'products' => $store->products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'sku' => $product->sku,
                        'product_name' => $product->product_name,
                        'is_active' => (bool) $product->is_active,
                        'cost_price' => (float) ($product->cost_price ?? 0),
                        'created_at' => optional($product->created_at)->toDateTimeString(),
                    ];
                })->values(),
                'customers' => $this->customersForStore((int) $store->id),
                'performance' => [
                    'users_total' => count($users),
                    'users_active' => count(array_filter($users, fn($u) => (bool) ($u['is_active'] ?? false))),
                    'products_total' => (int) $store->products_count,
                    'products_active' => (int) $store->active_products_count,
                    'customers_total' => $this->customerCountForStore((int) $store->id),
                    'age_days' => $this->storeAgeDays($store),
                ],
            ],
        ]);
    }

    public function deactivate(Request $request, string $store): JsonResponse
    {
        if (!auth()->user()?->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $storeModel = Store::withTrashed()->findOrFail((int) $store);
        $storeModel->update([
            'status' => 'inactive',
            'deactivation_reason' => trim((string) $validated['reason']),
            'deactivated_at' => now(),
            'deactivated_by' => $request->user()?->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Store deactivated successfully.',
            'data' => [
                'store' => $this->formatStore($storeModel->fresh(['users', 'products']), $this->customerCountForStore((int) $storeModel->id)),
            ],
        ]);
    }

    public function destroy(Request $request, string $store): JsonResponse
    {
        if (!auth()->user()?->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $storeModel = Store::withTrashed()->findOrFail((int) $store);
        $storeModel->update([
            'status' => 'inactive',
            'deactivation_reason' => trim((string) $validated['reason']),
            'deactivated_at' => now(),
            'deactivated_by' => $request->user()?->id,
        ]);
        $storeModel->delete();

        return response()->json([
            'success' => true,
            'message' => 'Store deleted successfully.',
        ]);
    }

    private function formatStore(Store $store, int $customersCount = 0): array
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
            'customers_count' => $customersCount,
            'active_users_count' => (int) ($store->active_users_count ?? 0),
            'active_products_count' => (int) ($store->active_products_count ?? 0),
            'created_at' => optional($store->created_at)->toDateTimeString(),
        ];
    }

    private function customerCountsByStore(array $storeIds): array
    {
        if (empty($storeIds) || !Schema::hasTable('ecommerce_orders')) {
            return [];
        }

        return DB::table('ecommerce_orders')
            ->whereIn('store_id', $storeIds)
            ->whereNotNull('user_id')
            ->selectRaw('store_id, COUNT(DISTINCT user_id) as customers_count')
            ->groupBy('store_id')
            ->pluck('customers_count', 'store_id')
            ->map(fn($count) => (int) $count)
            ->toArray();
    }

    private function customerCountForStore(int $storeId): int
    {
        if ($storeId <= 0 || !Schema::hasTable('ecommerce_orders')) {
            return 0;
        }

        return (int) DB::table('ecommerce_orders')
            ->where('store_id', $storeId)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');
    }

    private function customersForStore(int $storeId): array
    {
        if ($storeId <= 0 || !Schema::hasTable('ecommerce_orders')) {
            return [];
        }

        $rows = DB::table('ecommerce_orders')
            ->join('users', 'users.id', '=', 'ecommerce_orders.user_id')
            ->leftJoin('customers', 'customers.user_id', '=', 'users.id')
            ->where('ecommerce_orders.store_id', $storeId)
            ->whereNotNull('ecommerce_orders.user_id')
            ->groupBy(
                'users.id',
                'users.fname',
                'users.lname',
                'users.email',
                'customers.verification_status'
            )
            ->orderByDesc(DB::raw('MAX(ecommerce_orders.created_at)'))
            ->selectRaw("
                users.id,
                users.fname,
                users.lname,
                users.email,
                COALESCE(customers.verification_status, 'unverified') as verification_status,
                COUNT(ecommerce_orders.id) as orders_count,
                MAX(ecommerce_orders.created_at) as last_order_at
            ")
            ->get();

        return $rows->map(function ($row) {
            return [
                'id' => (int) $row->id,
                'name' => trim(($row->fname ?? '') . ' ' . ($row->lname ?? '')),
                'email' => $row->email,
                'verification_status' => $row->verification_status,
                'orders_count' => (int) ($row->orders_count ?? 0),
                'last_order_at' => $row->last_order_at,
            ];
        })->values()->all();
    }

    private function usersForStore(int $storeId): array
    {
        if ($storeId <= 0 || !Schema::hasTable('users')) {
            return [];
        }

        $usersByStoreId = DB::table('users')
            ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
            ->where('users.store_id', $storeId)
            ->selectRaw("
                users.id,
                users.fname,
                users.lname,
                users.email,
                users.is_active,
                users.created_at,
                COALESCE(roles.display_name, roles.name, 'N/A') as role_name
            ")
            ->get();

        $usersByEmployeeStore = collect();
        if (Schema::hasTable('employees')) {
            $usersByEmployeeStore = DB::table('employees')
                ->join('users', 'users.id', '=', 'employees.user_id')
                ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
                ->where('employees.store_id', $storeId)
                ->selectRaw("
                    users.id,
                    users.fname,
                    users.lname,
                    users.email,
                    users.is_active,
                    users.created_at,
                    COALESCE(roles.display_name, roles.name, 'N/A') as role_name
                ")
                ->get();
        }

        return $usersByStoreId
            ->concat($usersByEmployeeStore)
            ->unique('id')
            ->sortByDesc('created_at')
            ->values()
            ->map(function ($user) {
                return [
                    'id' => (int) $user->id,
                    'name' => trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')),
                    'email' => $user->email,
                    'role' => $user->role_name ?? 'N/A',
                    'is_active' => (bool) ($user->is_active ?? false),
                    'created_at' => $user->created_at,
                ];
            })
            ->all();
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
