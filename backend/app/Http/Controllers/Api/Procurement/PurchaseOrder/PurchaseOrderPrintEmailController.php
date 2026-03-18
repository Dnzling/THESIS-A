<?php
// backend/app/Http/Controllers/Api/Procurement/PurchaseOrder/PurchaseOrderPrintEmailController.php
// Add these methods to PurchaseOrderController or create this as a separate controller

namespace App\Http\Controllers\Api\Procurement\PurchaseOrder;

use App\Http\Controllers\Controller;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Core\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use PDF; // or any PDF library

class PurchaseOrderPrintEmailController extends Controller
{
    /**
     * Generate PDF for purchase order
     * GET /api/procurement/purchase-orders/{id}/print
     */
    public function generatePdf(int $id): JsonResponse
    {
        try {
            $po = PurchaseOrder::with([
                'branch',
                'supplier',
                'items.product',
                'approvals',
            ])->findOrFail($id);

            // Prepare data for PDF
            $data = [
                'po_number' => $po->po_number,
                'order_date' => $po->order_date,
                'expected_delivery_date' => $po->expected_delivery_date,
                'supplier' => [
                    'supplier_name' => $po->supplier->supplier_name,
                    'contact_person' => $po->supplier->contact_person,
                    'email' => $po->supplier->email,
                    'phone' => $po->supplier->phone,
                    'address' => $po->supplier->address,
                ],
                'branch' => [
                    'branch_name' => $po->branch->branch_name ?? 'Main Branch',
                    'address' => $po->branch->address ?? '',
                ],
                'items' => $po->items->map(fn($item) => [
                    'product_name' => $item->product->product_name,
                    'product_id' => $item->product_id,
                    'quantity_ordered' => $item->quantity_ordered,
                    'unit_cost' => $item->unit_cost,
                    'tax_rate' => $item->tax_rate,
                    'discount_percent' => $item->discount_percent,
                    'line_total' => $item->line_total,
                ])->toArray(),
                'subtotal' => $po->subtotal,
                'tax_total' => $po->tax_amount,
                'shipping_cost' => $po->shipping_cost,
                'discount_amount' => $po->discount_amount,
                'total_amount' => $po->total_amount,
                'payment_terms' => $po->payment_terms,
                'notes' => $po->notes,
                'approver_name' => $po->approvals->first()?->approver_name,
                'status' => $po->status,
            ];

            // Generate PDF (using DomPDF or similar)
            $pdf = \PDF::loadView('procurement.purchase-order-pdf', $data);
            
            return response()->json([
                'success' => true,
                'data' => base64_encode($pdf->output()),
                'filename' => "PO-{$po->po_number}.pdf",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Email purchase order to supplier
     * POST /api/procurement/purchase-orders/{id}/email
     */
    public function emailPo(Request $request, int $id): JsonResponse
    {
        try {
            $po = PurchaseOrder::with('supplier')->findOrFail($id);

            $validated = $request->validate([
                'recipient_email' => 'required|email',
                'subject' => 'required|string',
                'message' => 'nullable|string',
            ]);

            // Generate PDF
            $pdf = $this->generatePoDigitalFile($po);

            // Send email with attachment
            \Mail::send('emails.po-notification', [
                'po' => $po,
                'message' => $validated['message'],
            ], function ($message) use ($validated, $pdf, $po) {
                $message->to($validated['recipient_email'])
                    ->subject($validated['subject'])
                    ->attachData($pdf, "PO-{$po->po_number}.pdf");
            });

            ActivityLog::record(
                'po_email_sent',
                "PO emailed to {$validated['recipient_email']}",
                [
                    'po_number' => $po->po_number,
                    'recipient_email' => $validated['recipient_email'],
                    'subject' => $validated['subject'],
                ],
                'purchase_order',
                $po->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Purchase order emailed successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate warehouse receiving label
     * GET /api/procurement/purchase-orders/{id}/label
     */
    public function generateLabel(int $id): JsonResponse
    {
        try {
            $po = PurchaseOrder::with('supplier', 'items.product')->findOrFail($id);

            $data = [
                'po_number' => $po->po_number,
                'supplier_name' => $po->supplier->supplier_name,
                'item_count' => $po->items->count(),
                'total_amount' => $po->total_amount,
                'expected_items' => $po->items->map(fn($item) => [
                    'product_name' => $item->product->product_name,
                    'quantity' => $item->quantity_ordered,
                ])->toArray(),
            ];

            // Generate PDF label
            $pdf = \PDF::loadView('procurement.warehouse-label-pdf', $data)
                ->setPaper('a6', 'portrait');
            
            return response()->json([
                'success' => true,
                'data' => base64_encode($pdf->output()),
                'filename' => "LABEL-{$po->po_number}.pdf",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate label',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get frequently purchased products for quick add
     * GET /api/procurement/products/history
     */
    public function getProductHistory(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);

            $products = \DB::table('purchase_order_items')
                ->join('products', 'purchase_order_items.product_id', '=', 'products.id')
                ->leftJoin('product_assets as main_assets', function ($join) {
                    $join->on('purchase_order_items.product_id', '=', 'main_assets.product_id')
                        ->where('main_assets.asset_type', '=', 'Image_Main')
                        ->where('main_assets.is_primary', '=', 1);
                })
                ->select(
                    'purchase_order_items.product_id',
                    'products.product_name',
                    \DB::raw('COUNT(*) as purchase_count'),
                    \DB::raw('MAX(purchase_order_items.unit_cost) as unit_cost'),
                    \DB::raw('MAX(main_assets.file_path) as product_image')
                )
                ->groupBy('purchase_order_items.product_id', 'products.product_name')
                ->orderByDesc('purchase_count')
                ->limit($limit)
                ->get();

            $data = $products->map(fn($product) => [
                'product_id' => $product->product_id,
                'product_name' => $product->product_name,
                'product_image' => $product->product_image,
                'quantity_ordered' => 1,
                'last_price' => $product->unit_cost,
                'purchase_frequency' => $product->purchase_count,
            ])->toArray();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get product history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get supplier delivery history
     * GET /api/procurement/suppliers/{id}/delivery-history
     */
    public function getSupplierDeliveryHistory(int $supplierId): JsonResponse
    {
        try {
            $orders = PurchaseOrder::where('supplier_id', $supplierId)
                ->where('status', 'delivered')
                ->orderByDesc('expected_delivery_date')
                ->limit(10)
                ->get();

            $averageDeliveryDays = $orders->avg(function ($order) {
                if ($order->received_date && $order->expected_delivery_date) {
                    return \Carbon\Carbon::parse($order->received_date)
                        ->diffInDays(\Carbon\Carbon::parse($order->expected_delivery_date));
                }
                return 0;
            });

            $onTimeDeliveries = $orders->filter(function ($order) {
                if ($order->received_date && $order->expected_delivery_date) {
                    return \Carbon\Carbon::parse($order->received_date)
                        ->lte(\Carbon\Carbon::parse($order->expected_delivery_date));
                }
                return false;
            })->count();

            $lateDeliveryPercent = $orders->count() > 0 
                ? round((($orders->count() - $onTimeDeliveries) / $orders->count()) * 100)
                : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'average_delivery_days' => round($averageDeliveryDays),
                    'on_time_deliveries' => $onTimeDeliveries,
                    'total_deliveries' => $orders->count(),
                    'late_delivery_percentage' => $lateDeliveryPercent,
                    'recent_orders' => $orders->toArray(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get delivery history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get approved purchase orders (for goods receipt)
     * GET /api/procurement/purchase-orders/approved
     */
    public function getApprovedOrders(Request $request): JsonResponse
    {
        try {
            $query = PurchaseOrder::with('supplier', 'branch', 'items.product')
                ->where('store_id', auth()->user()->store_id)
                ->whereIn('status', ['approved', 'sent_to_supplier', 'supplier_accepted', 'in_transit']);

            if ($request->has('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }

            $orders = $query->orderByDesc('created_at')->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $orders,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get approved orders',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Request revision on purchase order
     * POST /api/procurement/purchase-orders/{id}/request-revision
     */
    public function requestRevision(Request $request, int $id): JsonResponse
    {
        try {
            $po = PurchaseOrder::findOrFail($id);

            $validated = $request->validate([
                'comments' => 'required|string',
            ]);

            // Add revision note
            $po->revisions()->create([
                'requested_by' => auth()->id(),
                'comments' => $validated['comments'],
                'status' => 'pending',
            ]);

            // Update PO status
            $po->update(['status' => 'revision_requested']);

            return response()->json([
                'success' => true,
                'message' => 'Revision requested successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to request revision',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper method to generate PO digital file
     */
    private function generatePoDigitalFile($po)
    {
        $data = [
            'po_number' => $po->po_number,
            'order_date' => $po->order_date,
            'expected_delivery_date' => $po->expected_delivery_date,
            'supplier' => $po->supplier,
            'branch' => $po->branch,
            'items' => $po->items,
            'subtotal' => $po->subtotal,
            'tax_total' => $po->tax_amount,
            'shipping_cost' => $po->shipping_cost,
            'discount_amount' => $po->discount_amount,
            'total_amount' => $po->total_amount,
        ];

        return \PDF::loadView('procurement.purchase-order-pdf', $data)->output();
    }

    /**
     * Get alternative suppliers for a product
     * GET /api/procurement/products/{productId}/alternative-suppliers
     */
    public function getAlternativeSuppliers(Request $request, int $productId): JsonResponse
    {
        try {
            // Get suppliers who have sold this product, ordered by rating and delivery performance
            $alternatives = \DB::table('purchase_order_items')
                ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
                ->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
                ->where('purchase_order_items.product_id', $productId)
                ->select(
                    'suppliers.id',
                    'suppliers.supplier_name',
                    'suppliers.contact_person',
                    'suppliers.email',
                    'suppliers.phone',
                    'suppliers.rating',
                    \DB::raw('AVG(purchase_order_items.unit_cost) as avg_price'),
                    \DB::raw('COUNT(*) as purchase_count')
                )
                ->where('suppliers.status', 'active')
                ->groupBy('suppliers.id', 'suppliers.supplier_name', 'suppliers.contact_person', 'suppliers.email', 'suppliers.phone', 'suppliers.rating')
                ->orderByDesc('suppliers.rating')
                ->limit(5)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $alternatives,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get alternative suppliers',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get branch budget information
     * GET /api/procurement/branches/{branchId}/budget
     */
    public function getBranchBudget(int $branchId): JsonResponse
    {
        try {
            // Get branch budget info
            $branch = \App\Models\Store\Branch::findOrFail($branchId);
            
            // Calculate current month spending
            $currentMonthSpending = PurchaseOrder::where('branch_id', $branchId)
                ->whereMonth('order_date', now()->month)
                ->whereYear('order_date', now()->year)
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount');

            $yearlyBudget = $branch->yearly_budget ?? 50000; // Default if not set
            $monthlyBudget = $yearlyBudget / 12;

            return response()->json([
                'success' => true,
                'data' => [
                    'monthly_budget' => $monthlyBudget,
                    'current_month_spending' => $currentMonthSpending,
                    'remaining_budget' => $monthlyBudget - $currentMonthSpending,
                    'budget_percentage_used' => round(($currentMonthSpending / $monthlyBudget) * 100),
                    'is_over_budget' => $currentMonthSpending > $monthlyBudget,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get branch budget',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
