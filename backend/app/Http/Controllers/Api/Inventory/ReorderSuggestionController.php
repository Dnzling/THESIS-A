<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\ReorderSuggestionRequest;
use App\Models\Inventory\ReorderSuggestion;
use App\Services\Inventory\ReorderSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ReorderSuggestionController extends Controller
{
    protected ReorderSuggestionService $suggestionService;

    public function __construct(ReorderSuggestionService $suggestionService)
    {
        $this->suggestionService = $suggestionService;
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
            $suggestion = $this->suggestionService->createSuggestion($request->validated());

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
            $updatedSuggestion = $this->suggestionService->updateSuggestion($suggestion, $request->validated());

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
            $request->validate([
                'notes' => 'nullable|string|max:1000',
            ]);

            $approvedBy = auth()->id();
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
            $request->validate([
                'notes' => 'nullable|string|max:1000',
            ]);

            $implementedBy = auth()->id();
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

            $branchId = $request->get('branch_id');
            $result = $this->suggestionService->generateSuggestions($branchId);

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

    /**
     * Get suggestion statistics.
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'branch_id' => 'nullable|integer|exists:branches,id',
            ]);

            $branchId = $request->get('branch_id');
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
                'suggestion_ids' => 'required|array|min:1',
                'suggestion_ids.*' => 'integer|exists:reorder_suggestions,id',
                'notes' => 'nullable|string|max:1000',
            ]);

            $suggestionIds = $request->get('suggestion_ids');
            $approvedBy = auth()->id();
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
                'suggestion_ids' => 'required|array|min:1',
                'suggestion_ids.*' => 'integer|exists:reorder_suggestions,id',
                'notes' => 'nullable|string|max:1000',
            ]);

            $suggestionIds = $request->get('suggestion_ids');
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
