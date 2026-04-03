<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\StockAlert;
use App\Services\Inventory\AlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlertController extends Controller
{
    protected $alertService;

    public function __construct(AlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    /**
     * Get all alerts for user's branch (paginated)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $branch = $user->branch;
            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not associated with a branch',
                ], 422);
            }

            $perPage = $request->get('per_page', 15);
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $status = $request->get('status', null); // acknowledged, resolved, null=all

            $query = StockAlert::where('branch_id', operator: $branch->id)
                ->where('branch_id', $user->branch_id);

            if ($status === 'acknowledged') {
                $query->where('is_acknowledged', true)->where('is_resolved', false);
            } elseif ($status === 'resolved') {
                $query->where('is_resolved', true);
            } elseif ($status === 'active') {
                $query->where('is_acknowledged', false)->where('is_resolved', false);
            }

            $alerts = $query
                ->orderBy($sortBy, $sortOrder)
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $alerts->items(),
                'meta' => [
                    'total' => $alerts->total(),
                    'count' => count($alerts->items()),
                    'per_page' => $alerts->perPage(),
                    'current_page' => $alerts->currentPage(),
                    'last_page' => $alerts->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve alerts: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single alert detail
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $alert = StockAlert::where('id', $id)
                ->where('store_id', $user->store_id)
                ->firstOrFail();

            // Check authorization
            if ($alert->branch_id !== optional($user->branch)->id && !$user->hasRole(['store_admin', 'super_admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view this alert',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $alert->load('product', 'branch'),
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Alert not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve alert: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Acknowledge an alert
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function acknowledge(Request $request, string $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $alert = StockAlert::where('id', $id)
                ->where('store_id', $user->store_id)
                ->firstOrFail();

            // Check authorization
            if ($alert->branch_id !== optional($user->branch)->id && !$user->hasRole(['store_admin', 'super_admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to acknowledge this alert',
                ], 403);
            }

            $notes = $request->get('notes', null);

            $alert->update([
                'is_acknowledged' => true,
                'acknowledged_by' => $user->id,
                'acknowledged_at' => now(),
                'notes' => $notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Alert acknowledged successfully',
                'data' => $alert,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Alert not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to acknowledge alert: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resolve an alert
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function resolve(Request $request, string $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $alert = StockAlert::where('id', $id)
                ->where('store_id', $user->store_id)
                ->firstOrFail();

            // Check authorization
            if ($alert->branch_id !== optional($user->branch)->id && !$user->hasRole(['store_admin', 'super_admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to resolve this alert',
                ], 403);
            }

            $resolutionNotes = $request->get('resolution_notes', null);
            $actionTaken = $request->get('action_taken', null); // e.g., 'stock_transferred', 'order_placed', 'manual_adjustment'

            $alert->update([
                'is_resolved' => true,
                'resolved_by' => $user->id,
                'resolved_at' => now(),
                'resolution_notes' => $resolutionNotes,
                'action_taken' => $actionTaken,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Alert resolved successfully',
                'data' => $alert,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Alert not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resolve alert: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get active unresolved alerts
     *
     * @return JsonResponse
     */
    public function getActive(): JsonResponse
    {
        try {
            $user = auth()->user();
            $branch = $user->branch;
            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not associated with a branch',
                ], 422);
            }

            $alerts = StockAlert::where('branch_id', $branch->id)
                ->where('store_id', $user->store_id)
                ->where('is_resolved', false)
                ->orderBy('severity', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $alerts,
                'meta' => ['total' => count($alerts)],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve active alerts: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get alerts filtered by type
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getByType(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $branch = $user->branch;
            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not associated with a branch',
                ], 422);
            }

            $type = $request->get('type');
            if (!$type) {
                return response()->json([
                    'success' => false,
                    'message' => 'Alert type is required',
                ], 422);
            }

            $validTypes = ['low_stock', 'out_of_stock', 'overstock', 'expiring_soon', 'damaged', 'slow_moving'];
            if (!in_array($type, $validTypes)) {
                return response()->json([
                    'success' => false,
                    'message' => "Invalid alert type. Valid types: " . implode(', ', $validTypes),
                ], 422);
            }

            $alerts = StockAlert::where('branch_id', $branch->id)
                ->where('store_id', $user->store_id)
                ->where('alert_type', $type)
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $alerts->items(),
                'meta' => [
                    'total' => $alerts->total(),
                    'count' => count($alerts->items()),
                    'type' => $type,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve alerts by type: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get alert statistics (count by type and status)
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $user = auth()->user();
            $branch = $user->branch;
            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not associated with a branch',
                ], 422);
            }

            $baseQuery = StockAlert::where('branch_id', $branch->id)
                ->where('branch_id', $user->branch_id);

            // Get counts by status (using your actual status enum)
            $active = (clone $baseQuery)->where('status', 'active')->count();
            $acknowledged = (clone $baseQuery)->where('status', 'acknowledged')->count();
            $resolved = (clone $baseQuery)->where('status', 'resolved')->count();

            // Get counts by alert_type (using your actual enum values)
            $byType = [
                'low_stock' => (clone $baseQuery)->where('alert_type', 'low_stock')->count(),
                'out_of_stock' => (clone $baseQuery)->where('alert_type', 'out_of_stock')->count(),
                'overstock' => (clone $baseQuery)->where('alert_type', 'overstock')->count(),
                'reorder_needed' => (clone $baseQuery)->where('alert_type', 'reorder_needed')->count(),
                'expired_soon' => (clone $baseQuery)->where('alert_type', 'expired_soon')->count(),
            ];

            // Get counts by status (more detailed)
            $byStatus = [
                'active' => $active,
                'acknowledged' => $acknowledged,
                'resolved' => $resolved,
            ];

            // Get counts by product (top 5 products with most alerts)
            $byProduct = (clone $baseQuery)
                ->with('product')
                ->select('product_id', DB::raw('count(*) as total'))
                ->whereNotNull('product_id')
                ->groupBy('product_id')
                ->orderByDesc('total')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->product_name ?? 'Unknown',
                        'total' => $item->total
                    ];
                });

            // Recent unresolved alerts (active and acknowledged)
            $recentUnresolved = (clone $baseQuery)
                ->with(['product', 'branch'])
                ->whereIn('status', ['active', 'acknowledged'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();

            $statistics = [
                'total' => (clone $baseQuery)->count(),
                'active' => $active,
                'acknowledged' => $acknowledged,
                'resolved' => $resolved,
                'unresolved' => $active + $acknowledged,
                'by_type' => $byType,
                'by_status' => $byStatus,
                'by_product' => $byProduct,
                'recent_unresolved' => $recentUnresolved,
                'summary' => [
                    'critical_alerts' => $active, // Treat active as critical
                    'needs_attention' => $active,
                    'total_alerts' => (clone $baseQuery)->count(),
                    'resolved_rate' => (clone $baseQuery)->count() > 0
                        ? round(($resolved / (clone $baseQuery)->count()) * 100, 2)
                        : 0
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $statistics,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve alert statistics: ' . $e->getMessage(),
            ], 500);
        }
    }
}
