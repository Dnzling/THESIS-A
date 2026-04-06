<?php

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceBudget;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetController extends Controller
{
    /**
     * GET /api/procurement/budgets/summary
     * Returns budget vs spend by category/department and monthly trend for the selected year.
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        $storeId = (int) $user->store_id;
        $year = (int) $request->get('year', now()->year);
        $branchId = $request->get('branch_id'); // optional scope

        $periodStart = now()->setDate($year, 1, 1)->startOfDay();
        $periodEnd = now()->setDate($year, 12, 31)->endOfDay();

        // Budgets for the store in the year range
        $budgets = FinanceBudget::query()
            ->where('store_id', $storeId)
            ->whereDate('period_start', '<=', $periodEnd)
            ->whereDate('period_end', '>=', $periodStart)
            ->get();

        // Actual spend from POs
        $poQuery = PurchaseOrder::query()
            ->where('store_id', $storeId)
            ->whereBetween('created_at', [$periodStart, $periodEnd]);

        if ($branchId) {
            $poQuery->where('branch_id', $branchId);
        }

        $poSpendByMonth = $poQuery->clone()
            ->groupByRaw('DATE_FORMAT(created_at, "%Y-%m")')
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month')
            ->selectRaw('SUM(total_amount) as actual')
            ->orderBy('month')
            ->get();

        // Map budgets to categories/departments
        $categories = $budgets->groupBy('category')->map(function ($rows, $category) use ($poQuery) {
            $budgetTotal = (float) $rows->sum('amount');
            $spentBudget = (float) $rows->sum('spent_amount');

            // Actual spend for this category (using PO items categories if available)
            $actualSpend = DB::table('purchase_order_items')
                ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
                ->leftJoin('products', 'products.id', '=', 'purchase_order_items.product_id')
                ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
                ->when($category, function ($q) use ($category) {
                    $q->where('categories.category_name', $category);
                }, function ($q) {
                    $q->whereNull('categories.category_name');
                })
                ->whereIn('purchase_orders.id', $poQuery->clone()->select('id'))
                ->sum('purchase_order_items.line_total');

            $remaining = $budgetTotal - ($spentBudget ?: $actualSpend);
            $percent = $budgetTotal > 0 ? round((($spentBudget ?: $actualSpend) / $budgetTotal) * 100) : 0;

            return [
                'category_name' => $category ?: 'Uncategorized',
                'budget' => $budgetTotal,
                'spent' => $spentBudget ?: (float) $actualSpend,
                'remaining' => $remaining,
                'percentage' => $percent,
            ];
        })->values();

        // Build monthly series with budget baseline (average monthly budget)
        $monthlyBudget = $budgets->sum('amount') / 12;
        $monthly = [];
        for ($m = 1; $m <= 12; $m++) {
            $label = sprintf('%d-%02d', $year, $m);
            $match = $poSpendByMonth->firstWhere('month', $label);
            $actual = $match ? (float) $match->actual : 0.0;
            $monthly[] = [
                'month' => $label,
                'budgeted' => $monthlyBudget,
                'actual' => $actual,
                'variance' => $actual - $monthlyBudget,
            ];
        }

        $summary = [
            'annual_budget' => (float) $budgets->sum('amount'),
            'monthly_budget' => (float) $monthlyBudget,
            'ytd_spend' => (float) $poQuery->sum('total_amount'),
            'current_month_spend' => (float) $poQuery->clone()
                ->whereBetween('created_at', [now()->startOfMonth(), now()])
                ->sum('total_amount'),
            'budget_status' => 'On Track',
            'categories' => $categories,
            'monthly' => $monthly,
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }
}
