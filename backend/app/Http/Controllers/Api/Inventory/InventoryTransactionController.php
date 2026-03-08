<?php
// backend/app/Http/Controllers/Inventory/InventoryTransactionController.php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InventoryTransactionController extends Controller
{
    /**
     * List all inventory transactions
     * GET /api/inventory/transactions
     */
    public function index(Request $request): JsonResponse
    {
        $query = InventoryTransaction::with([
            'branch',
            'product',
            'variation',
            'relatedBranch',
            'createdBy'
        ])->where('store_id', auth()->user()->store_id);

        // Filters
        if ($request->has('branch_id')) {
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
            'createdBy',
            'reference'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $transaction,
        ]);
    }

    /**
     * Get transaction summary/statistics
     * GET /api/inventory/transactions/summary
     */
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

    /**
     * Get transaction history for a specific product
     * GET /api/inventory/transactions/product/{productId}
     */
    public function productHistory(Request $request, int $productId): JsonResponse
    {
        $query = InventoryTransaction::with([
            'branch',
            'variation',
            'createdBy'
        ])
        ->where('product_id', $productId)
        ->where('store_id', auth()->user()->store_id);

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('variation_id')) {
            $query->where('variation_id', $request->variation_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('transaction_date', [
                $request->start_date,
                $request->end_date
            ]);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Export transactions to CSV
     * GET /api/inventory/transactions/export
     */
    public function export(Request $request): JsonResponse
    {
        $query = InventoryTransaction::with([
            'branch',
            'product',
            'variation'
        ])->where('store_id', auth()->user()->store_id);

        // Apply filters
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('transaction_date', [
                $request->start_date,
                $request->end_date
            ]);
        }

        if ($request->has('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')->get();

        // Format for export
        $exportData = $transactions->map(function ($transaction) {
            return [
                'transaction_number' => $transaction->transaction_number,
                'date' => $transaction->transaction_date->format('Y-m-d H:i:s'),
                'branch' => $transaction->branch->name,
                'product' => $transaction->product->product_name,
                'variation' => $transaction->variation?->variation_name ?? 'N/A',
                'transaction_type' => $transaction->transaction_type,
                'quantity_before' => $transaction->quantity_before,
                'quantity_change' => $transaction->quantity_change,
                'quantity_after' => $transaction->quantity_after,
                'unit_cost' => number_format($transaction->unit_cost ?? 0, 2),
                'total_value' => number_format($transaction->total_value ?? 0, 2),
                'reference_type' => $transaction->reference_type ?? 'N/A',
                'notes' => $transaction->notes ?? '',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $exportData,
            'filename' => 'inventory_transactions_' . date('Y-m-d') . '.csv',
        ]);
    }

    /**
     * Get stock movement chart data
     * GET /api/inventory/transactions/chart
     */
    public function chartData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'product_id' => 'nullable|exists:products,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'group_by' => 'nullable|in:day,week,month',
        ]);

        $groupBy = $validated['group_by'] ?? 'day';
        $dateFormat = match($groupBy) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $query = InventoryTransaction::selectRaw("
                DATE_FORMAT(transaction_date, '{$dateFormat}') as period,
                transaction_type,
                SUM(CASE WHEN quantity_change > 0 THEN quantity_change ELSE 0 END) as quantity_in,
                SUM(CASE WHEN quantity_change < 0 THEN ABS(quantity_change) ELSE 0 END) as quantity_out,
                SUM(total_value) as total_value
            ")
            ->where('store_id', auth()->user()->store_id)
            ->whereBetween('transaction_date', [$validated['start_date'], $validated['end_date']]);

        if (isset($validated['branch_id'])) {
            $query->where('branch_id', $validated['branch_id']);
        }

        if (isset($validated['product_id'])) {
            $query->where('product_id', $validated['product_id']);
        }

        $chartData = $query->groupBy('period', 'transaction_type')
            ->orderBy('period')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $chartData,
        ]);
    }

    /**
     * Get recent transactions (for dashboard)
     * GET /api/inventory/transactions/recent
     */
    public function recent(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);

        $transactions = InventoryTransaction::with([
            'branch',
            'product',
            'variation',
            'createdBy'
        ])
        ->where('store_id', auth()->user()->store_id)
        ->orderBy('transaction_date', 'desc')
        ->limit($limit)
        ->get();

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }
}