<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\StockIssue;
use App\Models\Inventory\StockIssueItem;
use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockIssueController extends Controller
{
    /**
     * Get the authenticated user's context (store & branch)
     */
    private function getUserContext(): array
    {
        return [
            'store_id' => auth()->user()->store_id,
            'branch_id' => auth()->user()->branch_id,
        ];
    }

    /**
     * Display stock issues
     * GET /api/inventory/issues
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $context = $this->getUserContext();

            $query = StockIssue::with(['branch', 'requester', 'approver'])
                ->where('store_id', $context['store_id']);

            // Filters
            if ($request->has('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('issue_type')) {
                $query->where('issue_type', $request->issue_type);
            }

            if ($request->has('date_from')) {
                $query->whereDate('issue_date', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('issue_date', '<=', $request->date_to);
            }

            $issues = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $issues,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch stock issues',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show single stock issue
     * GET /api/inventory/issues/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $context = $this->getUserContext();

            $issue = StockIssue::with([
                'branch',
                'requester',
                'approver',
                'issuer',
                'creator',
                'items.inventoryItem.product'
            ])
            ->where('store_id', $context['store_id'])
            ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $issue,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Stock issue not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Create new stock issue
     * POST /api/inventory/issues
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $context = $this->getUserContext();

            $validated = $request->validate([
                'issue_date' => 'required|date|before_or_equal:today',
                'issue_type' => 'required|in:damaged,lost,expired,theft,other',
                'description' => 'nullable|string',
                'remarks' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.inventory_item_id' => 'required|exists:branch_inventory,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.reason' => 'nullable|string',
                'items.*.remarks' => 'nullable|string',
            ]);

            DB::beginTransaction();

            // Generate issue number
            $issueNumber = 'SI-' . date('Y') . '-' . str_pad(
                StockIssue::where('store_id', $context['store_id'])
                    ->whereYear('created_at', date('Y'))
                    ->count() + 1,
                4, '0', STR_PAD_LEFT
            );

            $issue = StockIssue::create([
                'store_id' => $context['store_id'],
                'branch_id' => $context['branch_id'],
                'issue_number' => $issueNumber,
                'issue_date' => $validated['issue_date'],
                'issue_type' => $validated['issue_type'],
                'description' => $validated['description'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'status' => 'draft',
                'requested_by' => auth()->id(),
                'created_by' => auth()->id(),
            ]);

            $totalValue = 0;

            // Create issue items
            foreach ($validated['items'] as $itemData) {
                $inventoryItem = BranchInventory::findOrFail($itemData['inventory_item_id']);

                // Validate quantity doesn't exceed available stock
                if ($itemData['quantity'] > $inventoryItem->quantity_on_hand) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for {$inventoryItem->product->product_name}. Available: {$inventoryItem->quantity_on_hand}",
                    ], 422);
                }

                $unitCost = $inventoryItem->unit_cost ?? $inventoryItem->average_cost ?? 0;
                $itemTotalValue = $itemData['quantity'] * $unitCost;

                StockIssueItem::create([
                    'stock_issue_id' => $issue->id,
                    'inventory_item_id' => $itemData['inventory_item_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_cost' => $unitCost,
                    'total_value' => $itemTotalValue,
                    'reason' => $itemData['reason'] ?? null,
                    'remarks' => $itemData['remarks'] ?? null,
                ]);

                $totalValue += $itemTotalValue;
            }

            // Update total value
            $issue->update(['total_value' => $totalValue]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $issue->load(['items.inventoryItem.product']),
                'message' => 'Stock issue created successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create stock issue',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve stock issue
     * POST /api/inventory/issues/{id}/approve
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $context = $this->getUserContext();

            $issue = StockIssue::where('store_id', $context['store_id'])
                ->findOrFail($id);

            if (!$issue->canBeApproved()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock issue cannot be approved in its current status',
                ], 422);
            }

            $validated = $request->validate([
                'notes' => 'nullable|string',
            ]);

            DB::beginTransaction();

            $issue->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approval_notes' => $validated['notes'] ?? null,
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $issue->load(['approver']),
                'message' => 'Stock issue approved successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve stock issue',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get issue reasons
     * GET /api/inventory/issues/reasons
     */
    public function getReasons(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                ['value' => 'damaged', 'label' => 'Damaged Goods'],
                ['value' => 'lost', 'label' => 'Lost Items'],
                ['value' => 'expired', 'label' => 'Expired Items'],
                ['value' => 'theft', 'label' => 'Theft/Shoplifting'],
                ['value' => 'other', 'label' => 'Other'],
            ],
        ]);
    }
}
