<?php

namespace App\Http\Controllers\Api\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::query();

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        // Search by supplier name or company name
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('supplier_name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        // Sort by rating or name
        $sortBy = $request->get('sort_by', 'supplier_name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $suppliers = $query->paginate($request->get('per_page', 15));

        return response()->json($suppliers);
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');

        $suppliers = Supplier::where(function ($q) use ($query) {
                $q->where('supplier_name', 'like', "%{$query}%")
                    ->orWhere('company_name', 'like', "%{$query}%");
            })
            ->where('status', 'active')
            ->limit(10)
            ->get(['id', 'supplier_name', 'company_name', 'email', 'phone', 'rating']);

        return response()->json($suppliers);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_name' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'postal_code' => 'required|string',
            'country' => 'required|string',
            'payment_terms' => 'required|string',
            'tax_id' => 'nullable|string',
            'category' => 'required|in:raw_materials,furniture,accessories,services',
            'bank_details' => 'nullable|string',
        ]);

        try {
            $supplier = Supplier::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Supplier created successfully',
                'data' => $supplier
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create supplier: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $supplier = Supplier::with([
                'performanceMetrics' => function ($q) {
                    $q->orderBy('metric_date', 'desc')->limit(12);
                }
            ])->findOrFail($id);

            return response()->json($supplier);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier not found'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'supplier_name' => 'sometimes|string|max:255',
            'company_name' => 'sometimes|string|max:255',
            'contact_person' => 'sometimes|string|max:255',
            'email' => 'sometimes|email',
            'phone' => 'sometimes|string',
            'address' => 'sometimes|string',
            'city' => 'sometimes|string',
            'state' => 'sometimes|string',
            'postal_code' => 'sometimes|string',
            'country' => 'sometimes|string',
            'payment_terms' => 'sometimes|string',
            'tax_id' => 'nullable|string',
            'category' => 'sometimes|in:raw_materials,furniture,accessories,services',
            'bank_details' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive,blacklisted',
        ]);

        try {
            $supplier = Supplier::findOrFail($id);
            $supplier->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Supplier updated successfully',
                'data' => $supplier
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update supplier: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);

            // Check if supplier has active purchase orders
            $activeOrders = DB::table('purchase_orders')
                ->where('supplier_id', $id)
                ->where('status', '!=', 'cancelled')
                ->count();

            if ($activeOrders > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete supplier with active purchase orders'
                ], 400);
            }

            $supplier->delete();

            return response()->json([
                'success' => true,
                'message' => 'Supplier deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete supplier: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getByCategory($category)
    {
        $suppliers = Supplier::where('category', $category)
            ->where('status', 'active')
            ->get();

        return response()->json($suppliers);
    }
}
