<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;
use App\Models\Procurement\Supplier\Supplier;
use App\Models\Store\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $storeId = $this->authorizeStore($request, 'view');
        $base = PurchaseOrder::query()->where('store_id', $storeId);
        $stats = [
            'total_count' => (clone $base)->count(),
            'pending_receipt_count' => (clone $base)->whereIn('status', ['pending_receipt', 'partially_received'])->count(),
            'total_amount' => (clone $base)->sum('total_amount'),
            'delayed_count' => (clone $base)->whereNotNull('expected_delivery_date')->whereDate('expected_delivery_date', '<', today())->whereNotIn('status', ['goods_received', 'delivered', 'cancelled'])->count(),
        ];
        $orders = PurchaseOrder::query()
            ->with(['supplier:id,supplier_name,supplier_code', 'branch:id,name', 'createdBy.user:id,fname,lname'])
            ->withCount('items')
            ->where('store_id', $storeId)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->query('search'));
                $query->where(function ($nested) use ($search) {
                    $nested->where('po_number', 'like', "%{$search}%")
                        ->orWhereHas('supplier', fn ($supplier) => $supplier->where('supplier_name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('supplier_id'), fn ($query) => $query->where('supplier_id', $request->integer('supplier_id')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('order_date', '>=', $request->query('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('order_date', '<=', $request->query('date_to')))
            ->latest('id')
            ->paginate(min(50, max(1, $request->integer('per_page', 15))));

        return response()->json(['success' => true, 'data' => $orders, 'stats' => $stats, 'suppliers' => Supplier::query()->where('store_id', $storeId)->orderBy('supplier_name')->get(['id', 'supplier_name'])]);
    }

    public function options(Request $request)
    {
        $storeId = $this->authorizeStore($request, 'view');

        $products = Product::query()
            ->with([
                'variations' => fn ($query) => $query->select('id', 'product_id', 'variation_name', 'variation_sku')->where('is_active', true),
                'suppliers' => fn ($query) => $query->where('suppliers.store_id', $storeId)->where('suppliers.status', 'active'),
            ])
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->whereHas('suppliers', fn ($query) => $query->where('suppliers.store_id', $storeId)->where('suppliers.status', 'active'))
            ->orderBy('product_name')
            ->get(['id', 'product_name', 'sku', 'product_type', 'unit_of_measurement']);

        $productIds = $products->pluck('id');
        $inventoryPoints = DB::table('branch_inventory')
            ->where('store_id', $storeId)
            ->whereIn('product_id', $productIds)
            ->whereNull('deleted_at')
            ->get(['product_id', 'branch_id', 'variation_id', 'reorder_point'])
            ->groupBy('product_id');
        $reorderRules = DB::table('reorder_rules')
            ->whereIn('product_id', $productIds)
            ->where('is_active', true)
            ->get(['product_id', 'branch_id', 'reorder_point'])
            ->groupBy('product_id');

        $products = $products->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->product_name,
                'sku' => $product->sku,
                'product_type' => $product->product_type,
                'unit' => $product->unit_of_measurement,
                'inventory_points' => $inventoryPoints->get($product->id, collect())->values(),
                'reorder_rules' => $reorderRules->get($product->id, collect())->values(),
                'suppliers' => $product->suppliers->map(fn ($supplier) => [
                    'id' => $supplier->id,
                    'unit_cost' => (float) $supplier->pivot->supplier_price,
                ])->values(),
                'variations' => $product->variations->map(fn ($variation) => [
                    'id' => $variation->id,
                    'name' => $variation->variation_name,
                    'sku' => $variation->variation_sku,
                ])->values(),
            ]);

        return response()->json(['success' => true, 'data' => [
            'suppliers' => Supplier::query()->where('store_id', $storeId)->where('status', 'active')->orderBy('supplier_name')->get(['id', 'supplier_name', 'supplier_code', 'default_tax_rate', 'is_tax_exempt']),
            'branches' => Branch::query()->where('store_id', $storeId)->where('status', 'active')->orderByDesc('is_main_branch')->orderBy('name')->get(['id', 'name', 'branch_code']),
            'products' => $products,
        ]]);
    }

    public function show(Request $request, int $id)
    {
        $storeId = $this->authorizeStore($request, 'view');
        $order = PurchaseOrder::query()
            ->with(['supplier', 'branch', 'createdBy.user', 'items.product', 'items.variation', 'goodsReceipts:id,purchase_order_id,grn_number,receipt_date,receipt_status'])
            ->where('store_id', $storeId)
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $order]);
    }

    public function store(Request $request)
    {
        $storeId = $this->authorizeStore($request, 'manage');
        $validated = $request->validate([
            'submit' => ['sometimes', 'boolean'],
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')->where('store_id', $storeId)->whereNull('deleted_at')],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('store_id', $storeId)],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:today'],
            'payment_terms' => ['required', Rule::in(['cash_on_delivery', 'net_7', 'net_15', 'net_30', 'net_60', 'advance_payment'])],
            'fulfillment_method' => ['required', Rule::in(['supplier_delivery', 'store_pickup'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('store_id', $storeId)->where('is_active', true)->whereNull('deleted_at')],
            'items.*.variation_id' => ['nullable', 'integer', Rule::exists('product_variations', 'id')->where('store_id', $storeId)->where('is_active', true)->whereNull('deleted_at')],
            'items.*.quantity_ordered' => ['required', 'integer', 'min:10'],
        ]);

        $employee = $request->user()?->employee;
        if (!$employee || (int) $employee->store_id !== $storeId) {
            throw ValidationException::withMessages(['created_by' => 'A store employee profile is required to create a purchase order.']);
        }

        $supplier = Supplier::where('store_id', $storeId)->findOrFail($validated['supplier_id']);
        $branch = Branch::where('store_id', $storeId)->findOrFail($validated['branch_id']);
        if ($supplier->status !== 'active' || $branch->status !== 'active') {
            throw ValidationException::withMessages(['supplier_id' => 'Select an active supplier and branch.']);
        }

        foreach ($validated['items'] as $index => $item) {
            if (!DB::table('supplier_products')->where('supplier_id', $supplier->id)->where('product_id', $item['product_id'])->exists()) {
                throw ValidationException::withMessages(["items.{$index}.product_id" => 'Select a product linked to this supplier.']);
            }
            if (!empty($item['variation_id']) && !ProductVariation::query()
                ->where('store_id', $storeId)
                ->where('product_id', $item['product_id'])
                ->where('is_active', true)
                ->whereKey($item['variation_id'])->exists()) {
                throw ValidationException::withMessages(["items.{$index}.variation_id" => 'The variation does not belong to this product.']);
            }
        }

        $lines = collect($validated['items'])->map(function ($item) use ($supplier) {
            $item['unit_cost'] = (float) DB::table('supplier_products')
                ->where('supplier_id', $supplier->id)
                ->where('product_id', $item['product_id'])
                ->value('supplier_price');
            $item['line_total'] = round((int) $item['quantity_ordered'] * $item['unit_cost'], 2);
            return $item;
        });
        $subtotal = round($lines->sum('line_total'), 2);
        $discount = 0;
        $taxRate = $supplier->is_tax_exempt ? 0 : (float) ($supplier->default_tax_rate ?? 12);
        $taxAmount = round(($subtotal - $discount) * $taxRate / 100, 2);
        $shipping = 0;

        $order = DB::transaction(function () use ($validated, $storeId, $employee, $supplier, $lines, $subtotal, $discount, $taxRate, $taxAmount, $shipping) {
            do {
                $number = 'PO-' . now()->format('Ymd') . '-' . Str::upper(Str::random(8));
            } while (PurchaseOrder::where('po_number', $number)->exists());

            $order = PurchaseOrder::create([
                'po_number' => $number,
                'store_id' => $storeId,
                'branch_id' => $validated['branch_id'],
                'supplier_id' => $supplier->id,
                'status' => 'draft',
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $taxAmount,
                'shipping_cost' => $shipping,
                'total_amount' => round($subtotal - $discount + $taxAmount + $shipping, 2),
                'rfq_required' => false,
                'payment_status' => 'pending',
                'payment_terms' => $validated['payment_terms'],
                'order_date' => today(),
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'fulfillment_method' => $validated['fulfillment_method'],
                'created_by' => $employee->id,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id' => $line['product_id'],
                    'variation_id' => $line['variation_id'] ?? null,
                    'quantity_ordered' => $line['quantity_ordered'],
                    'quantity_received' => 0,
                    'quantity_rejected' => 0,
                    'unit_cost' => $line['unit_cost'],
                    'tax_rate' => $taxRate,
                    'discount_percent' => 0,
                    'line_total' => $line['line_total'],
                ]);
            }

            if ($validated['submit'] ?? false) {
                $order->submitForReceipt();
            }

            return $order;
        });

        return response()->json([
            'success' => true,
            'message' => ($validated['submit'] ?? false) ? 'Purchase order submitted and awaiting receipt.' : 'Purchase order saved as draft.',
            'data' => $order->load(['supplier', 'branch', 'items.product', 'items.variation']),
        ], 201);
    }

    public function send(Request $request, int $id)
    {
        $storeId = $this->authorizeStore($request, 'manage');
        $order = PurchaseOrder::query()->where('store_id', $storeId)->findOrFail($id);
        if ($order->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only draft purchase orders can be submitted.']);
        }
        $order->submitForReceipt();

        return response()->json(['success' => true, 'message' => 'Purchase order submitted and awaiting receipt.', 'data' => $order->fresh()]);
    }

    private function authorizeStore(Request $request, string $action): int
    {
        $storeId = (int) ($request->user()?->store_id ?? 0);
        abort_unless($storeId > 0 && $request->user()?->hasPermissionTo("inventory.purchase_orders.{$action}", $storeId), 403);
        return $storeId;
    }
}
