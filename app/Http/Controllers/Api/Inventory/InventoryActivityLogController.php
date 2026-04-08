<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Core\ActivityLog;
use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\StockAdjustment;
use App\Models\Inventory\StockCount;
use App\Models\Inventory\StockTransfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $storeId = (int) ($user?->store_id ?? 0);
            $branchId = (int) ($request->branch_id ?? ($user?->branch_id ?? 0));

            $query = ActivityLog::query()
                ->with(['user', 'branch', 'department'])
                ->where('store_id', $storeId)
                ->where(function ($builder) {
                    $builder
                        ->where('action', 'like', 'inventory.%')
                        ->orWhere('entity_type', 'like', 'inventory.%');
                });

            if ($branchId > 0) {
                $query->where(function ($builder) use ($branchId) {
                    $builder->where('branch_id', $branchId)
                        ->orWhereRaw(
                            "JSON_VALID(meta) AND (
                                CAST(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.branch_id')) AS UNSIGNED) = ?
                                OR CAST(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.from_branch_id')) AS UNSIGNED) = ?
                                OR CAST(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.to_branch_id')) AS UNSIGNED) = ?
                            )",
                            [$branchId, $branchId, $branchId]
                        );
                });
            }

            if ($request->filled('search')) {
                $search = trim((string) $request->search);
                $query->where(function ($builder) use ($search) {
                    $builder->where('action', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('entity_type', 'like', "%{$search}%");
                });
            }

            if ($request->filled('entity_type')) {
                $query->where('entity_type', $request->entity_type);
            }

            if ($request->filled('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }

            if ($request->filled('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            $perPage = (int) $request->integer('per_page', 15);
            $logs = $query->orderByDesc('created_at')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $logs,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve inventory activity logs.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $storeId = (int) ($user?->store_id ?? 0);

            $log = ActivityLog::query()
                ->with(['user', 'branch', 'department'])
                ->where('store_id', $storeId)
                ->findOrFail($id);

            if (!str_starts_with((string) $log->action, 'inventory.') && !str_starts_with((string) $log->entity_type, 'inventory.')) {
                return response()->json([
                    'success' => false,
                    'message' => 'This log is not part of the inventory module.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $log,
                'source' => $this->resolveSource($log),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve inventory activity log detail.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function resolveSource(ActivityLog $log): ?array
    {
        if (empty($log->entity_type) || empty($log->entity_id)) {
            return null;
        }

        return match ($log->entity_type) {
            'inventory.stock_transfer' => $this->resolveStockTransfer((int) $log->entity_id),
            'inventory.stock_adjustment' => $this->resolveStockAdjustment((int) $log->entity_id),
            'inventory.stock_count' => $this->resolveStockCount((int) $log->entity_id),
            'inventory.branch_inventory' => $this->resolveBranchInventory((int) $log->entity_id),
            default => null,
        };
    }

    private function resolveStockTransfer(int $id): ?array
    {
        $record = StockTransfer::query()
            ->with(['fromBranch:id,name,branch_code', 'toBranch:id,name,branch_code', 'items.product:id,product_name,sku', 'items.variation:id,variation_name,variation_sku'])
            ->find($id);

        if (!$record) {
            return null;
        }

        return [
            'type' => 'stock_transfer',
            'record' => $record,
        ];
    }

    private function resolveStockAdjustment(int $id): ?array
    {
        $record = StockAdjustment::query()
            ->with(['branch:id,name,branch_code', 'items.product:id,product_name,sku', 'items.variation:id,variation_name,variation_sku'])
            ->find($id);

        if (!$record) {
            return null;
        }

        return [
            'type' => 'stock_adjustment',
            'record' => $record,
        ];
    }

    private function resolveStockCount(int $id): ?array
    {
        $record = StockCount::query()
            ->with(['branch:id,name,branch_code', 'countSheets.product:id,product_name,sku', 'countSheets.variation:id,variation_name,variation_sku'])
            ->find($id);

        if (!$record) {
            return null;
        }

        return [
            'type' => 'stock_count',
            'record' => $record,
        ];
    }

    private function resolveBranchInventory(int $id): ?array
    {
        $record = BranchInventory::query()
            ->with(['branch:id,name,branch_code', 'product:id,product_name,sku', 'variation:id,variation_name,variation_sku'])
            ->find($id);

        if (!$record) {
            return null;
        }

        return [
            'type' => 'branch_inventory',
            'record' => $record,
        ];
    }
}
