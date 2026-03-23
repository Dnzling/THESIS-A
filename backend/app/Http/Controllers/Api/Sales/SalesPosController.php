<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesOrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesPosController extends Controller
{
    public function products(Request $request): JsonResponse
    {
        $user = $request->user();
        $storeId = (int) ($user->store_id ?? 0);
        $branchId = (int) ($request->input('branch_id', $user->branch_id ?? 0));

        $query = BranchInventory::query()
            ->with(['product:id,product_name,sku,base_price,discounted_price,is_active', 'variation:id,variation_name'])
            ->where('store_id', $storeId)
            ->when($branchId > 0, fn($q) => $q->where('branch_id', $branchId))
            ->where('quantity_available', '>', 0)
            ->whereHas('product', fn($q) => $q->where('is_active', true)->whereNull('deleted_at'));

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $rows = $query->orderByDesc('quantity_available')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'customer_name' => 'nullable|string|max:150',
            'customer_phone' => 'nullable|string|max:50',
            'payment_method' => 'required|in:cash,card,gcash,bank_transfer,mixed',
            'discount_amount' => 'nullable|numeric|min:0',
            'amount_tendered' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.branch_inventory_id' => 'required|exists:branch_inventory,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $storeId = (int) ($user->store_id ?? 0);
        $branchId = (int) ($validated['branch_id'] ?? $user->branch_id ?? 0);
        if (!$storeId) {
            return response()->json(['success' => false, 'message' => 'No store assigned.'], 422);
        }

        $order = DB::transaction(function () use ($validated, $storeId, $branchId, $user) {
            $subtotal = 0.0;
            $tax = 0.0;
            $discount = (float) ($validated['discount_amount'] ?? 0);

            $order = SalesOrder::create([
                'store_id' => $storeId,
                'branch_id' => $branchId ?: null,
                'order_number' => $this->nextOrderNumber(),
                'status' => 'completed',
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'payment_method' => $validated['payment_method'],
                'subtotal' => 0,
                'discount_amount' => $discount,
                'tax_amount' => 0,
                'total_amount' => 0,
                'amount_tendered' => (float) ($validated['amount_tendered'] ?? 0),
                'change_amount' => 0,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            foreach ($validated['items'] as $itemRow) {
                $inv = BranchInventory::query()
                    ->with(['product:id,product_name,sku,base_price,discounted_price,tax_rate'])
                    ->lockForUpdate()
                    ->findOrFail((int) $itemRow['branch_inventory_id']);

                if ((int) $inv->store_id !== $storeId) {
                    abort(response()->json(['success' => false, 'message' => 'Invalid inventory item for this store.'], 422));
                }

                $qty = (int) $itemRow['quantity'];
                if ((int) $inv->quantity_available < $qty) {
                    abort(response()->json(['success' => false, 'message' => "Insufficient stock for {$inv->product?->product_name}."], 422));
                }

                $unitPrice = (float) ($inv->product?->discounted_price ?? $inv->product?->base_price ?? 0);
                $lineSubtotal = $qty * $unitPrice;
                $lineTax = 0.0;
                $lineTotal = $lineSubtotal + $lineTax;

                SalesOrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $inv->product_id,
                    'variation_id' => $inv->variation_id,
                    'branch_inventory_id' => $inv->id,
                    'product_name' => $inv->product?->product_name ?? 'Product',
                    'sku' => $inv->product?->sku,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'line_discount' => 0,
                    'line_tax' => $lineTax,
                    'line_total' => $lineTotal,
                ]);

                $before = (int) $inv->quantity_available;
                $inv->quantity_available = max(0, (int) $inv->quantity_available - $qty);
                $inv->quantity_on_hand = max(0, (int) $inv->quantity_on_hand - $qty);
                $inv->stock_status = $inv->quantity_available <= 0 ? 'out_of_stock' : ($inv->quantity_available <= $inv->reorder_point ? 'low_stock' : 'in_stock');
                $inv->save();

                InventoryTransaction::create([
                    'transaction_number' => 'TXN-SALE-' . now()->format('YmdHis') . '-' . random_int(1000, 9999),
                    'store_id' => $storeId,
                    'branch_id' => $branchId ?: $inv->branch_id,
                    'product_id' => $inv->product_id,
                    'variation_id' => $inv->variation_id,
                    'transaction_type' => 'sale',
                    'quantity_before' => $before,
                    'quantity_change' => -$qty,
                    'quantity_after' => (int) $inv->quantity_available,
                    'reference_type' => 'sales_pos_order',
                    'reference_id' => $order->id,
                    'notes' => "POS sale {$order->order_number}",
                    'unit_cost' => (float) ($inv->average_cost ?? 0),
                    'total_value' => $lineTotal,
                    'requires_approval' => false,
                    'approval_status' => 'auto_approved',
                    'created_by' => $user->id,
                    'transaction_date' => now(),
                ]);

                $subtotal += $lineSubtotal;
                $tax += $lineTax;
            }

            $total = max(0, $subtotal + $tax - $discount);
            $tendered = (float) ($order->amount_tendered ?? 0);
            $change = max(0, $tendered - $total);

            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'total_amount' => $total,
                'change_amount' => $change,
            ]);

            return $order->fresh(['items', 'branch']);
        });

        return response()->json(['success' => true, 'message' => 'POS checkout completed.', 'data' => $order], 201);
    }

    public function orders(Request $request): JsonResponse
    {
        $query = SalesOrder::query()->with(['branch:id,name', 'creator:id,fname,lname'])->withCount('items');
        $this->applyStoreScope($request, $query);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $rows = $query->orderByDesc('created_at')->paginate((int) $request->input('per_page', 20));
        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $query = SalesOrder::query()
            ->with(['branch:id,name', 'creator:id,fname,lname,email', 'items.product:id,product_name,sku', 'items.variation:id,variation_name']);
        $this->applyStoreScope($request, $query);
        $order = $query->findOrFail($id);

        return response()->json(['success' => true, 'data' => $order]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $query = SalesOrder::query();
        $this->applyStoreScope($request, $query);

        $today = (clone $query)->whereDate('created_at', now()->toDateString());
        $month = (clone $query)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);

        return response()->json([
            'success' => true,
            'data' => [
                'today_orders' => (clone $today)->count(),
                'today_sales' => (float) (clone $today)->sum('total_amount'),
                'month_orders' => (clone $month)->count(),
                'month_sales' => (float) (clone $month)->sum('total_amount'),
                'recent_orders' => (clone $query)->with('branch:id,name')->orderByDesc('created_at')->limit(8)->get(),
            ],
        ]);
    }

    private function applyStoreScope(Request $request, $query): void
    {
        $user = $request->user();
        if (!$user->hasRole('super_admin')) {
            $query->where('store_id', $user->store_id);
            return;
        }

        if ($request->filled('store_id')) {
            $query->where('store_id', (int) $request->input('store_id'));
        }
    }

    private function nextOrderNumber(): string
    {
        $prefix = 'POS-' . now()->format('Ymd') . '-';
        $last = SalesOrder::query()->where('order_number', 'like', "{$prefix}%")->orderByDesc('id')->value('order_number');
        $seq = 1;
        if ($last && preg_match('/(\d+)$/', (string) $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}

