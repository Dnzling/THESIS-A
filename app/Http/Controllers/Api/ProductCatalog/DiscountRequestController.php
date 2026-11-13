<?php

namespace App\Http\Controllers\Api\ProductCatalog;

use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\InventoryTransaction;
use App\Services\Core\PermissionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DiscountRequestController extends BaseController
{
    public function index(Request $request, PermissionService $permissions): JsonResponse
    {
        $this->authorizePermission($permissions, 'merchandising.pricing.view');
        $user = $request->user();
        $assignedBranch = (int) ($user?->branch_id ?: DB::table('employees')->where('user_id', $user?->id)->value('branch_id'));
        $query = DB::table('merchandising_discount_requests as r')
            ->join('products as p', 'p.id', '=', 'r.product_id')
            ->join('branches as b', 'b.id', '=', 'r.branch_id')
            ->leftJoin('product_variations as v', 'v.id', '=', 'r.variation_id')
            ->leftJoin('users as u', 'u.id', '=', 'r.requested_by')
            ->where('r.store_id', (int) $this->getStoreId())
            ->when($assignedBranch > 0, fn ($q) => $q->where('r.branch_id', $assignedBranch))
            ->select([
                'r.id', 'r.discount_type', 'r.discount_value', 'r.report_period_start', 'r.report_period_end',
                'r.units_sold', 'r.available_stock', 'r.days_cover', 'r.days_without_sale', 'r.days_listed',
                'r.stock_value', 'r.reason', 'r.status', 'r.created_at', 'p.product_name', 'p.sku',
                'b.name as branch_name', 'v.variation_name', 'u.fname as requester_fname', 'u.lname as requester_lname',
            ]);

        if ($request->filled('search')) {
            $term = trim((string) $request->query('search'));
            $query->where(fn ($q) => $q->where('p.product_name', 'like', "%{$term}%")
                ->orWhere('p.sku', 'like', "%{$term}%")
                ->orWhere('b.name', 'like', "%{$term}%"));
        }
        if ($request->filled('status') && in_array($request->query('status'), ['pending'], true)) {
            $query->where('r.status', $request->query('status'));
        }

        $rows = $query->orderByDesc('r.created_at')->limit(200)->get()->map(fn ($row) => [
            'id' => (int) $row->id,
            'product_name' => $row->product_name,
            'sku' => $row->sku,
            'variation_name' => $row->variation_name,
            'branch_name' => $row->branch_name,
            'discount_type' => $row->discount_type,
            'discount_value' => (float) $row->discount_value,
            'report_period_start' => $row->report_period_start,
            'report_period_end' => $row->report_period_end,
            'units_sold' => (int) $row->units_sold,
            'available_stock' => (int) $row->available_stock,
            'days_cover' => $row->days_cover === null ? null : (int) $row->days_cover,
            'days_without_sale' => $row->days_without_sale === null ? null : (int) $row->days_without_sale,
            'days_listed' => (int) $row->days_listed,
            'stock_value' => (float) $row->stock_value,
            'reason' => $row->reason,
            'status' => $row->status,
            'requested_by' => trim(($row->requester_fname ?? '') . ' ' . ($row->requester_lname ?? '')) ?: 'Former user',
            'created_at' => $row->created_at,
        ]);

        return $this->successResponse(['requests' => $rows]);
    }

    public function store(Request $request, PermissionService $permissions): JsonResponse
    {
        $this->authorizePermission($permissions, 'inventory.reports.view');

        $validated = $request->validate([
            'branch_inventory_id' => ['required', 'integer'],
            'report_period_start' => ['required', 'date', 'before_or_equal:today'],
            'report_period_end' => ['required', 'date', 'after_or_equal:report_period_start', 'before_or_equal:today'],
            'discount_type' => ['required', Rule::in(['percentage', 'fixed'])],
            'discount_value' => [
                'required', 'numeric', 'gt:0',
                Rule::when($request->input('discount_type') === 'percentage', ['max:100']),
            ],
            'reason' => ['required', 'string', 'min:10', 'max:1500'],
        ]);

        $storeId = (int) $this->getStoreId();
        $inventory = BranchInventory::query()->with(['product', 'variation', 'branch'])
            ->where('store_id', $storeId)->find($validated['branch_inventory_id']);
        if (!$inventory || !$inventory->product || $inventory->product->product_type !== 'finished_good'
            || (int) $inventory->branch?->store_id !== $storeId) {
            throw ValidationException::withMessages(['branch_inventory_id' => 'The selected report item is not available in this store.']);
        }
        $assignedBranch = (int) ($request->user()->branch_id
            ?: DB::table('employees')->where('user_id', $request->user()->id)->value('branch_id'));
        abort_if($assignedBranch > 0 && $assignedBranch !== (int) $inventory->branch_id, 403,
            'You cannot submit a discount request for another branch.');

        $start = Carbon::parse($validated['report_period_start'])->startOfDay();
        $end = Carbon::parse($validated['report_period_end'])->endOfDay();
        abort_if($start->diffInDays($end->copy()->startOfDay()) + 1 > 366, 422, 'The report period cannot exceed one year.');

        $sales = InventoryTransaction::query()
            ->where('store_id', $storeId)
            ->where('branch_id', $inventory->branch_id)
            ->where('product_id', $inventory->product_id)
            ->where('transaction_type', 'sale')
            ->whereBetween('transaction_date', [$start, $end]);
        $inventory->variation_id === null
            ? $sales->whereNull('variation_id')
            : $sales->where('variation_id', $inventory->variation_id);
        $unitsSold = (int) (clone $sales)->sum(DB::raw('ABS(quantity_change)'));
        $dailySales = $unitsSold / max(1, $start->diffInDays($end->copy()->startOfDay()) + 1);
        $daysCover = $dailySales > 0 ? (int) floor((int) $inventory->quantity_available / $dailySales) : null;
        $lastSale = InventoryTransaction::query()
            ->where('store_id', $storeId)
            ->where('branch_id', $inventory->branch_id)
            ->where('product_id', $inventory->product_id)
            ->where('transaction_type', 'sale')
            ->where('transaction_date', '<=', $end);
        $inventory->variation_id === null
            ? $lastSale->whereNull('variation_id')
            : $lastSale->where('variation_id', $inventory->variation_id);
        $lastSaleDate = $lastSale->max('transaction_date');
        $daysWithoutSale = $lastSaleDate ? (int) Carbon::parse($lastSaleDate)->startOfDay()->diffInDays($end->copy()->startOfDay()) : null;
        $daysListed = $inventory->created_at && $inventory->created_at->lessThanOrEqualTo($end)
            ? (int) $inventory->created_at->startOfDay()->diffInDays($end->copy()->startOfDay()) : 0;
        if ((int) $inventory->quantity_available <= 0
            || !(($unitsSold === 0 && $daysListed >= 30) || ($daysCover !== null && $daysCover > 60))) {
            throw ValidationException::withMessages([
                'branch_inventory_id' => 'This product is no longer classified as a slow mover for the selected report period.',
            ]);
        }
        $unitCost = (float) ($inventory->variation?->cost_price ?? $inventory->product->getRawOriginal('cost_price') ?? 0);

        $id = DB::table('merchandising_discount_requests')->insertGetId([
            'store_id' => $storeId,
            'branch_id' => $inventory->branch_id,
            'product_id' => $inventory->product_id,
            'variation_id' => $inventory->variation_id,
            'branch_inventory_id' => $inventory->id,
            'requested_by' => $request->user()->id,
            'discount_type' => $validated['discount_type'],
            'discount_value' => $validated['discount_value'],
            'report_period_start' => $start->toDateString(),
            'report_period_end' => $end->toDateString(),
            'units_sold' => $unitsSold,
            'available_stock' => (int) $inventory->quantity_available,
            'days_cover' => $daysCover,
            'days_without_sale' => $daysWithoutSale,
            'days_listed' => $daysListed,
            'stock_value' => round((int) $inventory->quantity_available * $unitCost, 2),
            'reason' => trim($validated['reason']),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->successResponse(['id' => $id], 'Discount request submitted for merchandising review.', 201);
    }

    private function authorizePermission(PermissionService $permissions, string $permission): void
    {
        $user = request()->user();
        abort_unless($user && $permissions->userHasPermission($user, $permission, (int) $this->getStoreId()), 403,
            'You do not have permission to access discount requests.');
    }
}
