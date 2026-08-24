<?php

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Supplier\Supplier;
use App\Models\Procurement\Requisition\PurchaseRequisition;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Procurement\Supplier\SupplierPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     * GET /api/procurement/suppliers/stats
     */
    public function getStats(Request $request)
    {
        try {
            $user = auth()->user();
            $storeId = (int) ($user?->store_id ?? 0);
            $branchId = (int) ($user?->branch_id ?? 0);

            if ($storeId <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is not assigned to a store.',
                ], 422);
            }

            $scopePurchaseOrders = function ($query) use ($storeId, $branchId) {
                $query->where('purchase_orders.store_id', $storeId);
                if ($branchId > 0) {
                    $query->where('purchase_orders.branch_id', $branchId);
                }
            };

            $scopeRequisitions = function ($query) use ($storeId, $branchId) {
                $query->where('purchase_requisitions.store_id', $storeId);
                if ($branchId > 0) {
                    $query->where('purchase_requisitions.branch_id', $branchId);
                }
            };

            // ===== SUMMARY CARDS =====
            $activeSuppliersCount = Supplier::where('store_id', $storeId)->where('status', 'active')->count();
            $totalSuppliersCount = Supplier::where('store_id', $storeId)->count();
        
            $pendingPRCount = PurchaseRequisition::where($scopeRequisitions)
                ->where('status', 'submitted')
                ->count();
            $pendingPOApprovalsCount = PurchaseOrder::where($scopePurchaseOrders)
                ->whereIn('status', ['pending_finance_approval'])
                ->count();
            $totalPendingApprovals = $pendingPRCount + $pendingPOApprovalsCount;

            $activePOs = PurchaseOrder::where($scopePurchaseOrders)
                ->whereIn('status', ['sent_to_supplier', 'supplier_accepted', 'in_transit']);
            $activePOsCount = (clone $activePOs)->count();
            $activePOsValue = (clone $activePOs)->sum('total_amount') ?? 0;
            $totalPOsValue = PurchaseOrder::where($scopePurchaseOrders)->sum('total_amount') ?? 0;

            $pendingPaymentsCount = 0;
            $pendingPaymentsAmount = 0;
            try {
                $pendingPayments = SupplierPayment::where('store_id', $storeId)
                    ->where('status', 'pending_approval')
                    ->when($branchId > 0, function ($query) use ($branchId) {
                        $query->whereHas('purchaseOrder', fn ($po) => $po->where('branch_id', $branchId));
                    });
                $pendingPaymentsCount = (clone $pendingPayments)->count();
                $pendingPaymentsAmount = (clone $pendingPayments)->sum('payment_amount') ?? 0;
            } catch (\Exception $e) {
                \Log::warning('Error fetching pending payments: ' . $e->getMessage());
            }

            // ===== TOP SUPPLIERS =====
            $topSuppliers = Supplier::select('id', 'supplier_name', 'supplier_code', 'rating')
                ->withCount(['purchaseOrders as scoped_total_orders' => $scopePurchaseOrders])
                ->withSum(['purchaseOrders as scoped_total_spent' => $scopePurchaseOrders], 'total_amount')
                ->where('store_id', $storeId)
                ->where('status', 'active')
                ->orderByDesc('scoped_total_spent')
                ->limit(5)
                ->get()
                ->map(function ($supplier) {
                    return [
                        'id' => $supplier->id,
                        'name' => $supplier->supplier_name,
                        'code' => $supplier->supplier_code,
                        'rating' => round($supplier->rating, 1),
                        'total_orders' => (int) $supplier->scoped_total_orders,
                        'total_spent' => (float) ($supplier->scoped_total_spent ?? 0),
                    ];
                });

            // ===== RECENT PURCHASE ORDERS =====
            $recentPOs = PurchaseOrder::select('id', 'po_number', 'supplier_id', 'total_amount', 'status', 'created_at')
                ->where($scopePurchaseOrders)
                ->with('supplier:id,supplier_name')
                ->latest('created_at')
                ->limit(5)
                ->get()
                ->map(function ($po) {
                    return [
                        'id' => $po->id,
                        'number' => $po->po_number,
                        'supplier' => $po->supplier?->supplier_name,
                        'amount' => (float) $po->total_amount,
                        'status' => $po->status,
                        'date' => $po->created_at->format('Y-m-d'),
                    ];
                });

            // ===== PO STATUS BREAKDOWN =====
            $poStatusBreakdown = PurchaseOrder::where($scopePurchaseOrders)
                ->select('status')
                ->selectRaw('COUNT(*) as count')
                ->selectRaw('SUM(total_amount) as total')
                ->groupBy('status')
                ->get()
                ->map(function ($po) {
                    return [
                        'status' => $po->status,
                        'count' => (int) $po->count,
                        'total' => (float) ($po->total ?? 0),
                    ];
                });

            // ===== KEY METRICS =====
            $completedPOs = PurchaseOrder::where($scopePurchaseOrders)->where('status', 'delivered')->count();
            $avgSupplierRating = Supplier::where('store_id', $storeId)->where('status', 'active')->avg('rating') ?? 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'active_suppliers' => [
                            'count' => $activeSuppliersCount,
                            'total' => $totalSuppliersCount,
                            'label' => 'Active Suppliers'
                        ],
                        'pending_approvals' => [
                            'pr_count' => $pendingPRCount,
                            'po_count' => $pendingPOApprovalsCount,
                            'total' => $totalPendingApprovals,
                            'label' => 'Pending Approvals'
                        ],
                        'active_pos' => [
                            'count' => $activePOsCount,
                            'total_value' => (float) $activePOsValue,
                            'label' => 'Active POs'
                        ],
                        'pending_payments' => [
                            'count' => $pendingPaymentsCount,
                            'total_amount' => (float) $pendingPaymentsAmount,
                            'label' => 'Pending Payments'
                        ]
                    ],
                    'metrics' => [
                        'total_po_value' => (float) $totalPOsValue,
                        'completed_pos' => $completedPOs,
                        'avg_supplier_rating' => round($avgSupplierRating, 1),
                    ],
                    'top_suppliers' => $topSuppliers,
                    'recent_pos' => $recentPOs,
                    'po_status_breakdown' => $poStatusBreakdown,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard Stats Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get summary cards data (fallback)
     * GET /api/procurement/suppliers/summary-cards
     */
    public function getSummaryCards(Request $request)
    {
        return $this->getStats($request);
    }
}
