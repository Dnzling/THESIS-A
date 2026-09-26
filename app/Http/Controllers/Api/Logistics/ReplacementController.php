<?php

namespace App\Http\Controllers\Api\Logistics;

use App\Http\Controllers\Controller;
use App\Models\CRM\EcommerceOrderReturn;
use App\Models\Core\User;
use App\Models\Ecommerce\EcommerceDeliveryVehicle;
use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Store\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReplacementController extends Controller
{
    private function scopedReturn(Request $request, int $id): EcommerceOrderReturn
    {
        $storeId = (int) ($request->user()?->store_id ?? 0);
        abort_if($storeId <= 0, 403, 'A store is required.');

        return EcommerceOrderReturn::query()
            ->where('store_id', $storeId)
            ->where('return_type', 'replacement')
            ->with(['order', 'orderItem.branchInventory', 'pickup.destinationBranch', 'replacementBranch'])
            ->findOrFail($id);
    }

    private function authorizeStock(Request $request): void
    {
        abort_unless($request->user()->hasAnyPermission([
            'inventory.receiving.manage', 'inventory.stock.manage', 'warehouse.receiving.view', 'crm.returns.manage',
        ]) || $request->user()->hasRole('store_admin'), 403);
    }

    private function authorizeLogistics(Request $request, ?int $driverId = null): void
    {
        $allowed = $request->user()->hasPermissionTo('logistics.deliveries.manage')
            || $request->user()->hasRole('store_admin')
            || ($driverId && (int) $request->user()->id === $driverId);
        abort_unless($allowed, 403);
    }

    public function index(Request $request): JsonResponse
    {
        $storeId = (int) ($request->user()?->store_id ?? 0);
        abort_if($storeId <= 0, 403);
        $rows = EcommerceOrderReturn::query()
            ->where('store_id', $storeId)
            ->where('return_type', 'replacement')
            ->whereIn('status', ['approved', 'received', 'replaced'])
            ->with(['order:id,order_number,shipping_name', 'orderItem:id,product_name,sku', 'pickup:id,return_id,destination_branch_id,status'])
            ->latest()->paginate(15);

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $return = $this->scopedReturn($request, $id);
        $return->load(['orderItem.product', 'pickup.destinationBranch']);
        $branches = Branch::query()->where('store_id', $return->store_id)->where('status', 'active')
            ->get(['id', 'name', 'branch_type']);
        $variationId = $return->orderItem?->branchInventory?->variation_id;
        $stock = BranchInventory::query()->where('store_id', $return->store_id)
            ->where('product_id', $return->orderItem?->product_id)
            ->where('variation_id', $variationId)
            ->whereIn('branch_id', $branches->pluck('id'))
            ->get(['id', 'branch_id', 'quantity_available', 'quantity_reserved']);
        $drivers = User::query()->where('store_id', $return->store_id)->where('is_active', true)
            ->whereHas('role', fn ($query) => $query->where('name', 'driver'))
            ->get(['id', 'fname', 'lname']);
        $vehicles = EcommerceDeliveryVehicle::query()->where('store_id', $return->store_id)
            ->where('is_active', true)->get(['id', 'vehicle_name', 'plate_number']);

        return response()->json(['success' => true, 'data' => $return, 'branches' => $branches,
            'stock' => $stock, 'drivers' => $drivers, 'vehicles' => $vehicles]);
    }

    public function reserve(Request $request, int $id): JsonResponse
    {
        $this->authorizeStock($request);
        $validated = $request->validate(['branch_id' => ['required', 'integer', 'exists:branches,id']]);
        $return = $this->scopedReturn($request, $id);
        Branch::query()->where('store_id', $return->store_id)->where('status', 'active')
            ->findOrFail($validated['branch_id']);
        if ($return->orderItem?->branch_inventory_id && !$return->orderItem?->branchInventory) {
            return response()->json(['message' => 'The original SKU is unavailable; it cannot be safely matched to replacement stock.'], 422);
        }

        $result = DB::transaction(function () use ($return, $validated) {
            $case = EcommerceOrderReturn::query()->lockForUpdate()->findOrFail($return->id);
            if ($case->status !== 'received' || !in_array($case->replacement_status, ['awaiting_stock', null], true)) {
                throw ValidationException::withMessages(['replacement' => 'This case cannot be reserved again.']);
            }
            $variationId = $return->orderItem?->branchInventory?->variation_id;
            $inventory = BranchInventory::query()->where('store_id', $return->store_id)
                ->where('branch_id', $validated['branch_id'])->where('product_id', $return->orderItem?->product_id)
                ->where('variation_id', $variationId)->lockForUpdate()->first();
            $case->replacement_branch_id = $validated['branch_id'];
            $quantity = (int) $case->received_quantity;
            if (!$inventory || $quantity <= 0 || (int) $inventory->quantity_available < $quantity) {
                $case->replacement_status = 'awaiting_stock';
                $case->save();
                return ['reserved' => false, 'case' => $case];
            }
            $inventory->quantity_available -= $quantity;
            $inventory->quantity_reserved += $quantity;
            $inventory->stock_status = $inventory->quantity_available <= 0 ? 'out_of_stock'
                : ($inventory->quantity_available <= (int) $inventory->reorder_point ? 'low_stock' : 'in_stock');
            $inventory->save();
            $case->replacement_inventory_id = $inventory->id;
            $case->replacement_status = 'reserved';
            $case->save();
            return ['reserved' => true, 'case' => $case];
        });

        return response()->json(['success' => true, 'message' => $result['reserved']
            ? 'Replacement stock reserved.' : 'Not enough stock. Case is awaiting stock.', 'data' => $result['case']]);
    }

    public function assign(Request $request, int $id): JsonResponse
    {
        $this->authorizeLogistics($request);
        $validated = $request->validate([
            'driver_user_id' => ['required', 'integer', 'exists:users,id'],
            'vehicle_id' => ['required', 'integer', 'exists:ecommerce_delivery_vehicles,id'],
        ]);
        $return = $this->scopedReturn($request, $id);
        User::query()->where('store_id', $return->store_id)->where('is_active', true)
            ->whereHas('role', fn ($query) => $query->where('name', 'driver'))
            ->findOrFail($validated['driver_user_id']);
        EcommerceDeliveryVehicle::query()->where('store_id', $return->store_id)
            ->where('is_active', true)->findOrFail($validated['vehicle_id']);
        DB::transaction(function () use ($return, $validated): void {
            $case = EcommerceOrderReturn::query()->lockForUpdate()->findOrFail($return->id);
            if ($case->status !== 'received' || $case->replacement_status !== 'reserved') {
                throw ValidationException::withMessages(['replacement' => 'Reserve replacement stock before assigning a driver.']);
            }
            $case->update(['replacement_driver_id' => $validated['driver_user_id'],
                'replacement_vehicle_id' => $validated['vehicle_id'], 'replacement_status' => 'assigned',
                'replacement_assigned_at' => now()]);
        });

        return response()->json(['success' => true, 'message' => 'Replacement driver assigned.', 'data' => $return]);
    }

    public function dispatch(Request $request, int $id): JsonResponse
    {
        $return = $this->scopedReturn($request, $id);
        $this->authorizeLogistics($request, (int) $return->replacement_driver_id);
        if (!$request->user()->employee?->id) {
            return response()->json(['message' => 'An employee profile is required to record the inventory issue.'], 422);
        }
        DB::transaction(function () use ($return, $request): void {
            $case = EcommerceOrderReturn::query()->lockForUpdate()->findOrFail($return->id);
            if ($case->status !== 'received' || $case->replacement_status !== 'assigned') {
                throw ValidationException::withMessages(['replacement' => 'Only assigned replacements can be dispatched.']);
            }
            $inventory = BranchInventory::query()->where('store_id', $case->store_id)
                ->lockForUpdate()->findOrFail($case->replacement_inventory_id);
            $quantity = (int) $case->received_quantity;
            if ((int) $inventory->quantity_reserved < $quantity || (int) $inventory->quantity_on_hand < $quantity) {
                throw ValidationException::withMessages(['replacement' => 'Reserved stock is no longer available.']);
            }
            $before = (int) $inventory->quantity_on_hand;
            $inventory->quantity_reserved -= $quantity;
            $inventory->quantity_on_hand -= $quantity;
            $inventory->stock_status = $inventory->quantity_available <= 0 ? 'out_of_stock'
                : ($inventory->quantity_available <= (int) $inventory->reorder_point ? 'low_stock' : 'in_stock');
            $inventory->save();
            InventoryTransaction::query()->create([
                'transaction_number' => 'INVTX-REPL-' . $case->id . '-' . now()->format('YmdHisv'),
                'store_id' => $case->store_id, 'branch_id' => $inventory->branch_id,
                'product_id' => $inventory->product_id, 'variation_id' => $inventory->variation_id,
                'transaction_type' => 'sale', 'quantity_before' => $before, 'quantity_change' => -$quantity,
                'quantity_after' => $inventory->quantity_on_hand, 'reference_type' => 'ecommerce_return_replacement',
                'reference_id' => $case->id, 'notes' => 'Zero-charge replacement dispatched.',
                'unit_cost' => $inventory->average_cost ?? $inventory->unit_cost ?? 0,
                'total_value' => (float) ($inventory->average_cost ?? $inventory->unit_cost ?? 0) * $quantity,
                'created_by' => $request->user()->employee?->id,
                'transaction_date' => now(),
            ]);
            $case->replacement_status = 'out_for_delivery';
            $case->replacement_dispatched_at = now();
            $case->save();
        });

        return response()->json(['success' => true, 'message' => 'Replacement dispatched.']);
    }

    public function deliver(Request $request, int $id): JsonResponse
    {
        $return = $this->scopedReturn($request, $id);
        $this->authorizeLogistics($request, (int) $return->replacement_driver_id);
        $validated = $request->validate([
            'received_by' => ['required', 'string', 'max:255'],
            'proof' => ['required', 'image', 'max:5120'],
        ]);
        if ($return->status !== 'received' || $return->replacement_status !== 'out_for_delivery') {
            return response()->json(['message' => 'Only dispatched replacements can be delivered.'], 422);
        }
        $path = $validated['proof']->store("replacement-proofs/{$return->store_id}", 'public');
        try {
            DB::transaction(function () use ($return, $validated, $path): void {
                $case = EcommerceOrderReturn::query()->lockForUpdate()->findOrFail($return->id);
                if ($case->replacement_status !== 'out_for_delivery') {
                    throw ValidationException::withMessages(['replacement' => 'Replacement was already delivered.']);
                }
                $case->replacement_status = 'delivered';
                $case->replacement_delivered_at = now();
                $case->replacement_received_by = $validated['received_by'];
                $case->replacement_proof_path = $path;
                $case->status = 'replaced';
                $case->resolved_at = now();
                $case->save();
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);
            throw $e;
        }

        return response()->json(['success' => true, 'message' => 'Replacement delivered and case completed.']);
    }

    public function fail(Request $request, int $id): JsonResponse
    {
        $return = $this->scopedReturn($request, $id);
        $this->authorizeLogistics($request, (int) $return->replacement_driver_id);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        DB::transaction(function () use ($return, $validated): void {
            $case = EcommerceOrderReturn::query()->lockForUpdate()->findOrFail($return->id);
            if ($case->status !== 'received' || $case->replacement_status !== 'out_for_delivery') {
                throw ValidationException::withMessages(['replacement' => 'Only an active delivery can be marked failed.']);
            }
            $case->replacement_status = 'delivery_failed';
            $case->replacement_failure_reason = $validated['reason'];
            $case->save();
        });

        return response()->json(['success' => true, 'message' => 'Delivery failed. Replacement remains open for reattempt.']);
    }

    public function reattempt(Request $request, int $id): JsonResponse
    {
        $this->authorizeLogistics($request);
        $return = $this->scopedReturn($request, $id);
        DB::transaction(function () use ($return): void {
            $case = EcommerceOrderReturn::query()->lockForUpdate()->findOrFail($return->id);
            if ($case->status !== 'received' || $case->replacement_status !== 'delivery_failed' || !$case->replacement_driver_id) {
                throw ValidationException::withMessages(['replacement' => 'This replacement cannot be reattempted.']);
            }
            $case->replacement_status = 'out_for_delivery';
            $case->save();
        });

        return response()->json(['success' => true, 'message' => 'Replacement delivery reopened. Reserved stock was not deducted again.']);
    }

    public function disposition(Request $request, int $id): JsonResponse
    {
        $this->authorizeStock($request);
        $validated = $request->validate(['disposition' => ['required', 'in:resell,discard']]);
        $return = $this->scopedReturn($request, $id);
        if ($return->status !== 'replaced' || $return->inventory_disposition) {
            return response()->json(['message' => 'Only a completed replacement with quarantined stock can be disposed.'], 422);
        }
        if ($validated['disposition'] === 'resell' && $return->product_condition !== 'good') {
            return response()->json(['message' => 'Damaged returns cannot be released to sellable stock.'], 422);
        }
        if ((int) ($request->user()->employee?->branch_id ?? 0) !== (int) $return->pickup?->destination_branch_id) {
            return response()->json(['message' => 'Only staff at the receiving branch can dispose of this return.'], 403);
        }
        $employeeId = (int) ($request->user()->employee?->id ?? 0);
        DB::transaction(function () use ($return, $validated, $employeeId): void {
            $case = EcommerceOrderReturn::query()->lockForUpdate()->findOrFail($return->id);
            if ($case->inventory_disposition || $case->status !== 'replaced') {
                throw ValidationException::withMessages(['disposition' => 'This return was already disposed.']);
            }
            $variationId = $return->orderItem?->branchInventory?->variation_id;
            $inventory = BranchInventory::query()->where('store_id', $case->store_id)
                ->where('branch_id', $return->pickup?->destination_branch_id)
                ->where('product_id', $return->orderItem?->product_id)
                ->where('variation_id', $variationId)->lockForUpdate()->firstOrFail();
            $quantity = (int) $case->received_quantity;
            if ((int) $inventory->quantity_quarantined < $quantity || $quantity <= 0) {
                throw ValidationException::withMessages(['disposition' => 'Quarantined quantity is insufficient.']);
            }
            $before = (int) $inventory->quantity_on_hand;
            $inventory->quantity_quarantined -= $quantity;
            if ($validated['disposition'] === 'resell') {
                $inventory->quantity_on_hand += $quantity;
                $inventory->quantity_available += $quantity;
            }
            $inventory->stock_status = $inventory->quantity_available <= 0 ? 'out_of_stock'
                : ($inventory->quantity_available <= (int) $inventory->reorder_point ? 'low_stock' : 'in_stock');
            $inventory->save();
            InventoryTransaction::query()->create([
                'transaction_number' => 'INVTX-RET-DISP-' . $case->id . '-' . now()->format('YmdHisv'),
                'store_id' => $case->store_id, 'branch_id' => $inventory->branch_id,
                'product_id' => $inventory->product_id, 'variation_id' => $inventory->variation_id,
                'transaction_type' => $validated['disposition'] === 'resell' ? 'customer_return' : 'writeoff',
                'quantity_before' => $before,
                'quantity_change' => $validated['disposition'] === 'resell' ? $quantity : 0,
                'quantity_after' => $inventory->quantity_on_hand,
                'reference_type' => 'ecommerce_order_return', 'reference_id' => $case->id,
                'notes' => 'Quarantined return ' . $validated['disposition'] . ' after replacement delivery.',
                'unit_cost' => $inventory->average_cost ?? $inventory->unit_cost ?? 0,
                'total_value' => (float) ($inventory->average_cost ?? $inventory->unit_cost ?? 0) * $quantity,
                'created_by' => $employeeId, 'transaction_date' => now(),
            ]);
            $case->inventory_disposition = $validated['disposition'];
            $case->save();
        });

        return response()->json(['success' => true, 'message' => 'Returned unit disposition recorded.']);
    }
}
