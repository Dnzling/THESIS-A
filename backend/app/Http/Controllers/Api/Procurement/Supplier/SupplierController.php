<?php
// backend/app/Http/Controllers/Procurement/Supplier/SupplierController.php

namespace App\Http\Controllers\Api\Procurement\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Supplier\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    /**
     * List all suppliers
     * GET /api/procurement/suppliers
     */
    public function index(Request $request): JsonResponse
    {
        $query = Supplier::where('store_id', Auth::user()->store_id);

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

        // Generate supplier code using datetime for uniqueness
        $supplierCode = 'SUP-' . date('YmdHis') . '-' . str_pad(random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

        $validated['store_id'] = Auth::user()->store_id;
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
            ->whereIn('status', ['draft', 'pending_finance_approval', 'approved', 'sent_to_supplier', 'supplier_accepted', 'in_transit'])
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

    /**
     * Manage supplier contacts
     * GET /api/procurement/suppliers/{id}/contacts
     */
    public function getContacts(int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);
        $contacts = $supplier->contacts()->orderBy('is_primary', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $contacts,
        ]);
    }

    /**
     * Add supplier contact
     * POST /api/procurement/suppliers/{id}/contacts
     */
    public function addContact(Request $request, int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);

        $validated = $request->validate([
            'contact_name' => 'required|string|max:255',
            'contact_title' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'fax' => 'nullable|string|max:50',
            'contact_type' => 'required|in:Sales,Technical,Support,Billing,Logistics',
            'preferred_contact_method' => 'nullable|in:Email,Phone,Mobile,WhatsApp,Fax',
            'is_primary' => 'nullable|boolean',
            'is_emergency_contact' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $contact = $supplier->contacts()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Contact added successfully',
            'data' => $contact,
        ], 201);
    }

    /**
     * Update supplier contact
     * PUT /api/procurement/suppliers/{id}/contacts/{contactId}
     */
    public function updateContact(Request $request, int $id, int $contactId): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);
        $contact = $supplier->contacts()->findOrFail($contactId);

        $validated = $request->validate([
            'contact_name' => 'nullable|string|max:255',
            'contact_title' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'fax' => 'nullable|string|max:50',
            'contact_type' => 'nullable|in:Sales,Technical,Support,Billing,Logistics',
            'preferred_contact_method' => 'nullable|in:Email,Phone,Mobile,WhatsApp,Fax',
            'is_primary' => 'nullable|boolean',
            'is_emergency_contact' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $contact->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Contact updated successfully',
            'data' => $contact,
        ]);
    }

    /**
     * Delete supplier contact
     * DELETE /api/procurement/suppliers/{id}/contacts/{contactId}
     */
    public function deleteContact(int $id, int $contactId): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);
        $contact = $supplier->contacts()->findOrFail($contactId);
        $contact->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contact deleted successfully',
        ]);
    }

    /**
     * Get supplier pricing history
     * GET /api/procurement/suppliers/{id}/pricing-history
     */
    public function getPricingHistory(Request $request, int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);
        
        $query = $supplier->priceHistory();

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        $prices = $query->with('product:id,product_name,sku')
            ->orderBy('effective_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $prices,
        ]);
    }

    /**
     * Update supplier pricing
     * POST /api/procurement/suppliers/{id}/update-price
     */
    public function updatePrice(Request $request, int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'unit_price' => 'required|numeric|min:0',
            'minimum_order_quantity' => 'nullable|integer|min:1',
            'lead_time_days' => 'nullable|integer|min:1',
            'pack_size' => 'nullable|integer|min:1',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:effective_date',
        ]);

        // Create new price record (deactivate old one if exists)
        \App\Models\Procurement\Supplier\SupplierPrice::where('supplier_id', $id)
            ->where('product_id', $validated['product_id'])
            ->active()
            ->update(['is_active' => false]);

        $price = \App\Models\Procurement\Supplier\SupplierPrice::create([
            'supplier_id' => $id,
            'product_id' => $validated['product_id'],
            'unit_price' => $validated['unit_price'],
            'minimum_order_quantity' => $validated['minimum_order_quantity'] ?? 1,
            'lead_time_days' => $validated['lead_time_days'] ?? 7,
            'pack_size' => $validated['pack_size'] ?? 1,
            'effective_date' => $validated['effective_date'] ?? now(),
            'expiry_date' => $validated['expiry_date'],
            'is_active' => true,
            'currency' => 'PHP',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Supplier price updated successfully',
            'data' => $price,
        ], 201);
    }

    /**
     * Blacklist supplier
     * POST /api/procurement/suppliers/{id}/blacklist
     */
    public function blacklist(Request $request, int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|in:quality_issues,late_delivery,non_payment,other',
            'notes' => 'nullable|string',
        ]);

        $supplier->update([
            'status' => 'blacklisted',
        ]);

        // Log the blacklist action
        \Illuminate\Support\Facades\Log::info("Supplier blacklisted: {$supplier->supplier_name}", [
            'supplier_id' => $id,
            'reason' => $validated['reason'],
            'notes' => $validated['notes'],
            'blacklisted_by' => Auth::id(),
            'blacklisted_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Supplier blacklisted successfully',
            'data' => $supplier,
        ]);
    }

    /**
     * Activate blacklisted supplier
     * POST /api/procurement/suppliers/{id}/activate
     */
    public function activate(int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);

        if ($supplier->status !== 'blacklisted') {
            return response()->json([
                'success' => false,
                'message' => 'Supplier is not blacklisted',
            ], 422);
        }

        $supplier->update(['status' => 'active']);

        return response()->json([
            'success' => true,
            'message' => 'Supplier activated successfully',
            'data' => $supplier,
        ]);
    }
}
