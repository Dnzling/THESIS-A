<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Core\ApprovalWorkflow;
use App\Models\Hr\Employee;
use App\Models\Inventory\InventoryTransaction;
use App\Services\Core\ApprovalEngine;
use App\Support\EmployeeContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryTransactionController extends Controller
{
    public function __construct(protected ApprovalEngine $approvalEngine) {}

    private function hasGlobalAccess(): bool
    {
        $roleName = strtolower(auth()->user()?->role?->name ?? '');
        return in_array($roleName, ['super_admin', 'owner'], true);
    }

    private function getUserContext(Request $request): array
    {
        $user = $request->user();
        $storeId = (int) ($user?->store_id ?? 0);
        $branchId = (int) ($user?->branch_id ?? 0);

        if ($user && ($storeId === 0 || $branchId === 0)) {
            $employee = Employee::query()
                ->where('user_id', $user->id)
                ->first(['store_id', 'branch_id']);

            $storeId = $storeId ?: (int) ($employee?->store_id ?? 0);
            $branchId = $branchId ?: (int) ($employee?->branch_id ?? 0);
        }

        return [
            'store_id' => $storeId,
            'branch_id' => $branchId,
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $context = $this->getUserContext($request);
        $storeId = $context['store_id'];
        $branchId = $context['branch_id'];

        if (!$this->hasGlobalAccess() && $storeId <= 0 && $branchId <= 0) {
            $perPage = $request->get('per_page', 20);
            $empty = InventoryTransaction::query()->whereRaw('1 = 0')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $empty,
            ]);
        }

        $query = InventoryTransaction::with([
            'branch',
            'product',
            'variation',
            'relatedBranch',
            'createdBy'
        ]);

        if ($storeId > 0) {
            $query->where('store_id', $storeId);
        }
        if ($branchId > 0) {
            $query->where('branch_id', $branchId);
        }

        // Filters
        if ($branchId <= 0 && $request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('transaction_date', [
                $request->start_date,
                $request->end_date
            ]);
        }

        if ($request->has('reference_type')) {
            $query->where('reference_type', $request->reference_type);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'transaction_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $transactions = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Show single transaction
     * GET /api/inventory/transactions/{id}
     */
    public function show($id): JsonResponse
    {
        $transaction = InventoryTransaction::with([
            'branch',
            'product',
            'variation',
            'relatedBranch',
            'createdBy'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $transaction,
        ]);
    }

    /**
     * Printable transaction detail page (Blade) for PDF/print.
     * GET /inventory/transactions/{id}/print
     */
    public function print(int $id): View
    {
        $user = Auth::user();

        $transaction = InventoryTransaction::with([
            'branch',
            'product',
            'variation',
            'relatedBranch',
            'createdBy',
        ])->findOrFail($id);

        if (!$this->hasGlobalAccess() && (int) ($transaction->store_id ?? 0) !== (int) ($user?->store_id ?? 0)) {
            abort(403, 'Unauthorized access to this transaction print view.');
        }

        return view('inventory.transactions.print', [
            'transaction' => $transaction,
            'transactionTypeLabel' => $this->formatTransactionType($transaction->transaction_type),
            'generatedAt' => now(),
        ]);
    }

    private function formatTransactionType(?string $type): string
    {
        if (!$type) {
            return '-';
        }

        return collect(explode('_', $type))
            ->map(fn ($word) => ucfirst($word))
            ->implode(' ');
    }
    /**
     * Create an inventory transaction and evaluate approval workflow.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transaction_number' => ['required', 'string', 'max:50', 'unique:inventory_transactions,transaction_number'],
            'store_id' => ['required', 'exists:stores,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'product_id' => ['required', 'exists:products,id'],
            'variation_id' => ['nullable', 'exists:product_variations,id'],
            'transaction_type' => ['required', 'string', 'max:50'],
            'quantity_before' => ['required', 'integer'],
            'quantity_change' => ['required', 'integer', 'not_in:0'],
            'quantity_after' => ['required', 'integer'],
            'related_branch_id' => ['nullable', 'exists:branches,id'],
            'reference_type' => ['nullable', 'string', 'max:50'],
            'reference_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
            'unit_cost' => ['nullable', 'numeric'],
            'total_value' => ['nullable', 'numeric'],
            'created_by' => ['nullable', 'exists:employees,id'],
            'transaction_date' => ['required', 'date'],
        ]);

        $authUser = Auth::user();

        $validated['created_by'] = $validated['created_by']
            ?? $authUser?->employee?->id
            ?? null;

        if (!$validated['created_by']) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to resolve creator employee id. Provide created_by explicitly.',
            ], 422);
        }

        $payload = DB::transaction(function () use ($validated, $authUser): array {
            $transaction = InventoryTransaction::create($validated);

            $approval = $this->approvalEngine->process(
                $transaction,
                'inventory.adjust',
                $authUser,
                (int) $validated['store_id']
            );

            return [
                'transaction' => $transaction->fresh(['approvalWorkflow']),
                'approval' => $approval,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $payload,
        ], 201);
    }

    /**
     * Manually approve a pending transaction workflow.
     */
    public function approve(Request $request, InventoryTransaction $inventoryTransaction): JsonResponse
    {
        if (!$inventoryTransaction->approval_workflow_id) {
            return response()->json(['success' => false, 'message' => 'No approval workflow found.'], 404);
        }

        DB::transaction(function () use ($inventoryTransaction): void {
            $workflow = ApprovalWorkflow::query()->findOrFail($inventoryTransaction->approval_workflow_id);
            $actorId = EmployeeContext::currentEmployeeId();

            $workflow->update([
                'status' => 'approved',
                'approved_by' => $actorId,
                'approved_at' => now(),
            ]);

            $workflow->tasks()->where('status', 'pending')->update([
                'status' => 'completed',
                'completed_at' => now(),
                'notes' => 'Completed by manual approval.',
            ]);

            $inventoryTransaction->update([
                'requires_approval' => false,
                'approval_status' => 'approved',
                'approved_by' => $actorId,
                'approved_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Inventory transaction approved.',
            'data' => $inventoryTransaction->fresh(['approvalWorkflow.tasks']),
        ]);
    }

    /**
     * Manually reject a pending transaction workflow.
     */
    public function reject(Request $request, InventoryTransaction $inventoryTransaction): JsonResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        if (!$inventoryTransaction->approval_workflow_id) {
            return response()->json(['success' => false, 'message' => 'No approval workflow found.'], 404);
        }

        DB::transaction(function () use ($inventoryTransaction, $validated): void {
            $workflow = ApprovalWorkflow::query()->findOrFail($inventoryTransaction->approval_workflow_id);
            $workflow->update([
                'status' => 'rejected',
                'notes' => $validated['notes'] ?? 'Rejected manually.',
            ]);

            $workflow->tasks()->where('status', 'pending')->update([
                'status' => 'rejected',
                'completed_at' => now(),
                'notes' => $validated['notes'] ?? 'Rejected by approver.',
            ]);

            $inventoryTransaction->update([
                'requires_approval' => true,
                'approval_status' => 'rejected',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Inventory transaction rejected.',
            'data' => $inventoryTransaction->fresh(['approvalWorkflow.tasks']),
        ]);
    }

    /**
     * List pending approvals for the authenticated approver.
     */
    public function pendingApprovals(Request $request): JsonResponse
    {
        $storeId = Auth::user()?->store_id;

        $query = InventoryTransaction::query()
            ->with(['product', 'branch', 'approvalWorkflow.tasks'])
            ->pendingApproval();

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $data = $query->orderByDesc('transaction_date')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json(['success' => true, 'data' => $data]);
    }
    public function summary(Request $request): JsonResponse
    {
        $query = InventoryTransaction::where('store_id', auth()->user()->store_id);

        // Apply date filter
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('transaction_date', [
                $request->start_date,
                $request->end_date
            ]);
        }

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $summary = [
            'total_transactions' => $query->count(),
            'by_type' => [
                'purchases' => (clone $query)->where('transaction_type', 'purchase')->count(),
                'sales' => (clone $query)->where('transaction_type', 'sale')->count(),
                'transfers_in' => (clone $query)->where('transaction_type', 'transfer_in')->count(),
                'transfers_out' => (clone $query)->where('transaction_type', 'transfer_out')->count(),
                'adjustments' => (clone $query)->where('transaction_type', 'adjustment')->count(),
                'damages' => (clone $query)->where('transaction_type', 'damage')->count(),
                'returns_to_supplier' => (clone $query)->where('transaction_type', 'return_to_supplier')->count(),
                'customer_returns' => (clone $query)->where('transaction_type', 'customer_return')->count(),
            ],
            'total_value_in' => (clone $query)->whereIn('transaction_type', ['purchase', 'transfer_in', 'customer_return'])
                ->where('quantity_change', '>', 0)
                ->sum('total_value'),
            'total_value_out' => (clone $query)->whereIn('transaction_type', ['sale', 'transfer_out', 'return_to_supplier', 'damage'])
                ->where('quantity_change', '<', 0)
                ->sum('total_value'),
        ];

        $summary['net_value'] = $summary['total_value_in'] - abs($summary['total_value_out']);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }
}
