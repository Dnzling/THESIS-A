<?php

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Procurement\Requisition\PurchaseRequisition;
use App\Models\Procurement\RFQ\RequestForQuotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function getStats(Request $request)
    {
        try {
            $storeId = (int) ($request->user()?->store_id ?? 0);
            $branchId = (int) ($request->user()?->branch_id ?? 0);

            if ($storeId <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is not assigned to a store.',
                ], 422);
            }

            $orders = PurchaseOrder::query()->where('store_id', $storeId)
                ->when($branchId > 0, fn ($query) => $query->where('branch_id', $branchId));
            $requisitions = PurchaseRequisition::query()->where('store_id', $storeId)
                ->when($branchId > 0, fn ($query) => $query->where('branch_id', $branchId));
            // RFQs have a store but no branch of their own, so show the store-wide quote queue.
            $rfqs = RequestForQuotation::query()->where('store_id', $storeId);

            $thisMonth = now()->startOfMonth();
            $nextMonth = $thisMonth->copy()->addMonth();
            $lastMonth = $thisMonth->copy()->subMonth();
            $monthlyValue = fn ($start, $end) => (float) (clone $orders)
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end)
                ->whereNotIn('status', ['draft', 'cancelled', 'rejected_finance', 'declined_supplier'])
                ->sum('total_amount');

            $currentValue = $monthlyValue($thisMonth, $nextMonth);
            $previousValue = $monthlyValue($lastMonth, $thisMonth);
            $trend = [];
            for ($offset = 5; $offset >= 0; $offset--) {
                $start = $thisMonth->copy()->subMonths($offset);
                $trend[] = [
                    'label' => $start->format('M'),
                    'value' => $monthlyValue($start, $start->copy()->addMonth()),
                ];
            }

            $pendingPrQuery = (clone $requisitions)->whereIn('status', ['pending', 'submitted', 'pending_central_review']);
            $pendingPoQuery = (clone $orders)->whereIn('status', ['draft', 'pending_finance_approval', 'revision_requested']);
            $quotedRfqQuery = (clone $rfqs)
                ->whereIn('status', ['pending', 'sent', 'receiving', 'partially_approved'])
                ->whereHas('supplierPortalFeedbacks', fn ($query) => $query->where('status', 'pending'));

            $pendingPrs = (clone $pendingPrQuery)
                ->select('id', 'pr_number', 'branch_id', 'estimated_amount', 'priority', 'status', 'submitted_at', 'created_at')
                ->with('branch:id,name')
                ->orderBy('priority')->orderBy('created_at')->limit(5)->get()
                ->map(fn ($pr) => [
                    'id' => $pr->id,
                    'number' => $pr->pr_number,
                    'branch' => $pr->branch?->name,
                    'amount' => (float) ($pr->estimated_amount ?? 0),
                    'priority' => (int) $pr->priority,
                    'status' => $pr->status,
                    'date' => $pr->submitted_at?->toDateString() ?? $pr->created_at?->toDateString(),
                ]);

            $pendingPos = (clone $pendingPoQuery)
                ->select('id', 'po_number', 'supplier_id', 'total_amount', 'status', 'created_at')
                ->with('supplier:id,supplier_name')
                ->oldest('created_at')->limit(5)->get()
                ->map(fn ($po) => [
                    'id' => $po->id,
                    'number' => $po->po_number,
                    'supplier' => $po->supplier?->supplier_name,
                    'amount' => (float) ($po->total_amount ?? 0),
                    'status' => $po->status,
                    'date' => $po->created_at?->toDateString(),
                ]);

            $quotedRfqs = (clone $quotedRfqQuery)
                ->select('id', 'rfq_number', 'title', 'status', 'updated_at')
                ->with(['supplierPortalFeedbacks' => fn ($query) => $query
                    ->select('id', 'rfq_id', 'supplier_portal_id', 'submitted_at')
                    ->where('status', 'pending')])
                ->latest('updated_at')->limit(5)->get()
                ->map(fn ($rfq) => [
                    'id' => $rfq->id,
                    'number' => $rfq->rfq_number,
                    'title' => $rfq->title,
                    'quotes' => $rfq->supplierPortalFeedbacks->pluck('supplier_portal_id')->unique()->count(),
                    'last_quote_at' => $rfq->supplierPortalFeedbacks->max('submitted_at')?->toDateString(),
                    'status' => $rfq->status,
                ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'po_value_this_month' => $currentValue,
                        'po_value_previous_month' => $previousValue,
                        'pending_prs' => (clone $pendingPrQuery)->count(),
                        'pending_pos' => (clone $pendingPoQuery)->count(),
                        'quoted_rfqs' => (clone $quotedRfqQuery)->count(),
                    ],
                    'po_trend' => $trend,
                    'pending_prs' => $pendingPrs,
                    'pending_pos' => $pendingPos,
                    'quoted_rfqs' => $quotedRfqs,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Procurement dashboard stats failed', ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard statistics.',
            ], 500);
        }
    }

    public function getSummaryCards(Request $request)
    {
        return $this->getStats($request);
    }
}
