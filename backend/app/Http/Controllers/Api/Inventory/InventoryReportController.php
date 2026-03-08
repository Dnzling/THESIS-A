<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\ReportingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryReportController extends Controller
{
    public function __construct(protected ReportingService $reportingService)
    {
        $this->middleware('auth:sanctum');
        $this->middleware('can:inventory.view_reports');
    }

    /**
     * Get branch summary with KPIs
     */
    public function branchSummary(?int $days = null): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;
            $branchId = auth()->user()->branch_id;

            $summary = $this->reportingService->getBranchSummary($storeId, $branchId, $days);

            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get store-wide consolidated summary
     */
    public function storeSummary(?int $days = null): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;

            $this->authorize('inventory.view_all_branches');

            $summary = $this->reportingService->getStoreSummary($storeId, $days);

            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get movement trends
     */
    public function movements(Request $request): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;
            $branchId = auth()->user()->branch_id;
            $days = $request->query('days', 30);

            $trends = $this->reportingService->getMovementTrends($storeId, $branchId, $days);

            return response()->json([
                'success' => true,
                'data' => $trends,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get inventory value by category
     */
    public function valueByCategory(): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;
            $branchId = auth()->user()->branch_id;

            $values = $this->reportingService->getValueByCategory($storeId, $branchId);

            return response()->json([
                'success' => true,
                'data' => $values,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get slow-moving items
     */
    public function slowMovers(Request $request): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;
            $branchId = auth()->user()->branch_id;
            $days = $request->query('days', 90);
            $minValue = $request->query('min_value', 5000);

            $items = $this->reportingService->getSlowMovers($storeId, $branchId, $days, $minValue);

            return response()->json([
                'success' => true,
                'data' => $items,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get fast-moving items
     */
    public function fastMovers(Request $request): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;
            $branchId = auth()->user()->branch_id;
            $days = $request->query('days', 30);
            $minQty = $request->query('min_qty', 50);

            $items = $this->reportingService->getFastMovers($storeId, $branchId, $days, $minQty);

            return response()->json([
                'success' => true,
                'data' => $items,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get transfer metrics
     */
    public function transfers(Request $request): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;
            $branchId = auth()->user()->branch_id;
            $days = $request->query('days', 30);

            $metrics = $this->reportingService->getTransferMetrics($storeId, $branchId, $days);

            return response()->json([
                'success' => true,
                'data' => $metrics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get stock aging report
     */
    public function aging(): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;
            $branchId = auth()->user()->branch_id;

            $aging = $this->reportingService->getAgingReport($storeId, $branchId);

            return response()->json([
                'success' => true,
                'data' => $aging,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
