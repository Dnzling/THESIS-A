<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\ReorderSuggestionRequest;
use App\Models\Hr\Employee;
use App\Models\Inventory\ReorderSuggestion;
use App\Models\Store\Branch;
use App\Services\Inventory\ReorderSuggestionService;
use App\Support\EmployeeContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Core\SystemNotification;
use Exception;

class ReorderSuggestionController extends Controller
{
    protected ReorderSuggestionService $suggestionService;

    private function userHasAnyPermission($user, array $permissionNames, int $storeId): bool
    {
        if (!$user) {
            return false;
        }

        foreach ($permissionNames as $permission) {
            $normalized = (string) $permission;
            $aliases = array_values(array_unique([
                $normalized,
                Str::contains($normalized, '_') ? str_replace('_', '-', $normalized) : $normalized,
                Str::contains($normalized, '-') ? str_replace('-', '_', $normalized) : $normalized,
            ]));

            foreach ($aliases as $candidate) {
                if ($candidate && $user->hasPermissionTo($candidate, $storeId)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function __construct(ReorderSuggestionService $suggestionService)
    {
        $this->suggestionService = $suggestionService;
    }

    /**
     * Resolve authenticated user context.
     */
    private function getUserContext(): array
    {
        $user = auth()->user();
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

    private function resolveBranchId(Request $request): int
    {
        $context = $this->getUserContext();
        return (int) ($request->branch_id ?? $context['branch_id'] ?? 0);
    }

    private function canAccessBranchRecord(int $recordBranchId): bool
    {
        $context = $this->getUserContext();
        if ($this->hasGlobalAccess()) {
            return true;
        }

        if (empty($context['store_id']) && empty($context['branch_id'])) {
            return false;
        }

        if (!empty($context['branch_id'])) {
            return (int) $context['branch_id'] === (int) $recordBranchId;
        }

        return Branch::query()
            ->where('id', $recordBranchId)
            ->where('store_id', $context['store_id'])
            ->exists();
    }

    private function hasGlobalAccess(): bool
    {
        $roleName = strtolower(auth()->user()?->role?->name ?? '');
        return in_array($roleName, ['super_admin', 'owner'], true);
    }

    /**
     * Display a listing of reorder suggestions.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'status', 'priority', 'type', 'branch_id', 'product_id',
                'expired', 'search'
            ]);
            $context = $this->getUserContext();

            if (!$this->hasGlobalAccess() && empty($context['store_id']) && empty($context['branch_id'])) {
                $perPage = $request->get('per_page', 15);
                $empty = ReorderSuggestion::query()->whereRaw('1 = 0')->paginate($perPage);

                return response()->json([
                    'success' => true,
                    'data' => $empty,
                    'message' => 'Reorder suggestions retrieved successfully.',
                ]);
            }

            if (!isset($filters['branch_id']) && !empty($context['branch_id'])) {
                $filters['branch_id'] = $context['branch_id'];
            }

            if (!empty($context['store_id'])) {
                $filters['store_id'] = $context['store_id'];
            }

            $perPage = $request->get('per_page', 15);
            $suggestions = $this->suggestionService->getSuggestions($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $suggestions,
                'message' => 'Reorder suggestions retrieved successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reorder suggestions.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created reorder suggestion.
     */
    public function store(ReorderSuggestionRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $storeId = (int) ($user?->store_id ?? 0);

            $payload = $request->validated();
            if (empty($payload['branch_id'])) {
                $payload['branch_id'] = $this->resolveBranchId($request);
            }

            $suggestion = $this->suggestionService->createSuggestion($payload);

            if ($this->userHasAnyPermission($user, ['inventory.reorder_suggestions.approve'], $storeId) && $suggestion->isPending()) {
                $approvedBy = EmployeeContext::currentEmployeeId();
                $this->suggestionService->approveSuggestion($suggestion, $approvedBy, 'Auto-approved on creation.');
                $suggestion = $suggestion->fresh(['reorderRule', 'product', 'branch', 'approver']);
            }

            return response()->json([
                'success' => true,
                'data' => $suggestion,
                'message' => 'Reorder suggestion created successfully.',
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create reorder suggestion.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified reorder suggestion.
     */
    public function show(ReorderSuggestion $suggestion): JsonResponse
    {
        try {
            if (!$this->canAccessBranchRecord((int) $suggestion->branch_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to reorder suggestion.',
                ], 403);
            }

            $suggestion->load(['reorderRule', 'product', 'branch', 'approver', 'implementer']);

            return response()->json([
                'success' => true,
                'data' => $suggestion,
                'message' => 'Reorder suggestion retrieved successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reorder suggestion.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified reorder suggestion.
     */
    public function update(ReorderSuggestionRequest $request, ReorderSuggestion $suggestion): JsonResponse
    {
        try {
            if (!$this->canAccessBranchRecord((int) $suggestion->branch_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to reorder suggestion.',
                ], 403);
            }

            $payload = $request->validated();
            if (empty($payload['branch_id'])) {
                $payload['branch_id'] = (int) $suggestion->branch_id;
            }

            $updatedSuggestion = $this->suggestionService->updateSuggestion($suggestion, $payload);

            return response()->json([
                'success' => true,
                'data' => $updatedSuggestion,
                'message' => 'Reorder suggestion updated successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update reorder suggestion.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified reorder suggestion.
     */
    public function destroy(ReorderSuggestion $suggestion): JsonResponse
    {
        try {
            if (!$this->canAccessBranchRecord((int) $suggestion->branch_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to reorder suggestion.',
                ], 403);
            }

            // Only allow deletion of pending suggestions
            if (!$suggestion->isPending()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending suggestions can be deleted.',
                ], 422);
            }

            $suggestion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Reorder suggestion deleted successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete reorder suggestion.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve a reorder suggestion.
     */
    public function approve(Request $request, ReorderSuggestion $suggestion): JsonResponse
    {
        try {
            if (!$this->canAccessBranchRecord((int) $suggestion->branch_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to reorder suggestion.',
                ], 403);
            }

            $request->validate([
                'notes' => 'nullable|string|max:1000',
            ]);

            $approvedBy = EmployeeContext::currentEmployeeId();
            $notes = $request->get('notes');

            if (!$this->suggestionService->approveSuggestion($suggestion, $approvedBy, $notes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Suggestion cannot be approved.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $suggestion->fresh(['reorderRule', 'product', 'branch', 'approver']),
                'message' => 'Reorder suggestion approved successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve reorder suggestion.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject a reorder suggestion.
     */
    public function reject(Request $request, ReorderSuggestion $suggestion): JsonResponse
    {
        try {
            if (!$this->canAccessBranchRecord((int) $suggestion->branch_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to reorder suggestion.',
                ], 403);
            }

            $request->validate([
                'notes' => 'nullable|string|max:1000',
            ]);

            $notes = $request->get('notes');

            if (!$this->suggestionService->rejectSuggestion($suggestion, $notes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Suggestion cannot be rejected.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $suggestion->fresh(['reorderRule', 'product', 'branch']),
                'message' => 'Reorder suggestion rejected successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject reorder suggestion.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Implement a reorder suggestion.
     */
    public function implement(Request $request, ReorderSuggestion $suggestion): JsonResponse
    {
        try {
            if (!$this->canAccessBranchRecord((int) $suggestion->branch_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to reorder suggestion.',
                ], 403);
            }

            $request->validate([
                'notes' => 'nullable|string|max:1000',
            ]);

            $implementedBy = EmployeeContext::currentEmployeeId();
            $notes = $request->get('notes');

            if (!$this->suggestionService->implementSuggestion($suggestion, $implementedBy, $notes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Suggestion cannot be implemented.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $suggestion->fresh(['reorderRule', 'product', 'branch', 'implementer']),
                'message' => 'Reorder suggestion implemented successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to implement reorder suggestion.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel a reorder suggestion.
     */
    public function cancel(Request $request, ReorderSuggestion $suggestion): JsonResponse
    {
        try {
            if (!$this->canAccessBranchRecord((int) $suggestion->branch_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to reorder suggestion.',
                ], 403);
            }

            $request->validate([
                'notes' => 'nullable|string|max:1000',
            ]);

            $notes = $request->get('notes');

            if (!$this->suggestionService->cancelSuggestion($suggestion, $notes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Suggestion cannot be cancelled.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $suggestion->fresh(['reorderRule', 'product', 'branch']),
                'message' => 'Reorder suggestion cancelled successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel reorder suggestion.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate reorder suggestions based on active rules.
     */
    public function generateSuggestions(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'branch_id' => 'nullable|integer|exists:branches,id',
            ]);

            $branchId = $this->resolveBranchId($request);
            $result = $this->suggestionService->generateSuggestions($branchId);

            if ((int) ($result['total_generated'] ?? 0) > 0) {
                $this->createGroupedSuggestionNotifications((int) ($result['total_generated'] ?? 0));
            }

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => "Generated {$result['total_generated']} reorder suggestions.",
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate reorder suggestions.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function createGroupedSuggestionNotifications(int $suggestionCount): void
    {
        $context = $this->getUserContext();
        $storeId = (int) ($context['store_id'] ?? 0);
        if ($storeId <= 0) {
            return;
        }

        $permissionName = 'inventory.reorder_suggestions.view';
        $permissionId = DB::table('permissions')
            ->where('name', $permissionName)
            ->where('is_active', true)
            ->value('id');

        if (empty($permissionId)) {
            return;
        }

        $roleBasedUserIds = DB::table('users')
            ->join('role_permissions', 'users.role_id', '=', 'role_permissions.role_id')
            ->where('users.store_id', $storeId)
            ->where('users.is_active', true)
            ->where('role_permissions.permission_id', (int) $permissionId)
            ->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $grantedUserIds = DB::table('user_permissions')
            ->join('users', 'users.id', '=', 'user_permissions.user_id')
            ->where('users.store_id', $storeId)
            ->where('users.is_active', true)
            ->where('user_permissions.type', 'grant')
            ->where('user_permissions.permission_id', (int) $permissionId)
            ->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $revokedUserIds = DB::table('user_permissions')
            ->join('users', 'users.id', '=', 'user_permissions.user_id')
            ->where('users.store_id', $storeId)
            ->where('users.is_active', true)
            ->where('user_permissions.type', 'revoke')
            ->where('user_permissions.permission_id', (int) $permissionId)
            ->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $recipientIds = collect(array_merge($roleBasedUserIds, $grantedUserIds))
            ->unique()
            ->reject(fn ($id) => in_array((int) $id, $revokedUserIds, true))
            ->values()
            ->all();

        if (empty($recipientIds)) {
            return;
        }

        $title = "YOU HAVE {$suggestionCount} REORDER SUGGESTIONS";
        $message = "{$suggestionCount} new reorder suggestion(s) are ready for review.";

        foreach ($recipientIds as $userId) {
            SystemNotification::create([
                'store_id' => $storeId,
                'branch_id' => null,
                'user_id' => (int) $userId,
                'module' => 'inventory',
                'entity_type' => 'reorder_suggestion',
                'action' => 'manual_generated_grouped',
                'title' => $title,
                'message' => $message,
                'data' => [
                    'suggestion_count' => $suggestionCount,
                    'trigger' => 'manual',
                ],
                'link' => '/inventory/reorder-suggestions',
                'severity' => 'warning',
                'is_read' => false,
            ]);
        }
    }

    /**
     * Get suggestion statistics.
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'branch_id' => 'nullable|integer|exists:branches,id',
            ]);

            $branchId = $this->resolveBranchId($request);
            $stats = $this->suggestionService->getSuggestionStats($branchId);

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Suggestion statistics retrieved successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve suggestion statistics.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk approve suggestions.
     */
    public function bulkApprove(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'suggestion_ids' => 'nullable|array|min:1',
                'suggestion_ids.*' => 'integer|exists:reorder_suggestions,id',
                'ids' => 'nullable|array|min:1',
                'ids.*' => 'integer|exists:reorder_suggestions,id',
                'notes' => 'nullable|string|max:1000',
            ]);

            $suggestionIds = $request->get('suggestion_ids', $request->get('ids', []));
            if (empty($suggestionIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No suggestions provided for bulk approve.',
                ], 422);
            }

            $context = $this->getUserContext();
            if (!empty($context['branch_id'])) {
                $suggestionIds = ReorderSuggestion::query()
                    ->whereIn('id', $suggestionIds)
                    ->where('branch_id', $context['branch_id'])
                    ->pluck('id')
                    ->all();
            }
            $approvedBy = EmployeeContext::currentEmployeeId();
            $notes = $request->get('notes');

            $result = $this->suggestionService->bulkApprove($suggestionIds, $approvedBy, $notes);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => "Approved {$result['total_approved']} suggestions.",
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk approve suggestions.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk reject suggestions.
     */
    public function bulkReject(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'suggestion_ids' => 'nullable|array|min:1',
                'suggestion_ids.*' => 'integer|exists:reorder_suggestions,id',
                'ids' => 'nullable|array|min:1',
                'ids.*' => 'integer|exists:reorder_suggestions,id',
                'notes' => 'nullable|string|max:1000',
            ]);

            $suggestionIds = $request->get('suggestion_ids', $request->get('ids', []));
            if (empty($suggestionIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No suggestions provided for bulk reject.',
                ], 422);
            }

            $context = $this->getUserContext();
            if (!empty($context['branch_id'])) {
                $suggestionIds = ReorderSuggestion::query()
                    ->whereIn('id', $suggestionIds)
                    ->where('branch_id', $context['branch_id'])
                    ->pluck('id')
                    ->all();
            }
            $notes = $request->get('notes');

            $result = $this->suggestionService->bulkReject($suggestionIds, $notes);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => "Rejected {$result['total_rejected']} suggestions.",
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk reject suggestions.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get suggestion types.
     */
    public function getTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'suggestion_types' => [
                    ['value' => 'automatic', 'label' => 'Automatic'],
                    ['value' => 'manual', 'label' => 'Manual'],
                    ['value' => 'emergency', 'label' => 'Emergency'],
                ],
                'priorities' => [
                    ['value' => 'low', 'label' => 'Low'],
                    ['value' => 'medium', 'label' => 'Medium'],
                    ['value' => 'high', 'label' => 'High'],
                    ['value' => 'critical', 'label' => 'Critical'],
                ],
                'statuses' => [
                    ['value' => 'pending', 'label' => 'Pending'],
                    ['value' => 'approved', 'label' => 'Approved'],
                    ['value' => 'rejected', 'label' => 'Rejected'],
                    ['value' => 'implemented', 'label' => 'Implemented'],
                    ['value' => 'cancelled', 'label' => 'Cancelled'],
                ],
            ],
            'message' => 'Suggestion types retrieved successfully.',
        ]);
    }
}
