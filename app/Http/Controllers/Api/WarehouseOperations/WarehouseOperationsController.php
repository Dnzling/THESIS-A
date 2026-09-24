<?php

namespace App\Http\Controllers\Api\WarehouseOperations;

use App\Http\Controllers\Controller;
use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\WarehouseLocation;
use App\Models\Inventory\StockTransfer;
use App\Models\Inventory\Warehouse;
use App\Models\ProductCatalog\Category;
use App\Models\Procurement\Receiving\GoodsReceipt;
use App\Support\EmployeeContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseOperationsController extends Controller
{
    private function storeId(Request $request): int
    {
        $user = $request->user();
        $storeId = (int) ($user?->store_id ?: $user?->employee?->store_id);

        abort_if($storeId < 1, 403, 'Your account is not assigned to a store.');

        return $storeId;
    }

    private function stockQuery(Request $request): Builder
    {
        return BranchInventory::query()
            ->where('store_id', $this->storeId($request))
            ->with([
                'branch:id,store_id,name,branch_type',
                'product:id,sku,product_name,product_type,unit_of_measurement,cost_price,category_id',
                'product.category:id,category_name',
                'variation:id,variation_name,variation_sku',
            ]);
    }

    private function employeeName($employee): ?string
    {
        $user = $employee?->user;
        return $user ? trim(($user->fname ?? '').' '.($user->lname ?? '')) : null;
    }

    public function dashboard(Request $request): JsonResponse
    {
        $storeId = $this->storeId($request);
        $stock = BranchInventory::where('store_id', $storeId);

        return response()->json(['success' => true, 'data' => [
            'summary' => [
                'warehouses' => Warehouse::where('store_id', $storeId)->count(),
                'active_warehouses' => Warehouse::where('store_id', $storeId)->active()->count(),
                'total_skus' => (clone $stock)->distinct('product_id')->count('product_id'),
                'quantity_on_hand' => (int) (clone $stock)->sum('quantity_on_hand'),
                'low_stock' => (clone $stock)->where('stock_status', 'low_stock')->count(),
                'out_of_stock' => (clone $stock)->where('stock_status', 'out_of_stock')->count(),
                'pending_transfers' => StockTransfer::where('store_id', $storeId)->pending()->count(),
            ],
            'low_stock_items' => $this->stockQuery($request)->whereIn('stock_status', ['low_stock', 'out_of_stock'])->limit(8)->get(),
            'recent_transfers' => $this->transferQuery($request)->latest()->limit(8)->get()->map(fn ($row) => $this->mapTransfer($row)),
        ]]);
    }

    public function warehouses(Request $request): JsonResponse
    {
        $query = Warehouse::query()->where('store_id', $this->storeId($request))
            ->with('branch:id,store_id,name,branch_type')->withCount('locations');

        $query->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
            ->where('name', 'like', '%'.$request->search.'%')
            ->orWhere('warehouse_code', 'like', '%'.$request->search.'%')
            ->orWhere('city', 'like', '%'.$request->search.'%')));
        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));
        $query->when($request->filled('type'), fn ($q) => $q->where('type', $request->type));
        $query->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')));

        $page = $query->latest()->paginate($request->integer('per_page', 15));
        $page->getCollection()->transform(function (Warehouse $warehouse) {
            $stock = BranchInventory::where('store_id', $warehouse->store_id)
                ->where('branch_id', $warehouse->branch_id)
                ->where('warehouse_section', $warehouse->warehouse_code);
            $warehouse->stock_skus = (clone $stock)->count();
            $warehouse->quantity_on_hand = (int) $stock->sum('quantity_on_hand');
            $warehouse->capacity_utilization = $warehouse->max_capacity_units
                ? round(($warehouse->quantity_on_hand / $warehouse->max_capacity_units) * 100, 2) : 0;
            return $warehouse;
        });

        return response()->json(['success' => true, 'data' => $page]);
    }

    public function warehouse(Request $request, int $id): JsonResponse
    {
        $warehouse = Warehouse::where('store_id', $this->storeId($request))
            ->with(['branch:id,store_id,name,branch_type', 'locations'])->findOrFail($id);

        $query = $this->stockQuery($request)->where('branch_id', $warehouse->branch_id)
            ->where('warehouse_section', $warehouse->warehouse_code);
        $usesFallback = false;
        if (!(clone $query)->exists()) {
            $query = $this->stockQuery($request)->where('branch_id', $warehouse->branch_id)
                ->whereNull('warehouse_section');
            $usesFallback = $query->exists();
        }
        $items = $query->orderByDesc('quantity_on_hand')->get();

        return response()->json(['success' => true, 'data' => [
            'warehouse' => $warehouse,
            'summary' => [
                'total_skus' => $items->count(),
                'quantity_on_hand' => (int) $items->sum('quantity_on_hand'),
                'available' => (int) $items->sum('quantity_available'),
                'reserved' => (int) $items->sum('quantity_reserved'),
                'low_stock' => $items->whereIn('stock_status', ['low_stock', 'out_of_stock'])->count(),
                'capacity_utilization' => $warehouse->max_capacity_units
                    ? round(($items->sum('quantity_on_hand') / $warehouse->max_capacity_units) * 100, 2) : 0,
            ],
            'stock' => $items,
            'uses_branch_fallback' => $usesFallback,
        ]]);
    }

    public function stock(Request $request): JsonResponse
    {
        $query = $this->stockQuery($request)
            ->whereHas('branch', fn ($branchQuery) => $branchQuery
                ->where('store_id', $this->storeId($request))
                ->where('branch_type', 'warehouse'));
        $warehouse = null;
        if ($request->filled('warehouse_id')) {
            $warehouse = Warehouse::where('store_id', $this->storeId($request))->findOrFail($request->integer('warehouse_id'));
            $query->where('branch_id', $warehouse->branch_id)->where('warehouse_section', $warehouse->warehouse_code);
        }
        $query->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')));
        $query->when($request->filled('status'), fn ($q) => $q->where('stock_status', $request->status));
        $query->when($request->filled('product_type'), fn ($q) => $q->whereHas('product', fn ($p) => $p->where('product_type', $request->product_type)));
        $query->when($request->filled('search'), fn ($q) => $q->whereHas('product', fn ($p) => $p
            ->where('product_name', 'like', '%'.$request->search.'%')->orWhere('sku', 'like', '%'.$request->search.'%')));

        $page = $query->latest()->paginate($request->integer('per_page', 15));
        $warehouses = Warehouse::where('store_id', $this->storeId($request))->get()->groupBy('branch_id');
        $page->getCollection()->transform(function ($row) use ($warehouses, $warehouse) {
            $matched = $warehouse ?: ($warehouses[$row->branch_id] ?? collect())->firstWhere('warehouse_code', $row->warehouse_section)
                ?: ($warehouses[$row->branch_id] ?? collect())->firstWhere('type', 'main');
            $row->warehouse = $matched ? $matched->only(['id', 'warehouse_code', 'name', 'type']) : null;
            // Product hides cost_price globally. Expose it only for this protected
            // warehouse valuation response; never substitute the selling price.
            $row->product?->makeVisible('cost_price');
            $unitCost = (float) ($row->product?->cost_price ?? 0);
            $row->stock_value = (float) $row->quantity_on_hand * $unitCost;
            return $row;
        });

        return response()->json(['success' => true, 'data' => $page]);
    }

    public function stockItem(Request $request, int $id): JsonResponse
    {
        $storeId = $this->storeId($request);
        $row = BranchInventory::query()
            ->where('store_id', $storeId)
            ->whereHas('branch', fn ($query) => $query
                ->where('store_id', $storeId)
                ->where('branch_type', 'warehouse'))
            ->with([
                'branch:id,store_id,name,branch_code,branch_type,address,city,province,status',
                'product.category', 'product.subcategory', 'product.suppliers', 'product.assets',
                'variation', 'lastCountedBy.user:id,fname,lname', 'warehouseLocation.warehouse',
            ])
            ->findOrFail($id);

        $warehouses = Warehouse::where('store_id', $storeId)
            ->where('branch_id', $row->branch_id)->get();
        $warehouse = $warehouses->firstWhere('warehouse_code', $row->warehouse_section)
            ?: $warehouses->firstWhere('type', 'main')
            ?: $warehouses->first();

        $row->product?->makeVisible('cost_price');
        $unitCost = (float) ($row->product?->cost_price ?? 0);
        $row->setAttribute('warehouse', $warehouse?->only([
            'id', 'warehouse_code', 'name', 'type', 'status', 'manager_name', 'manager_phone',
        ]));
        $row->setAttribute('unit_cost', $unitCost);
        $row->setAttribute('stock_value', (float) $row->quantity_on_hand * $unitCost);

        return response()->json(['success' => true, 'data' => $row]);
    }

    public function stockItemOptions(Request $request): JsonResponse
    {
        $storeId = $this->storeId($request);
        return response()->json(['success' => true, 'data' => [
            'categories' => Category::where('store_id', $storeId)->orderBy('category_name')->get(['id', 'category_name']),
            'warehouses' => Warehouse::where('store_id', $storeId)->active()->orderBy('name')->get(['id', 'branch_id', 'warehouse_code', 'name']),
        ]]);
    }

    public function updateStockItem(Request $request, int $id): JsonResponse
    {
        $storeId = $this->storeId($request);
        $stock = BranchInventory::where('store_id', $storeId)
            ->whereHas('branch', fn ($query) => $query->where('branch_type', 'warehouse'))
            ->with('product')->findOrFail($id);

        $validated = $request->validate([
            'product_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'product_type' => ['required', 'string', 'max:50'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'unit_of_measurement' => ['required', 'string', 'max:50'],
            'brand' => ['nullable', 'string', 'max:100'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'length_cm' => ['nullable', 'numeric', 'min:0'],
            'width_cm' => ['nullable', 'numeric', 'min:0'],
            'height_cm' => ['nullable', 'numeric', 'min:0'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'quantity_on_hand' => ['required', 'integer', 'min:0'],
            'quantity_reserved' => ['required', 'integer', 'min:0'],
            'quantity_damaged' => ['required', 'integer', 'min:0'],
            'quantity_incoming' => ['required', 'integer', 'min:0'],
            'warehouse_section' => ['nullable', 'string', 'max:50'],
            'warehouse_location_id' => ['nullable', 'integer', 'exists:warehouse_locations,id'],
            'aisle' => ['nullable', 'string', 'max:50'],
            'rack' => ['nullable', 'string', 'max:50'],
            'shelf' => ['nullable', 'string', 'max:50'],
            'bin_code' => ['nullable', 'string', 'max:100'],
            'reorder_point' => ['required', 'integer', 'min:0'],
            'reorder_quantity' => ['required', 'integer', 'min:0'],
            'maximum_stock' => ['required', 'integer', 'min:0'],
            'safety_stock' => ['required', 'integer', 'min:0'],
        ]);

        $categoryBelongsToStore = !$validated['category_id'] || Category::where('store_id', $storeId)->whereKey($validated['category_id'])->exists();
        if (!$categoryBelongsToStore) {
            return response()->json(['success' => false, 'message' => 'The selected category does not belong to this store.'], 422);
        }
        if ((int) $validated['quantity_reserved'] > (int) $validated['quantity_on_hand']) {
            return response()->json(['success' => false, 'message' => 'Reserved quantity cannot exceed quantity on hand.'], 422);
        }

        if (!empty($validated['warehouse_location_id'])) {
            $location = WarehouseLocation::with('warehouse')
                ->find($validated['warehouse_location_id']);
            if (!$location || !$location->warehouse || (int) $location->warehouse->branch_id !== (int) $stock->branch_id) {
                return response()->json(['success' => false, 'message' => 'The selected location does not belong to this warehouse branch.'], 422);
            }

            // Keep the legacy text fields synchronized for existing stock screens.
            $validated['warehouse_section'] = $location->warehouse->warehouse_code;
            $validated['aisle'] = $location->aisle;
            $validated['rack'] = $location->rack;
            $validated['shelf'] = $location->shelf;
            $validated['bin_code'] = $location->bin;
        }

        DB::transaction(function () use ($stock, $validated) {
            $stock->product->update(collect($validated)->only([
                'product_name', 'description', 'product_type', 'category_id', 'unit_of_measurement',
                'brand', 'cost_price', 'length_cm', 'width_cm', 'height_cm', 'weight_kg', 'is_active',
            ])->all());

            $onHand = (int) $validated['quantity_on_hand'];
            $reserved = (int) $validated['quantity_reserved'];
            $reorderPoint = (int) $validated['reorder_point'];
            $available = max(0, $onHand - $reserved);
            $status = $available <= 0 ? 'out_of_stock' : ($available <= $reorderPoint ? 'low_stock' : 'in_stock');
            $stock->update([
                ...collect($validated)->only([
                    'quantity_on_hand', 'quantity_reserved', 'quantity_damaged', 'quantity_incoming',
                    'warehouse_section', 'warehouse_location_id', 'aisle', 'rack', 'shelf', 'bin_code', 'reorder_point',
                    'reorder_quantity', 'maximum_stock', 'safety_stock',
                ])->all(),
                'quantity_available' => $available,
                'stock_status' => $status,
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Warehouse stock record updated successfully.']);
    }

    private function transferQuery(Request $request): Builder
    {
        return StockTransfer::query()->where('store_id', $this->storeId($request))->with([
            'fromBranch:id,store_id,name,branch_type', 'toBranch:id,store_id,name,branch_type',
            'requestedBy.user:id,fname,lname', 'items.product:id,sku,product_name,unit_of_measurement',
        ]);
    }

    private function mapTransfer(StockTransfer $row): StockTransfer
    {
        $row->requested_by_name = $this->employeeName($row->requestedBy);
        $row->total_items = $row->items->count();
        $row->total_quantity = (int) $row->items->sum('requested_quantity');
        return $row;
    }

    public function transferRequests(Request $request): JsonResponse
    {
        $query = $this->transferQuery($request)->pending();
        $query->when($request->filled('search'), fn ($q) => $q->where('transfer_number', 'like', '%'.$request->search.'%'));
        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));
        $page = $query->latest()->paginate($request->integer('per_page', 15));
        $page->getCollection()->transform(fn ($row) => $this->mapTransfer($row));
        return response()->json(['success' => true, 'data' => $page]);
    }

    public function transferRequest(Request $request, int $id): JsonResponse
    {
        $transfer = $this->transferDetailQuery($request)->findOrFail($id);
        $this->mapTransfer($transfer);

        return response()->json(['success' => true, 'data' => $transfer]);
    }

    public function approveTransferRequest(Request $request, int $id): JsonResponse
    {
        $this->authorizeTransferAction($request, 'warehouse.transfers.approve');
        $transfer = $this->transferDetailQuery($request)->findOrFail($id);
        abort_unless(in_array($transfer->status, ['requested', 'pending_approval'], true), 422, 'Only requested transfers can be approved.');

        DB::transaction(function () use ($transfer): void {
            foreach ($transfer->items as $item) {
                $stock = BranchInventory::query()
                    ->where('store_id', $transfer->store_id)
                    ->where('branch_id', $transfer->from_branch_id)
                    ->where('product_id', $item->product_id)
                    ->where('variation_id', $item->variation_id)
                    ->lockForUpdate()
                    ->first();

                abort_unless($stock && $stock->quantity_available >= $item->requested_quantity, 422, "Insufficient source stock for {$item->product?->product_name}.");
                $item->update(['approved_quantity' => $item->requested_quantity]);
            }

            $transfer->update([
                'status' => 'sender_approved',
                'sender_approved_by' => EmployeeContext::currentEmployeeId(),
                'sender_approved_date' => now(),
                'rejection_reason' => null,
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Transfer request approved successfully.']);
    }

    public function rejectTransferRequest(Request $request, int $id): JsonResponse
    {
        $this->authorizeTransferAction($request, 'warehouse.transfers.reject');
        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'min:3', 'max:1000']]);
        $transfer = $this->transferDetailQuery($request)->findOrFail($id);
        abort_unless(in_array($transfer->status, ['requested', 'pending_approval'], true), 422, 'Only requested transfers can be rejected.');

        $transfer->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return response()->json(['success' => true, 'message' => 'Transfer request rejected.']);
    }

    private function transferDetailQuery(Request $request): Builder
    {
        return StockTransfer::query()->where('store_id', $this->storeId($request))->with([
            'fromBranch', 'toBranch', 'requestedBy.user', 'senderApprovedBy.user',
            'receiverAcknowledgedBy.user', 'financeApprovedBy.user', 'items.product.category',
            'items.variation',
        ]);
    }

    private function authorizeTransferAction(Request $request, string $permission): void
    {
        $user = $request->user();
        abort_unless($user && $user->hasPermissionTo($permission, $this->storeId($request)), 403, 'You do not have permission to perform this warehouse transfer action.');
    }

    public function transferHistory(Request $request): JsonResponse
    {
        $query = $this->transferQuery($request)->whereIn('status', ['received', 'cancelled', 'rejected']);
        $query->when($request->filled('search'), fn ($q) => $q->where('transfer_number', 'like', '%'.$request->search.'%'));
        $page = $query->latest()->paginate($request->integer('per_page', 15));
        $page->getCollection()->transform(fn ($row) => $this->mapTransfer($row));
        return response()->json(['success' => true, 'data' => $page]);
    }

    public function receiving(Request $request): JsonResponse
    {
        $storeId = $this->storeId($request);
        $query = GoodsReceipt::query()->whereHas('branch', fn ($q) => $q->where('store_id', $storeId))->with([
            'branch:id,store_id,name,branch_type', 'purchaseOrder:id,po_number,supplier_id',
            'purchaseOrder.supplier:id,supplier_name', 'receivedBy.user:id,fname,lname', 'items',
        ]);
        $query->when($request->filled('search'), fn ($q) => $q->where('grn_number', 'like', '%'.$request->search.'%'));
        $query->when($request->filled('status'), fn ($q) => $q->where('receipt_status', $request->status));
        $page = $query->latest('receipt_date')->paginate($request->integer('per_page', 15));
        $page->getCollection()->transform(function ($row) {
            $row->received_by_name = $this->employeeName($row->receivedBy);
            $row->total_items = $row->items->count();
            $row->total_quantity = (int) $row->items->sum('quantity_received');
            return $row;
        });
        return response()->json(['success' => true, 'data' => $page]);
    }
}
