<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\ReportingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryReportController extends Controller
{
    public function __construct(protected ReportingService $reportingService) {}

    /**
     * Get branch summary with KPIs
     */
    public function branchSummary(Request $request): JsonResponse
    {
        try {
            $storeId =  Auth::user()->store_id;
            $branchId =  Auth::user()->branch_id;
            $days = $request->query('days');
            $productType = $request->query('product_type');

            $summary = $this->reportingService->getBranchSummary($storeId, $branchId, $days, $productType);

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
    public function storeSummary(Request $request): JsonResponse
    {
        try {
            $storeId =  Auth::user()->store_id;
            $days = $request->query('days');
            $productType = $request->query('product_type');

            $this->authorize('inventory.reports.view_all_branches');

            $summary = $this->reportingService->getStoreSummary($storeId, $days, $productType);

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
            $storeId =  Auth::user()->store_id;
            $branchId =  Auth::user()->branch_id;
            $days = $request->query('days', 30);
            $productType = $request->query('product_type');

            $trends = $this->reportingService->getMovementTrends($storeId, $branchId, $days, $productType);

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
    public function valueByCategory(Request $request): JsonResponse
    {
        try {
            $storeId =  Auth::user()->store_id;
            $branchId =  Auth::user()->branch_id;
            $productType = $request->query('product_type');

            $values = $this->reportingService->getValueByCategory($storeId, $branchId, $productType);

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
            $storeId =  Auth::user()->store_id;
            $branchId =  Auth::user()->branch_id;
            $days = $request->query('days', 90);
            $minValue = $request->query('min_value', 5000);
            $productType = $request->query('product_type');

            $items = $this->reportingService->getSlowMovers($storeId, $branchId, $days, $minValue, $productType);

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
            $storeId = Auth::user()->store_id;
            $branchId =  Auth::user()->branch_id;
            $days = $request->query('days', 30);
            $minQty = $request->query('min_qty', 50);
            $productType = $request->query('product_type');

            $items = $this->reportingService->getFastMovers($storeId, $branchId, $days, $minQty, $productType);

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
            $storeId =  Auth::user()->store_id;
            $branchId =  Auth::user()->branch_id;
            $days = $request->query('days', 30);
            $productType = $request->query('product_type');

            $metrics = $this->reportingService->getTransferMetrics($storeId, $branchId, $days, $productType);

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
    public function aging(Request $request): JsonResponse
    {
        try {
            $storeId =  Auth::user()->store_id;
            $branchId =  Auth::user()->branch_id;
            $productType = $request->query('product_type');

            $aging = $this->reportingService->getAgingReport($storeId, $branchId, $productType);

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
