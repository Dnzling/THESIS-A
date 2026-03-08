<?php
// backend/app/Http/Controllers/Procurement/Requisition/PurchaseRequisitionController.php

namespace App\Http\Controllers\Api\Procurement\Requisition;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Requisition\PurchaseRequisition;
use App\Models\Procurement\Requisition\PurchaseRequisitionItem;
use App\Models\Procurement\Config\ProcurementSettings;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PurchaseRequisitionController extends Controller
{
    /**
     * List all purchase requisitions
     * GET /api/procurement/requisitions
     */
    public function index(Request $request): JsonResponse
    {
        $query = PurchaseRequisition::with(['branch', 'requestedBy'])
            ->where('store_id', auth()->user()->store_id);

        // Filters
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('requisition_type')) {
            $query->where('requisition_type', $request->requisition_type);
        }

        if ($request->has('procurement_route')) {
            $query->where('procurement_route', $request->procurement_route);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        $requisitions = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $requisitions,
        ]);
    }

    /**
     * Show single requisition
     * GET /api/procurement/requisitions/{id}
     */
    public function show(int $id): JsonResponse
    {
        $requisition = PurchaseRequisition::with([
            'branch',
            'requestedBy',
            'items.product',
            'items.variation',
            'purchaseOrders',
            'rfqs'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $requisition,
        ]);
    }

    /**
     * Create new purchase requisition
     * POST /api/procurement/requisitions
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'requisition_type' => 'required|in:regular,urgent,new_product,seasonal,emergency',
            'required_date' => 'required|date|after:today',
            'reason' => 'required|string',
            'priority' => 'nullable|integer|min:1|max:5',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variation_id' => 'nullable|exists:product_variations,id',
            'items.*.quantity_requested' => 'required|integer|min:1',
            'items.*.estimated_unit_cost' => 'nullable|numeric|min:0',
            'items.*.specifications' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Generate PR number
            $lastPR = PurchaseRequisition::latest()->first();
            $prNumber = 'PR-' . date('Y') . '-' . str_pad(($lastPR?->id ?? 0) + 1, 5, '0', STR_PAD_LEFT);

            // Calculate estimated amount
            $estimatedAmount = 0;
            foreach ($validated['items'] as $item) {
                $estimatedAmount += ($item['quantity_requested'] * ($item['estimated_unit_cost'] ?? 0));
            }

            // Get procurement settings
            $settings = ProcurementSettings::where('store_id', auth()->user()->store_id)->first();

            // Determine procurement route
            $procurementRoute = 'branch_direct';
            if ($settings) {
                if ($estimatedAmount >= $settings->procurement_threshold) {
                    $procurementRoute = 'centralized';
                }

                if ($settings->shouldRequireRFQ($estimatedAmount)) {
                    $procurementRoute = 'rfq_required';
                }
            }

            // Determine required approvals based on amount
            $requiredApprovals = ['warehouse_manager'];
            if ($estimatedAmount >= 100000) {
                $requiredApprovals[] = 'branch_manager';
            }
            if ($estimatedAmount >= 500000) {
                $requiredApprovals[] = 'finance_manager';
            }

            // Create PR
            $pr = PurchaseRequisition::create([
                'pr_number' => $prNumber,
                'store_id' => auth()->user()->store_id,
                'branch_id' => $validated['branch_id'],
                'requisition_type' => $validated['requisition_type'],
                'status' => 'draft',
                'estimated_amount' => $estimatedAmount,
                'procurement_route' => $procurementRoute,
                'required_approvals' => $requiredApprovals,
                'required_date' => $validated['required_date'],
                'reason' => $validated['reason'],
                'priority' => $validated['priority'] ?? 3,
                'requested_by' => auth()->id(),
            ]);

            // Create items
            foreach ($validated['items'] as $item) {
                PurchaseRequisitionItem::create([
                    'requisition_id' => $pr->id,
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'] ?? null,
                    'quantity_requested' => $item['quantity_requested'],
                    'estimated_unit_cost' => $item['estimated_unit_cost'] ?? null,
                    'specifications' => $item['specifications'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase requisition created successfully',
                'data' => $pr->load('items.product'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create purchase requisition',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update purchase requisition
     * PUT /api/procurement/requisitions/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $pr = PurchaseRequisition::findOrFail($id);

        if ($pr->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft requisitions can be updated',
            ], 422);
        }

        $validated = $request->validate([
            'required_date' => 'nullable|date|after:today',
            'reason' => 'nullable|string',
            'priority' => 'nullable|integer|min:1|max:5',
        ]);

        $pr->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Purchase requisition updated successfully',
            'data' => $pr,
        ]);
    }

    /**
     * Submit PR for approval
     * POST /api/procurement/requisitions/{id}/submit
     */
    public function submit(int $id): JsonResponse
    {
        $pr = PurchaseRequisition::with('items')->findOrFail($id);

        if ($pr->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft requisitions can be submitted',
            ], 422);
        }

        if ($pr->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot submit requisition without items',
            ], 422);
        }

        $pr->submit();

        return response()->json([
            'success' => true,
            'message' => 'Purchase requisition submitted successfully',
            'data' => $pr->fresh(),
        ]);
    }

    /**
     * Approve PR
     * POST /api/procurement/requisitions/{id}/approve
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $pr = PurchaseRequisition::findOrFail($id);

        $validated = $request->validate([
            'role' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        // Check if user has this role
        $userRole = auth()->user()->role->name;

        if ($userRole !== $validated['role']) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to approve as this role',
            ], 403);
        }

        // Add approval
        $pr->addApproval(
            $validated['role'],
            auth()->id(),
            auth()->user()->full_name,
            $validated['notes'] ?? null
        );

        // Update status
        $requiredApprovals = $pr->required_approvals ?? [];
        $receivedApprovals = collect($pr->approval_chain ?? [])->pluck('role')->toArray();

        $allApproved = true;
        foreach ($requiredApprovals as $requiredRole) {
            if (!in_array($requiredRole, $receivedApprovals)) {
                $allApproved = false;
                break;
            }
        }

        if ($allApproved) {
            $pr->update(['status' => 'warehouse_approved']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Purchase requisition approved successfully',
            'data' => $pr->fresh(),
        ]);
    }

    /**
     * Reject PR
     * POST /api/procurement/requisitions/{id}/reject
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $pr = PurchaseRequisition::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $pr->update([
            'status' => 'rejected',
            'approval_chain' => array_merge($pr->approval_chain ?? [], [
                [
                    'role' => auth()->user()->role->name,
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()->full_name,
                    'action' => 'rejected',
                    'reason' => $validated['reason'],
                    'rejected_at' => now()->toDateTimeString(),
                ]
            ]),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Purchase requisition rejected',
        ]);
    }

    /**
     * Cancel PR
     * POST /api/procurement/requisitions/{id}/cancel
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $pr = PurchaseRequisition::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        if (!in_array($pr->status, ['draft', 'submitted'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft or submitted requisitions can be cancelled',
            ], 422);
        }

        $pr->update([
            'status' => 'cancelled',
            'reason' => $pr->reason . "\n\nCancellation reason: " . $validated['reason'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Purchase requisition cancelled',
        ]);
    }

    /**
     * Delete PR
     * DELETE /api/procurement/requisitions/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $pr = PurchaseRequisition::findOrFail($id);

        if ($pr->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft requisitions can be deleted',
            ], 422);
        }

        $pr->delete();

        return response()->json([
            'success' => true,
            'message' => 'Purchase requisition deleted successfully',
        ]);
    }
}