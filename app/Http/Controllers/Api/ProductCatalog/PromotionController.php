<?php

namespace App\Http\Controllers\Api\ProductCatalog;

use App\Models\Core\NavigationItem;
use App\Models\Core\Permission;
use App\Models\ProductCatalog\Category;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\Promotion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\Core\PermissionService;

class PromotionController extends BaseController
{
    public function index(Request $request)
    {
        $this->authorizeRead();
        $storeId = (int) $this->getStoreId();
        $now = now();
        $query = Promotion::byStore($storeId)->with(['categories:id,category_name', 'products:id,product_name,sku']);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(fn (Builder $q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }

        if ($request->filled('status')) {
            match ($request->input('status')) {
                'active' => $query->where('is_active', true)
                    ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
                    ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now)),
                'scheduled' => $query->where('is_active', true)->where('starts_at', '>', $now),
                'expired' => $query->whereNotNull('ends_at')->where('ends_at', '<', $now),
                'inactive' => $query->where('is_active', false),
                default => null,
            };
        }

        match ($request->input('sort_by', 'priority_desc')) {
            'priority_asc' => $query->orderBy('priority'),
            'oldest' => $query->oldest(),
            'newest' => $query->latest(),
            default => $query->orderByDesc('priority')->latest(),
        };

        $promotions = $query->get()->map(fn (Promotion $promotion) => $this->present($promotion));
        $all = Promotion::byStore($storeId)->get();

        return $this->successResponse([
            'promotions' => $promotions,
            'stats' => [
                'total' => $all->count(),
                'active' => $all->filter(fn ($p) => $this->status($p) === 'active')->count(),
                'scheduled' => $all->filter(fn ($p) => $this->status($p) === 'scheduled')->count(),
                'inactive' => $all->filter(fn ($p) => $this->status($p) === 'inactive')->count(),
            ],
        ], 'Promotions retrieved successfully');
    }

    public function options()
    {
        $this->authorizeRead();
        $storeId = (int) $this->getStoreId();

        return $this->successResponse([
            'categories' => Category::byStore($storeId)->active()
                ->orderBy('category_name')->get(['id', 'category_name', 'parent_category_id']),
            'products' => Product::byStore($storeId)->where('is_active', true)
                ->where('product_type', 'finished_good')->orderBy('product_name')
                ->get(['id', 'product_name', 'sku', 'base_price', 'discounted_price', 'category_id', 'subcategory_id']),
        ], 'Promotion options retrieved successfully');
    }

    public function store(Request $request)
    {
        $this->authorizeWrite();
        return $this->persist($request, new Promotion());
    }

    public function update(Request $request, int $id)
    {
        $this->authorizeWrite();
        $promotion = Promotion::byStore((int) $this->getStoreId())->findOrFail($id);
        return $this->persist($request, $promotion);
    }

    public function toggle(int $id)
    {
        $this->authorizeWrite();
        $promotion = Promotion::byStore((int) $this->getStoreId())->findOrFail($id);
        $promotion->update(['is_active' => !$promotion->is_active]);

        return $this->successResponse($this->present($promotion->fresh(['categories', 'products'])), 'Promotion status updated');
    }

    public function duplicate(int $id)
    {
        $this->authorizeWrite();
        $source = Promotion::byStore((int) $this->getStoreId())->with(['categories', 'products'])->findOrFail($id);
        $copy = DB::transaction(function () use ($source) {
            $copy = $source->replicate();
            $copy->name = $source->name . ' (Copy)';
            $copy->is_active = false;
            $copy->save();
            $copy->categories()->sync($source->categories->modelKeys());
            $copy->products()->sync($source->products->modelKeys());
            return $copy;
        });

        return $this->successResponse($this->present($copy->load(['categories', 'products'])), 'Promotion duplicated', 201);
    }

    public function destroy(int $id)
    {
        $this->authorizeWrite();
        $promotion = Promotion::byStore((int) $this->getStoreId())->findOrFail($id);
        $promotion->delete();
        return $this->successResponse(null, 'Promotion deleted successfully');
    }

    private function persist(Request $request, Promotion $promotion)
    {
        $storeId = (int) $this->getStoreId();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'discount_type' => ['required', Rule::in(['percentage', 'fixed'])],
            'discount_value' => ['required', 'numeric', 'gt:0', $request->input('discount_type') === 'percentage' ? 'max:100' : 'max:99999999.99'],
            'target_type' => ['required', Rule::in(['all', 'categories', 'products'])],
            'category_ids' => ['array', Rule::requiredIf($request->input('target_type') === 'categories'), 'min:1'],
            'category_ids.*' => ['integer'],
            'product_ids' => ['array', Rule::requiredIf($request->input('target_type') === 'products'), 'min:1'],
            'product_ids.*' => ['integer'],
            'minimum_order_amount' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', Rule::when($request->filled('starts_at'), ['after:starts_at'])],
            'priority' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['required', 'boolean'],
        ]);

        $categoryIds = Category::byStore($storeId)->whereIn('id', $data['category_ids'] ?? [])->pluck('id')->all();
        $productIds = Product::byStore($storeId)->whereIn('id', $data['product_ids'] ?? [])->pluck('id')->all();
        if (($data['target_type'] === 'categories' && count($categoryIds) !== count($data['category_ids']))
            || ($data['target_type'] === 'products' && count($productIds) !== count($data['product_ids']))) {
            return $this->errorResponse('One or more selected targets do not belong to this store.', 422);
        }

        DB::transaction(function () use ($promotion, $data, $storeId, $categoryIds, $productIds) {
            $promotion->fill([
                ...$data,
                'store_id' => $storeId,
                'minimum_order_amount' => $data['minimum_order_amount'] ?? 0,
                'priority' => $data['priority'] ?? 0,
            ])->save();
            $promotion->categories()->sync($data['target_type'] === 'categories' ? $categoryIds : []);
            $promotion->products()->sync($data['target_type'] === 'products' ? $productIds : []);
        });

        return $this->successResponse(
            $this->present($promotion->fresh(['categories', 'products'])),
            $promotion->wasRecentlyCreated ? 'Promotion created successfully' : 'Promotion updated successfully',
            $promotion->wasRecentlyCreated ? 201 : 200
        );
    }

    private function present(Promotion $promotion): array
    {
        $affected = match ($promotion->target_type) {
            'categories' => Product::byStore((int) $promotion->store_id)
                ->where(fn ($q) => $q->whereIn('category_id', $promotion->categories->modelKeys())
                    ->orWhereIn('subcategory_id', $promotion->categories->modelKeys()))->count(),
            'products' => $promotion->products->count(),
            default => Product::byStore((int) $promotion->store_id)->where('product_type', 'finished_good')->count(),
        };

        return [
            ...$promotion->toArray(),
            'category_ids' => $promotion->categories->modelKeys(),
            'product_ids' => $promotion->products->modelKeys(),
            'target_label' => match ($promotion->target_type) {
                'categories' => $promotion->categories->pluck('category_name')->join(', '),
                'products' => $promotion->products->pluck('product_name')->join(', '),
                default => 'All products',
            },
            'affected_products_count' => $affected,
            'status' => $this->status($promotion),
        ];
    }

    private function status(Promotion $promotion): string
    {
        if (!$promotion->is_active) return 'inactive';
        if ($promotion->starts_at?->isFuture()) return 'scheduled';
        if ($promotion->ends_at?->isPast()) return 'expired';
        return 'active';
    }

    private function authorizeRead(): void
    {
        abort_unless($this->hasPromotionPermission(false), 403, 'You do not have permission to view promotions.');
    }

    private function authorizeWrite(): void
    {
        abort_unless($this->canManagePromotions(), 403, 'You do not have permission to manage promotions.');
    }

    private function canManagePromotions(): bool
    {
        return $this->hasPromotionPermission(true);
    }

    private function hasPromotionPermission(bool $write): bool
    {
        $navigation = NavigationItem::query()
            ->where('route_name', 'merchandising.pricing')
            ->where('is_active', true)
            ->with(['permissions' => fn ($query) => $query->where('permissions.is_active', true)])
            ->first();

        if (!$navigation) {
            return false;
        }

        $permissionNames = $navigation->permissions->pluck('name');

        if ($write) {
            $managementSuffixes = ['manage', 'edit', 'create', 'update', 'delete'];
            $permissionNames = Permission::query()
                ->where('module', $navigation->module)
                ->where('name', 'like', $navigation->name . '.%')
                ->where('is_active', true)
                ->get(['name'])
                ->pluck('name')
                ->filter(fn (string $name) => collect($managementSuffixes)
                    ->contains(fn (string $suffix) => str_ends_with($name, '.' . $suffix)));
        }

        $user = auth()->user();
        if (!$user || $permissionNames->isEmpty()) {
            return false;
        }

        $effectivePermissions = app(PermissionService::class)->getUserPermissions(
            $user,
            (int) $this->getStoreId()
        );

        return $permissionNames->intersect($effectivePermissions)->isNotEmpty();
    }
}
