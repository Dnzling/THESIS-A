<?php

namespace App\Http\Controllers\Api\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class SupplierRecommendationController extends Controller
{
    public function getRecommendedSuppliers($productId)
    {
        try {
            // Get product's category from products table
            $product = DB::table('products')->find($productId);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            // Find suppliers by category with best performance
            $suppliers = Supplier::where('status', 'active')
                ->where('category', '!=', null)
                ->orderBy('quality_score', 'desc')
                ->orderBy('rating', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($supplier) {
                    return [
                        'id' => $supplier->id,
                        'supplier_name' => $supplier->supplier_name,
                        'company_name' => $supplier->company_name,
                        'contact_person' => $supplier->contact_person,
                        'email' => $supplier->email,
                        'phone' => $supplier->phone,
                        'payment_terms' => $supplier->payment_terms,
                        'quality_score' => $supplier->quality_score,
                        'rating' => $supplier->rating,
                        'on_time_percentage' => $supplier->total_orders > 0 
                            ? round(($supplier->on_time_deliveries / $supplier->total_orders) * 100, 2)
                            : 0,
                        'average_delivery_days' => $supplier->average_delivery_days,
                        'category' => $supplier->category,
                        'is_recommended' => true
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $suppliers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get recommendations: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getSuppliersForCategory($category)
    {
        try {
            $suppliers = Supplier::where('status', 'active')
                ->where('category', $category)
                ->orderBy('quality_score', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $suppliers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get suppliers: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAlternativeSuppliers($supplierId, $category = null)
    {
        try {
            $currentSupplier = Supplier::findOrFail($supplierId);

            $query = Supplier::where('status', 'active')
                ->where('id', '!=', $supplierId);

            if ($category) {
                $query->where('category', $category);
            } elseif ($currentSupplier->category) {
                $query->where('category', $currentSupplier->category);
            }

            $alternatives = $query
                ->orderBy('quality_score', 'desc')
                ->orderBy('rating', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($supplier) use ($currentSupplier) {
                    return [
                        'id' => $supplier->id,
                        'supplier_name' => $supplier->supplier_name,
                        'company_name' => $supplier->company_name,
                        'quality_score' => $supplier->quality_score,
                        'rating' => $supplier->rating,
                        'average_delivery_days' => $supplier->average_delivery_days,
                        'quality_difference' => round($supplier->quality_score - $currentSupplier->quality_score, 2),
                        'delivery_advantage' => $currentSupplier->average_delivery_days - $supplier->average_delivery_days . ' days',
                        'contact_person' => $supplier->contact_person,
                        'email' => $supplier->email,
                        'phone' => $supplier->phone
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $alternatives
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get alternatives: ' . $e->getMessage()
            ], 500);
        }
    }
}
