<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\StockIssue;
use App\Models\Inventory\StockIssueItem;
use App\Services\Core\PermissionService;
use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Support\EmployeeContext;
use Illuminate\Support\Str;

class StockIssueController extends Controller
{
    /**
     * Get the authenticated user's context (store & branch)
     */
    private function getUserContext(): array
    {
        return [
            'store_id' => Auth::user()->store_id,
            'branch_id' => Auth::user()->branch_id,
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

            $query = StockIssue::with(['branch', 'requester', 'approver', 'creator'])
                ->where('store_id', $context['store_id']);

            // Filters
            if (!empty($context['branch_id'])) {
                $query->where('branch_id', $context['branch_id']);
            } elseif ($request->has('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('movement_type')) {
                $query->where('movement_type', $request->movement_type);
            }

            if ($request->filled('issue_type')) {
                $query->where('issue_type', $request->issue_type);
            }

            if ($request->filled('search')) {
                $search = trim((string) $request->search);
                $query->where(function ($builder) use ($search) {
                    $builder->where('issue_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%");
                });
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
                'issue_type' => 'required|string|max:50',
                'movement_type' => 'required|in:add,deduct',
                'description' => 'nullable|string',
                'remarks' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.inventory_item_id' => 'required|exists:branch_inventory,id',
                'items.*.quantity' => 'required|integer|min:1',
            ]);

            DB::beginTransaction();

            // Generate issue number using datetime for uniqueness
            $issueNumber = 'SI-' . date('YmdHis') . '-' . str_pad(random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

            $issue = StockIssue::create([
                'store_id' => $context['store_id'],
                'branch_id' => $context['branch_id'],
                'issue_number' => $issueNumber,
                'issue_date' => now()->toDateString(),
                'issue_type' => $validated['issue_type'],
                'movement_type' => $validated['movement_type'],
                'description' => $validated['description'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'status' => 'submitted',
                // These columns reference users.id, not employees.id.
                'requested_by' => auth()->id(),
                'created_by' => auth()->id(),
            ]);

            $totalValue = 0;

            // Create issue items
            foreach ($validated['items'] as $itemData) {
                $inventoryItem = BranchInventory::findOrFail($itemData['inventory_item_id']);

                // Validate quantity doesn't exceed available stock
                if (($validated['movement_type'] ?? 'deduct') === 'deduct' && $itemData['quantity'] > $inventoryItem->quantity_on_hand) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for {$inventoryItem->product->product_name}. Available: {$inventoryItem->quantity_on_hand}",
                    ], 422);
                }

                $unitCost = (float) $inventoryItem->product?->getRawOriginal('cost_price');
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

            // Users who can approve issuances may approve their own creation.
            // Apply the stock change in this same transaction so the record and
            // inventory cannot become out of sync.
            $authUser = $request->user();
            if ($authUser) {
                // Role permissions may have just been updated by an administrator;
                // refresh this decision instead of relying on the 30-minute cache.
                app(PermissionService::class)->clearUserCache($authUser);
            }

            if ($authUser?->hasPermissionTo('stock_issues.approve', $context['store_id'])) {
                $issue->load('items');

                foreach ($issue->items as $issueItem) {
                    $inventoryItem = BranchInventory::query()
                        ->where('store_id', $context['store_id'])
                        ->where('branch_id', $issue->branch_id)
                        ->lockForUpdate()
                        ->findOrFail($issueItem->inventory_item_id);

                    $quantity = (int) $issueItem->quantity;
                    $isDeduction = $issue->movement_type === 'deduct';

                    if ($isDeduction && $quantity > (int) $inventoryItem->quantity_on_hand) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Insufficient stock to approve this issuance.',
                        ], 422);
                    }

                    $inventoryItem->quantity_on_hand += $isDeduction ? -$quantity : $quantity;
                    $inventoryItem->quantity_available += $isDeduction ? -$quantity : $quantity;
                    $inventoryItem->updateStockStatus();
                }

                $issue->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
            }

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
     * Update a draft stock issuance.
     * PUT /api/inventory/issues/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $context = $this->getUserContext();
            $issue = StockIssue::query()
                ->where('store_id', $context['store_id'])
                ->where('branch_id', $context['branch_id'])
                ->findOrFail($id);

            if ($issue->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft stock issuances can be edited.',
                ], 422);
            }

            $validated = $request->validate([
                'issue_type' => 'required|string|max:50',
                'movement_type' => 'required|in:add,deduct',
                'description' => 'nullable|string',
                'remarks' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.inventory_item_id' => 'required|exists:branch_inventory,id',
                'items.*.quantity' => 'required|integer|min:1',
            ]);

            DB::beginTransaction();

            $totalValue = 0;
            $newItems = [];
            foreach ($validated['items'] as $itemData) {
                $inventoryItem = BranchInventory::query()
                    ->with('product')
                    ->where('store_id', $context['store_id'])
                    ->where('branch_id', $context['branch_id'])
                    ->findOrFail($itemData['inventory_item_id']);

                if ($validated['movement_type'] === 'deduct' && $itemData['quantity'] > $inventoryItem->quantity_on_hand) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for {$inventoryItem->product->product_name}.",
                    ], 422);
                }

                $unitCost = (float) $inventoryItem->product?->getRawOriginal('cost_price');
                $itemTotal = $itemData['quantity'] * $unitCost;
                $totalValue += $itemTotal;
                $newItems[] = [
                    'inventory_item_id' => $itemData['inventory_item_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_cost' => $unitCost,
                    'total_value' => $itemTotal,
                ];
            }

            $issue->update([
                'movement_type' => $validated['movement_type'],
                'issue_type' => $validated['issue_type'],
                'description' => $validated['description'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'total_value' => $totalValue,
                'updated_by' => auth()->id(),
            ]);

            $issue->items()->delete();
            $issue->items()->createMany($newItems);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $issue->fresh()->load(['creator', 'items.inventoryItem.product']),
                'message' => 'Stock issuance updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update stock issuance',
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

            // Apply the issuance atomically when it is approved. Locking the
            // rows prevents two approvals from consuming the same stock.
            $issue->load('items');
            foreach ($issue->items as $issueItem) {
                $inventoryItem = BranchInventory::query()
                    ->where('store_id', $context['store_id'])
                    ->where('branch_id', $issue->branch_id)
                    ->lockForUpdate()
                    ->findOrFail($issueItem->inventory_item_id);

                $quantity = (int) $issueItem->quantity;
                $isDeduction = $issue->movement_type === 'deduct';

                if ($isDeduction && $quantity > (int) $inventoryItem->quantity_on_hand) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient stock to approve this issuance.',
                    ], 422);
                }

                $inventoryItem->quantity_on_hand += $isDeduction ? -$quantity : $quantity;
                $inventoryItem->quantity_available += $isDeduction ? -$quantity : $quantity;
                $inventoryItem->updateStockStatus();
            }

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
