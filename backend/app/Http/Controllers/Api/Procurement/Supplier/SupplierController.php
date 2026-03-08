<?php
// backend/app/Http/Controllers/Procurement/Supplier/SupplierController.php

namespace App\Http\Controllers\Api\Procurement\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Supplier\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    /**
     * List all suppliers
     * GET /api/procurement/suppliers
     */
    public function index(Request $request): JsonResponse
    {
        $query = Supplier::where('store_id', auth()->user()->store_id);

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('supplier_type')) {
            $query->where('supplier_type', $request->supplier_type);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('supplier_name', 'LIKE', "%{$search}%")
                  ->orWhere('supplier_code', 'LIKE', "%{$search}%")
                  ->orWhere('company_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('min_rating')) {
            $query->where('rating', '>=', $request->min_rating);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $suppliers = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $suppliers,
        ]);
    }

    /**
     * Show single supplier
     * GET /api/procurement/suppliers/{id}
     */
    public function show(int $id): JsonResponse
    {
        $supplier = Supplier::with([
            'contracts',
            'products',
            'purchaseOrders' => function ($query) {
                $query->latest()->limit(10);
            }
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $supplier,
        ]);
    }

    /**
     * Create new supplier
     * POST /api/procurement/suppliers
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'fax' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'tin' => 'nullable|string|max:50',
            'business_registration' => 'nullable|string|max:100',
            'supplier_type' => 'required|in:manufacturer,wholesaler,distributor,importer,local_artisan',
            'payment_terms' => 'required|in:cash_on_delivery,net_7,net_15,net_30,net_60,advance_payment',
            'credit_limit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Generate supplier code
        $lastSupplier = Supplier::latest()->first();
        $supplierCode = 'SUP-' . date('Y') . '-' . str_pad(($lastSupplier?->id ?? 0) + 1, 3, '0', STR_PAD_LEFT);

        $validated['store_id'] = auth()->user()->store_id;
        $validated['supplier_code'] = $supplierCode;
        $validated['status'] = 'active';
        $validated['rating'] = 5.00;
        $validated['country'] = $validated['country'] ?? 'Philippines';

        $supplier = Supplier::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Supplier created successfully',
            'data' => $supplier,
        ], 201);
    }

    /**
     * Update supplier
     * PUT /api/procurement/suppliers/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);

        $validated = $request->validate([
            'supplier_name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'fax' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'tin' => 'nullable|string|max:50',
            'business_registration' => 'nullable|string|max:100',
            'supplier_type' => 'nullable|in:manufacturer,wholesaler,distributor,importer,local_artisan',
            'payment_terms' => 'nullable|in:cash_on_delivery,net_7,net_15,net_30,net_60,advance_payment',
            'credit_limit' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,inactive,blacklisted',
            'notes' => 'nullable|string',
        ]);

        $supplier->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Supplier updated successfully',
            'data' => $supplier,
        ]);
    }

    /**
     * Delete supplier
     * DELETE /api/procurement/suppliers/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);

        // Check if supplier has active purchase orders
        $activePOs = $supplier->purchaseOrders()
            ->whereIn('status', ['draft', 'pending_approval', 'approved', 'ordered'])
            ->count();

        if ($activePOs > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete supplier with active purchase orders',
            ], 422);
        }

        $supplier->delete();

        return response()->json([
            'success' => true,
            'message' => 'Supplier deleted successfully',
        ]);
    }

    /**
     * Attach products to supplier
     * POST /api/procurement/suppliers/{id}/products
     */
    public function attachProducts(Request $request, int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);

        $validated = $request->validate([
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.supplier_sku' => 'nullable|string|max:100',
            'products.*.supplier_price' => 'required|numeric|min:0',
            'products.*.minimum_order_quantity' => 'nullable|integer|min:1',
            'products.*.lead_time_days' => 'nullable|integer|min:1',
            'products.*.is_preferred_supplier' => 'nullable|boolean',
        ]);

        foreach ($validated['products'] as $product) {
            $supplier->products()->syncWithoutDetaching([
                $product['product_id'] => [
                    'supplier_sku' => $product['supplier_sku'] ?? null,
                    'supplier_price' => $product['supplier_price'],
                    'minimum_order_quantity' => $product['minimum_order_quantity'] ?? 1,
                    'lead_time_days' => $product['lead_time_days'] ?? 7,
                    'is_preferred_supplier' => $product['is_preferred_supplier'] ?? false,
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Products attached to supplier successfully',
            'data' => $supplier->load('products'),
        ]);
    }

    /**
     * Get supplier performance metrics
     * GET /api/procurement/suppliers/{id}/performance
     */
    public function performance(int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);

        $performance = [
            'rating' => $supplier->rating,
            'total_orders' => $supplier->total_orders,
            'total_amount_purchased' => $supplier->total_amount_purchased,
            'average_order_value' => $supplier->average_order_value,
            'on_time_deliveries' => $supplier->on_time_deliveries,
            'late_deliveries' => $supplier->late_deliveries,
            'on_time_delivery_rate' => $supplier->on_time_delivery_rate,
            'current_balance' => $supplier->current_balance,
            'credit_limit' => $supplier->credit_limit,
            'credit_available' => $supplier->credit_limit - $supplier->current_balance,
            'active_contracts' => $supplier->contracts()->active()->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $performance,
        ]);
    }

    /**
     * Update supplier rating
     * POST /api/procurement/suppliers/{id}/update-rating
     */
    public function updateRating(int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->updateRating();

        return response()->json([
            'success' => true,
            'message' => 'Supplier rating updated successfully',
            'data' => [
                'rating' => $supplier->rating,
                'on_time_delivery_rate' => $supplier->on_time_delivery_rate,
            ],
        ]);
    }
}