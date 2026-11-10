<?php

namespace App\Http\Controllers\Api\Logistics;

use App\Http\Controllers\Controller;
use App\Models\CRM\EcommerceOrderReturn;
use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Hr\Employee;
use App\Models\Store\Branch;
use App\Services\Finance\CashflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
            ->with([
                'order', 'orderItem.branchInventory', 'pickup.destinationBranch', 'replacementBranch', 'replacementTrip.vehicle', 'replacementTrip.driver',
                'salesOrder', 'salesOrderItem.branchInventory',
            ])
            ->findOrFail($id)
            ->normalizeSourceRelationsForDisplay();
    }

    private function authorizeStock(Request $request): void
    {
        abort_unless($request->user()->hasAnyPermission([
            'inventory.receiving.manage', 'inventory.stock.manage', 'warehouse.receiving.view', 'crm.returns.manage',
        ]) || $request->user()->hasRole('owner'), 403);
    }

    private function authorizeLogistics(Request $request, ?int $driverId = null): void
    {
        $allowed = $request->user()->hasPermissionTo('logistics.deliveries.manage')
            || $request->user()->hasRole('owner')
            || ($driverId && (int) $request->user()->id === $driverId);
        abort_unless($allowed, 403);
    }

    private function resolveSourceBranchId(EcommerceOrderReturn $return): int
    {
        if ($return->source_type === 'pos') {
            $return->loadMissing(['salesOrder', 'salesOrderItem.branchInventory']);
        } else {
            $return->loadMissing(['order', 'orderItem.branchInventory']);
        }

        $item = $return->sourceOrderItem();
        $order = $return->sourceOrder();

        return (int) ($item?->branchInventory?->branch_id
            ?: $order?->assigned_branch_id
            ?: $order?->branch_id
            ?: 0);
    }

    public function index(Request $request): JsonResponse
    {
        $storeId = (int) ($request->user()?->store_id ?? 0);
        abort_if($storeId <= 0, 403);
        $rows = EcommerceOrderReturn::query()
            ->where('store_id', $storeId)
            ->where('return_type', 'replacement')
            ->whereIn('status', ['approved', 'received', 'replaced'])
            ->with([
                'order:id,order_number,shipping_name', 'orderItem:id,product_name,sku',
                'salesOrder:id,order_number,customer_name', 'salesOrderItem:id,product_name,sku',
                'pickup:id,return_id,destination_branch_id,status',
            ])
            ->latest()->paginate(15);
        collect($rows->items())->each(fn (EcommerceOrderReturn $return) => $return->normalizeSourceRelationsForDisplay());

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $return = $this->scopedReturn($request, $id);
        $return->load(['orderItem.product', 'orderItem.branchInventory.variation', 'salesOrderItem.product', 'salesOrderItem.variation', 'salesOrderItem.branchInventory.variation', 'pickup.destinationBranch', 'replacementProduct', 'replacementVariation', 'replacementInventory.product', 'replacementInventory.variation', 'replacementInventory.branch']);
        $return->loadMissing('pickup:id,return_id,destination_branch_id');
        $fulfillmentBranchId = (int) ($return->pickup?->destination_branch_id ?: $this->resolveSourceBranchId($return));
        $branches = Branch::query()->where('store_id', $return->store_id)->where('status', 'active')
            ->whereKey($fulfillmentBranchId)
            ->get(['id', 'name', 'branch_type']);
        $originalItem = $return->sourceOrderItem();
        $originalInventory = $return->source_type === 'pos' ? $return->salesOrderItem?->branchInventory : $return->orderItem?->branchInventory;
        $variationId = $originalInventory?->variation_id ?? $originalItem?->variation_id;
        $stock = BranchInventory::query()->where('store_id', $return->store_id)
            ->where('product_id', $originalItem?->product_id)
            ->where('variation_id', $variationId)
            ->whereIn('branch_id', $branches->pluck('id'))
            ->get(['id', 'branch_id', 'quantity_available', 'quantity_reserved']);
        $quantity = max(1, (int) ($return->received_quantity ?: $return->requested_quantity ?: 1));
        $sourceQuantity = max(1, (int) ($originalItem?->quantity ?? 1));
        $sourceLineTotal = $return->source_type === 'pos'
            ? (float) ($originalItem?->line_total ?? ((float) ($originalItem?->unit_price ?? 0) * $sourceQuantity))
            : (float) ($originalItem?->line_subtotal ?? ((float) ($originalItem?->unit_price ?? 0) * $sourceQuantity));
        $sourceOrder = $return->sourceOrder();
        $orderSubtotal = max(0.0, (float) ($sourceOrder?->subtotal ?? $sourceLineTotal));
        $discountShare = $return->source_type === 'pos' ? 0.0 : ($orderSubtotal > 0
            ? round(max(0.0, (float) ($sourceOrder?->discount_amount ?? 0)) * ($sourceLineTotal / $orderSubtotal), 2)
            : 0.0);
        if ($return->source_type === 'pos') $discountShare = 0.0;
        $discountShare = min($sourceLineTotal, $discountShare);
        $originalPaidValue = max(0.0, round(($sourceLineTotal * min($quantity, $sourceQuantity) / $sourceQuantity) - ($discountShare * min($quantity, $sourceQuantity) / $sourceQuantity), 2));
        $originalProductId = (int) ($return->sourceOrderItem()?->product_id ?? 0);
        $alternatives = BranchInventory::query()
            ->where('store_id', $return->store_id)
            ->where('quantity_available', '>=', $quantity)
            ->where('product_id', '!=', $originalProductId)
            ->whereIn('branch_id', $branches->pluck('id'))
            ->whereHas('product', fn ($query) => $query->where('store_id', $return->store_id)->where('is_active', true))
            ->where(function ($query) use ($return): void {
                $query->whereNull('variation_id')->orWhereHas('variation', fn ($variation) => $variation
                    ->where('store_id', $return->store_id)
                    ->where('is_active', true)
                    ->whereColumn('product_variations.product_id', 'branch_inventory.product_id'));
            })
            ->with([
                'branch:id,name,branch_type',
                'product:id,store_id,product_name,sku,base_price,discounted_price,is_active,weight_kg',
                'variation:id,store_id,product_id,variation_name,variation_sku,base_price,discounted_price,is_active,weight_kg',
            ])
            ->get(['id', 'store_id', 'branch_id', 'product_id', 'variation_id', 'quantity_on_hand', 'quantity_reserved', 'quantity_damaged'])
            ->map(function (BranchInventory $inventory) use ($quantity, $originalPaidValue): array {
                $unitPrice = $this->replacementUnitPrice($inventory);
                $total = round($unitPrice * $quantity, 2);
                $unitWeight = $inventory->variation?->weight_kg ?? $inventory->product?->weight_kg;
                return [
                    'inventory_id' => (int) $inventory->id,
                    'product_id' => (int) $inventory->product_id,
                    'variation_id' => $inventory->variation_id ? (int) $inventory->variation_id : null,
                    'product_name' => $inventory->variation?->variation_name
                        ? $inventory->product?->product_name . ' - ' . $inventory->variation->variation_name
                        : $inventory->product?->product_name,
                    'sku' => $inventory->variation?->variation_sku ?: $inventory->product?->sku,
                    'branch_id' => (int) $inventory->branch_id,
                    'branch_name' => $inventory->branch?->name,
                    'branch_type' => $inventory->branch?->branch_type,
                    'quantity_available' => (int) $inventory->quantity_available,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $total,
                    'original_paid_value' => round((float) $originalPaidValue, 2),
                    'price_difference' => round($total - (float) $originalPaidValue, 2),
                    'weight_kg' => $unitWeight !== null ? (float) $unitWeight : null,
                    'total_weight_kg' => $unitWeight !== null ? round((float) $unitWeight * $quantity, 2) : null,
                ];
            })
            ->filter(fn (array $option): bool => $option['price_difference'] > 0)
            ->values();
        return response()->json(['success' => true, 'data' => $return, 'branches' => $branches,
            'stock' => $stock, 'original_paid_value' => round((float) $originalPaidValue, 2), 'alternatives' => $alternatives]);
    }

    public function reserveAlternative(Request $request, int $id, CashflowService $cashflow): JsonResponse
    {
        $this->authorizeStock($request);
        $validated = $request->validate([
            'inventory_id' => ['required', 'integer', 'exists:branch_inventory,id'],
            'customer_accepted' => ['required', 'accepted'],
            'payment_verified' => ['required', 'accepted'],
            'payment_method' => ['required', 'in:cash,card,gcash,bank_transfer'],
            'payment_reference' => ['required_unless:payment_method,cash', 'nullable', 'string', 'max:120'],
        ]);
        $return = $this->scopedReturn($request, $id);
        $actor = $request->user();

        $result = DB::transaction(function () use ($return, $validated, $actor, $cashflow): array {
            $case = EcommerceOrderReturn::query()->where('store_id', $return->store_id)
                ->lockForUpdate()->findOrFail($return->id);
            if ($case->status !== 'received' || !in_array($case->replacement_status, ['awaiting_stock', null], true)
                || $case->replacement_inventory_id) {
                throw ValidationException::withMessages(['replacement' => ['This replacement is not waiting for an alternative item.']]);
            }

            $quantity = (int) $case->received_quantity;
            if ($quantity <= 0) {
                throw ValidationException::withMessages(['replacement' => ['Inspect and receive the returned item before reserving an alternative.']]);
            }
            $inventory = BranchInventory::query()->where('store_id', $case->store_id)
                ->with(['product', 'variation', 'branch'])->lockForUpdate()->findOrFail($validated['inventory_id']);
            if (!$inventory->branch || (int) $inventory->branch->store_id !== (int) $case->store_id
                || $inventory->branch->status !== 'active' || !$inventory->product
                || (int) $inventory->product->store_id !== (int) $case->store_id || !$inventory->product->is_active
                || ($inventory->variation && ((int) $inventory->variation->store_id !== (int) $case->store_id
                    || (int) $inventory->variation->product_id !== (int) $inventory->product_id
                    || !$inventory->variation->is_active))) {
                throw ValidationException::withMessages(['inventory_id' => ['Select an active product and branch from this store.']]);
            }
            $originalItem = $case->sourceOrderItem();
            if (!$originalItem || (int) $originalItem->product_id === (int) $inventory->product_id) {
                throw ValidationException::withMessages(['inventory_id' => ['Choose a different product for the higher-value replacement.']]);
            }
            $availableQuantity = (int) $inventory->quantity_available;
            if ($availableQuantity < $quantity) {
                throw ValidationException::withMessages(['inventory_id' => ['Not enough available stock remains for this replacement. Refresh the page and choose another branch or item.']]);
            }

            $unitPrice = $this->replacementUnitPrice($inventory);
            if ($unitPrice <= 0) {
                throw ValidationException::withMessages(['inventory_id' => ['The replacement item has no valid selling price.']]);
            }
            $sourceQuantity = max(1, (int) ($originalItem->quantity ?? 1));
            $sourceLineTotal = $case->source_type === 'pos'
                ? (float) ($originalItem->line_total ?? ((float) ($originalItem->unit_price ?? 0) * $sourceQuantity))
                : (float) ($originalItem->line_subtotal ?? ((float) ($originalItem->unit_price ?? 0) * $sourceQuantity));
            $sourceOrder = $case->sourceOrder();
            $orderSubtotal = max(0.0, (float) ($sourceOrder?->subtotal ?? $sourceLineTotal));
            $discountShare = $case->source_type === 'pos' ? 0.0 : ($orderSubtotal > 0
                ? round(max(0.0, (float) ($sourceOrder?->discount_amount ?? 0)) * ($sourceLineTotal / $orderSubtotal), 2)
                : 0.0);
            if ($case->source_type === 'pos') $discountShare = 0.0;
            $discountShare = min($sourceLineTotal, $discountShare);
            $originalPaidValue = max(0.0, round(($sourceLineTotal * min($quantity, $sourceQuantity) / $sourceQuantity) - ($discountShare * min($quantity, $sourceQuantity) / $sourceQuantity), 2));
            $difference = round(($unitPrice * $quantity) - (float) $originalPaidValue, 2);
            if ($difference <= 0) {
                throw ValidationException::withMessages(['inventory_id' => ['This item is not priced higher than the returned item. Use the standard replacement stock workflow for equal or lower-priced items.']]);
            }

            $reference = trim((string) ($validated['payment_reference'] ?? ''));
            if ($validated['payment_method'] === 'cash' && $reference === '') {
                $reference = 'CASH-REPL-' . $case->id . '-' . now()->format('YmdHis');
            }

            $inventory->quantity_reserved += $quantity;
            $inventory->save();

            $cashflow->credit(
                (int) $case->store_id,
                $difference,
                'ecommerce_return_replacement',
                (int) $case->id,
                (int) $actor->id,
                'Higher-value replacement price difference for ' . ($case->return_number ?: 'Return #' . $case->id),
                (string) $validated['payment_method'],
                ['inventory_id' => (int) $inventory->id, 'original_paid_value' => $originalPaidValue,
                    'replacement_unit_price' => $unitPrice, 'quantity' => $quantity, 'payment_reference' => $reference]
            );

            $case->update([
                'replacement_inventory_id' => (int) $inventory->id,
                'replacement_branch_id' => (int) $inventory->branch_id,
                'replacement_product_id' => (int) $inventory->product_id,
                'replacement_variation_id' => $inventory->variation_id,
                'replacement_unit_price' => $unitPrice,
                'replacement_price_difference' => $difference,
                'replacement_payment_status' => 'paid',
                'replacement_payment_method' => $validated['payment_method'],
                'replacement_payment_reference' => $reference,
                'replacement_customer_accepted_by' => (int) $actor->id,
                'replacement_customer_accepted_at' => now(),
                'replacement_paid_by' => (int) $actor->id,
                'replacement_paid_at' => now(),
                'replacement_status' => 'reserved',
            ]);

            return ['case' => $case->fresh(['replacementProduct', 'replacementVariation', 'replacementBranch', 'replacementInventory'])];
        });

        return response()->json([
            'success' => true,
            'message' => 'Higher-value replacement accepted, price difference recorded, and stock reserved.',
            'data' => $result['case'],
        ]);
    }

    public function reserve(Request $request, int $id): JsonResponse
    {
        $this->authorizeStock($request);
        $validated = $request->validate(['branch_id' => ['required', 'integer', 'exists:branches,id']]);
        $return = $this->scopedReturn($request, $id);
        $return->loadMissing('pickup:id,return_id,destination_branch_id');
        $fulfillmentBranchId = (int) ($return->pickup?->destination_branch_id ?: $this->resolveSourceBranchId($return));
        if ($fulfillmentBranchId <= 0 || (int) $validated['branch_id'] !== $fulfillmentBranchId) {
            throw ValidationException::withMessages(['branch_id' => ['Replacement stock must be reserved at the branch that fulfilled the original order.']]);
        }
        Branch::query()->where('store_id', $return->store_id)->where('status', 'active')
            ->findOrFail($validated['branch_id']);
        if ($return->sourceOrderItem()?->branch_inventory_id && !$return->sourceOrderItem()?->branchInventory) {
            return response()->json(['message' => 'The original SKU is unavailable; it cannot be safely matched to replacement stock.'], 422);
        }

        $result = DB::transaction(function () use ($return, $validated) {
            $case = EcommerceOrderReturn::query()->lockForUpdate()->findOrFail($return->id);
            if (!in_array($case->status, ['approved', 'received'], true) || !in_array($case->replacement_status, ['awaiting_stock', null], true)) {
                throw ValidationException::withMessages(['replacement' => 'This case cannot be reserved again.']);
            }
            $originalItem = $return->sourceOrderItem();
            $originalInventory = $return->source_type === 'pos' ? $return->salesOrderItem?->branchInventory : $return->orderItem?->branchInventory;
            $variationId = $originalInventory?->variation_id ?? $originalItem?->variation_id;
            $inventory = BranchInventory::query()->where('store_id', $return->store_id)
                ->where('branch_id', $validated['branch_id'])->where('product_id', $originalItem?->product_id)
                ->where('variation_id', $variationId)->lockForUpdate()->first();
            $case->replacement_branch_id = $validated['branch_id'];
            $quantity = (int) ($case->received_quantity ?: $case->requested_quantity);
            if (!$inventory || $quantity <= 0 || max(0, (int) $inventory->quantity_on_hand - (int) $inventory->quantity_reserved - (int) $inventory->quantity_damaged) < $quantity) {
                $case->replacement_status = 'awaiting_stock';
                $case->save();
                return ['reserved' => false, 'case' => $case];
            }
            $inventory->quantity_reserved += $quantity;
            $inventory->save();
            $case->replacement_inventory_id = $inventory->id;
            $case->replacement_status = 'reserved';
            $case->save();
            return ['reserved' => true, 'case' => $case];
        });

        return response()->json(['success' => true, 'message' => $result['reserved']
            ? 'Replacement stock reserved.' : 'Not enough stock. Case is awaiting stock.', 'data' => $result['case']]);
    }

    private function replacementUnitPrice(BranchInventory $inventory): float
    {
        $variationDiscountedPrice = (float) ($inventory->variation?->discounted_price ?? 0);
        if ($variationDiscountedPrice > 0) {
            return round($variationDiscountedPrice, 2);
        }

        $variationBasePrice = (float) ($inventory->variation?->base_price ?? 0);
        if ($variationBasePrice > 0) {
            return round($variationBasePrice, 2);
        }

        $productDiscountedPrice = (float) ($inventory->product?->discounted_price ?? 0);
        if ($productDiscountedPrice > 0) {
            return round($productDiscountedPrice, 2);
        }

        return round((float) ($inventory->product?->base_price ?? 0), 2);
    }

    public function deliver(Request $request, int $id): JsonResponse
    {
        $return = $this->scopedReturn($request, $id);
        $this->authorizeLogistics($request, (int) $return->replacement_driver_id);
        $validated = $request->validate([
            'received_by' => ['required', 'string', 'max:255'],
            'proof' => ['required', 'image', 'max:5120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location_address' => ['nullable', 'string', 'max:2000'],
        ]);
        if (!in_array($return->status, ['approved', 'received'], true) || $return->replacement_status !== 'out_for_delivery') {
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
                if (Schema::hasColumn('ecommerce_order_returns', 'current_latitude')) {
                    $case->current_latitude = $validated['latitude'] ?? $case->current_latitude;
                    $case->current_longitude = $validated['longitude'] ?? $case->current_longitude;
                    $case->current_address = $validated['location_address'] ?? $case->current_address;
                }
                if ($case->status === 'received') {
                    $case->status = 'replaced';
                    $case->resolved_at = now();
                }
                $case->save();
                app(\App\Services\Logistics\CompleteDeliveryTrip::class)->afterDeliveryUpdate($case);
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);
            throw $e;
        }

        if ($return->user_id && $return->source_type !== 'pos') {
            $this->notify((int) $return->user_id, [
                'module' => 'ecommerce',
                'entity_type' => 'ecommerce_order_return',
                'entity_id' => (int) $return->id,
                'action' => 'replacement_delivered',
                'title' => 'Replacement delivered',
                'message' => $return->status === 'received'
                    ? 'Your replacement has been delivered. Thank you for completing the return process.'
                    : 'Your replacement has been delivered. The returned item will be inspected when it reaches the branch.',
                'severity' => 'success',
                'store_id' => (int) $return->store_id,
                'link' => '/orders/' . (int) $return->order_id,
                'data' => ['status' => 'replaced'],
            ]);
        }

        $branchEmployeeIds = Employee::query()
            ->where('store_id', (int) $return->store_id)
            ->where('branch_id', (int) $return->replacement_branch_id)
            ->where('status', 'active')
            ->whereNotNull('user_id')
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->pluck('user_id')
            ->map(fn ($userId) => (int) $userId)
            ->all();
        $permittedBranchUsers = array_values(array_intersect(
            $branchEmployeeIds,
            $this->userIdsWithAnyPermission((int) $return->store_id, ['crm.returns.view']),
        ));
        $returnNumber = $return->return_number ?: 'Return #' . (int) $return->id;
        foreach ($permittedBranchUsers as $userId) {
            $alreadyNotified = \App\Models\Core\SystemNotification::query()
                ->where('store_id', (int) $return->store_id)
                ->where('user_id', $userId)
                ->where('entity_type', 'ecommerce_order_return')
                ->where('entity_id', (int) $return->id)
                ->where('action', 'replacement_delivered')
                ->exists();
            if ($alreadyNotified) continue;

            $this->notify($userId, [
                'store_id' => (int) $return->store_id,
                'branch_id' => (int) $return->replacement_branch_id,
                'module' => 'crm',
                'entity_type' => 'ecommerce_order_return',
                'entity_id' => (int) $return->id,
                'action' => 'replacement_delivered',
                'title' => 'Replacement Delivered',
                'message' => "Replacement for {$returnNumber} was delivered to the customer.",
                'severity' => 'success',
                'link' => '/crm/returns/' . (int) $return->id,
                'data' => [
                    'required_permission' => 'crm.returns.view',
                    'status' => 'replaced',
                    'return_number' => $returnNumber,
                    'branch_id' => (int) $return->replacement_branch_id,
                ],
            ]);
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
            if (!in_array($case->status, ['approved', 'received'], true) || $case->replacement_status !== 'out_for_delivery') {
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
            if (!in_array($case->status, ['approved', 'received'], true) || $case->replacement_status !== 'delivery_failed' || !$case->replacement_driver_id) {
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
            $sourceItem = $return->sourceOrderItem();
            $sourceInventory = $return->source_type === 'pos' ? $return->salesOrderItem?->branchInventory : $return->orderItem?->branchInventory;
            $variationId = $sourceInventory?->variation_id ?? $sourceItem?->variation_id;
            $inventory = BranchInventory::query()->where('store_id', $case->store_id)
                ->where('branch_id', $return->pickup?->destination_branch_id)
                ->where('product_id', $sourceItem?->product_id)
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
            $inventory->suppressAutomaticTransactionLog();
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
















