<?php
// backend/app/Http/Controllers/Api/Procurement/DashboardController.php

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Supplier\Supplier;
use App\Models\Procurement\Requisition\PurchaseRequisition;
use App\Models\Procurement\RFQ\RequestForQuotation;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Procurement\Receiving\GoodsReceipt;
use App\Models\Procurement\Supplier\SupplierPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function getStats(Request $request)
    {
        $request->validate([
            'date_range' => 'nullable|in:today,week,month,quarter,year',
        ]);

        $dateRange = $request->date_range ?? 'month';
        $startDate = $this->getStartDate($dateRange);
        $endDate = now();

        // Get suppliers stats
        $suppliersStats = Supplier::selectRaw('
            COUNT(*) as total_suppliers,
            SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_suppliers,
            SUM(total_orders) as total_orders,
            SUM(total_amount_purchased) as total_purchased,
            AVG(rating) as average_rating
        ')->first();

        // Get purchase requisitions stats
        $prStats = PurchaseRequisition::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as total_prs,
                SUM(CASE WHEN status = "submitted" THEN 1 ELSE 0 END) as pending_prs,
                SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_prs,
                SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected_prs,
                SUM(CASE WHEN status = "converted_to_po" THEN 1 ELSE 0 END) as converted_prs,
                SUM(estimated_amount) as total_estimated_amount
            ')->first();

        // Get RFQ stats
        $rfqStats = RequestForQuotation::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as total_rfqs,
                SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as active_rfqs,
                SUM(CASE WHEN status = "quotes_received" THEN 1 ELSE 0 END) as pending_evaluation,
                SUM(CASE WHEN status = "awarded" THEN 1 ELSE 0 END) as awarded_rfqs
            ')->first();

        // Get purchase orders stats
        $poStats = PurchaseOrder::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as total_pos,
                SUM(CASE WHEN status IN ("pending_approval", "partially_approved") THEN 1 ELSE 0 END) as pending_approval,
                SUM(CASE WHEN status = "ordered" THEN 1 ELSE 0 END) as active_pos,
                SUM(CASE WHEN status = "received" THEN 1 ELSE 0 END) as completed_pos,
                SUM(total_amount) as total_po_value,
                SUM(CASE WHEN payment_status = "paid" THEN total_amount ELSE 0 END) as total_paid,
                SUM(CASE WHEN payment_status = "pending" THEN total_amount ELSE 0 END) as total_pending_payment
            ')->first();

        // Get goods receipts stats
        $grnStats = GoodsReceipt::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as total_grns,
                SUM(CASE WHEN receipt_status = "full" THEN 1 ELSE 0 END) as full_receipts,
                SUM(CASE WHEN receipt_status = "partial" THEN 1 ELSE 0 END) as partial_receipts,
                SUM(CASE WHEN receipt_status = "damaged" THEN 1 ELSE 0 END) as damaged_receipts
            ')->first();

        // Get payments stats
        $paymentsStats = SupplierPayment::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as total_payments,
                SUM(CASE WHEN status = "pending_approval" THEN 1 ELSE 0 END) as pending_approval,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_payments,
                SUM(payment_amount) as total_amount_paid
            ')->first();

        // Get top suppliers by purchase value
        $topSuppliers = Supplier::select('id', 'supplier_name', 'total_amount_purchased', 'total_orders', 'rating')
            ->orderByDesc('total_amount_purchased')
            ->limit(5)
            ->get();

        // Get purchase trends (last 30 days)
        $purchaseTrends = PurchaseOrder::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as total_amount')
            )
            ->whereBetween('created_at', [now()->subDays(30), now()])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get POs by status
        $posByStatus = PurchaseOrder::select('status')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(total_amount) as total_amount')
            ->groupBy('status')
            ->get();

        // Get payment methods breakdown
        $paymentMethods = SupplierPayment::select('payment_method')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(payment_amount) as total_amount')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('payment_method')
            ->get();

        // Get pending approvals count
        $pendingApprovals = PurchaseRequisition::whereIn('status', ['submitted', 'warehouse_approved'])
            ->count() 
            + PurchaseOrder::whereIn('status', ['pending_approval', 'partially_approved'])
            ->count();

        // Get overdue POs
        $overduePOs = PurchaseOrder::where('status', 'ordered')
            ->whereNotNull('expected_delivery_date')
            ->where('expected_delivery_date', '<', now())
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'suppliers' => [
                    'total' => (int) $suppliersStats->total_suppliers,
                    'active' => (int) $suppliersStats->active_suppliers,
                    'total_orders' => (int) $suppliersStats->total_orders,
                    'total_purchased' => (float) $suppliersStats->total_purchased,
                    'average_rating' => round($suppliersStats->average_rating, 2),
                ],
                'purchase_requisitions' => [
                    'total' => (int) $prStats->total_prs,
                    'pending' => (int) $prStats->pending_prs,
                    'approved' => (int) $prStats->approved_prs,
                    'rejected' => (int) $prStats->rejected_prs,
                    'converted' => (int) $prStats->converted_prs,
                    'total_estimated' => (float) $prStats->total_estimated_amount,
                ],
                'rfqs' => [
                    'total' => (int) $rfqStats->total_rfqs,
                    'active' => (int) $rfqStats->active_rfqs,
                    'pending_evaluation' => (int) $rfqStats->pending_evaluation,
                    'awarded' => (int) $rfqStats->awarded_rfqs,
                ],
                'purchase_orders' => [
                    'total' => (int) $poStats->total_pos,
                    'pending_approval' => (int) $poStats->pending_approval,
                    'active' => (int) $poStats->active_pos,
                    'completed' => (int) $poStats->completed_pos,
                    'total_value' => (float) $poStats->total_po_value,
                    'total_paid' => (float) $poStats->total_paid,
                    'total_pending_payment' => (float) $poStats->total_pending_payment,
                    'overdue' => $overduePOs,
                ],
                'goods_receipts' => [
                    'total' => (int) $grnStats->total_grns,
                    'full' => (int) $grnStats->full_receipts,
                    'partial' => (int) $grnStats->partial_receipts,
                    'damaged' => (int) $grnStats->damaged_receipts,
                ],
                'payments' => [
                    'total' => (int) $paymentsStats->total_payments,
                    'pending_approval' => (int) $paymentsStats->pending_approval,
                    'completed' => (int) $paymentsStats->completed_payments,
                    'total_amount' => (float) $paymentsStats->total_amount_paid,
                ],
                'pending_approvals' => $pendingApprovals,
                'top_suppliers' => $topSuppliers,
                'purchase_trends' => $purchaseTrends,
                'pos_by_status' => $posByStatus,
                'payment_methods' => $paymentMethods,
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'range' => $dateRange,
                ],
            ],
        ]);
    }

    /**
     * Get summary cards data
     */
    public function getSummaryCards(Request $request)
    {
        $dateRange = $request->date_range ?? 'month';
        $startDate = $this->getStartDate($dateRange);

        // Active POs
        $activePOs = PurchaseOrder::where('status', 'ordered')->count();
        $activePOsValue = PurchaseOrder::where('status', 'ordered')->sum('total_amount');

        // Pending approvals
        $pendingPRs = PurchaseRequisition::whereIn('status', ['submitted', 'warehouse_approved'])->count();
        $pendingPOs = PurchaseOrder::whereIn('status', ['pending_approval', 'partially_approved'])->count();

        // Pending payments
        $pendingPaymentsCount = SupplierPayment::where('status', 'pending_approval')->count();
        $pendingPaymentsValue = SupplierPayment::where('status', 'pending_approval')->sum('payment_amount');

        // Active suppliers
        $activeSuppliers = Supplier::where('status', 'active')->count();
        $avgRating = Supplier::where('status', 'active')->avg('rating');

        // POs this period
        $posThisPeriod = PurchaseOrder::whereBetween('created_at', [$startDate, now()])->count();
        $posValue = PurchaseOrder::whereBetween('created_at', [$startDate, now()])->sum('total_amount');

        return response()->json([
            'success' => true,
            'data' => [
                [
                    'title' => 'Active Purchase Orders',
                    'value' => $activePOs,
                    'icon' => 'pi pi-shopping-cart',
                    'color' => 'blue',
                    'subtitle' => '₱' . number_format($activePOsValue, 2),
                    'trend' => $posThisPeriod,
                    'trend_label' => "this {$dateRange}",
                ],
                [
                    'title' => 'Pending Approvals',
                    'value' => $pendingPRs + $pendingPOs,
                    'icon' => 'pi pi-clock',
                    'color' => 'orange',
                    'subtitle' => "{$pendingPRs} PRs, {$pendingPOs} POs",
                    'clickable' => true,
                    'route' => '/procurement/approvals',
                ],
                [
                    'title' => 'Pending Payments',
                    'value' => $pendingPaymentsCount,
                    'icon' => 'pi pi-money-bill',
                    'color' => 'red',
                    'subtitle' => '₱' . number_format($pendingPaymentsValue, 2),
                    'clickable' => true,
                    'route' => '/procurement/payments?status=pending',
                ],
                [
                    'title' => 'Active Suppliers',
                    'value' => $activeSuppliers,
                    'icon' => 'pi pi-users',
                    'color' => 'green',
                    'subtitle' => 'Avg rating: ' . number_format($avgRating, 1) . '★',
                ],
                [
                    'title' => 'Total Purchase Value',
                    'value' => '₱' . number_format($posValue / 1000, 1) . 'K',
                    'icon' => 'pi pi-chart-line',
                    'color' => 'purple',
                    'subtitle' => "this {$dateRange}",
                ],
            ],
        ]);
    }

    /**
     * Helper to get start date based on range
     */
    private function getStartDate(string $range): Carbon
    {
        return match($range) {
            'today' => now()->startOfDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'quarter' => now()->subMonths(3),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };
    }
}