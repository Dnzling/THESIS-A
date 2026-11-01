<?php

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceBudget;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetController extends Controller
{
    private const COMMITTED_PO_STATUSES = [
        'approved',
        'sent_to_supplier',
        'supplier_accepted',
        'in_transit',
        'pending_receipt',
        'partially_received',
        'delivered',
        'goods_received',
    ];

    /**
     * GET /api/procurement/budgets/summary
     * Shows store-wide budgets alongside committed purchase-order spend.
     */
    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'nullable|integer|min:2000|max:2100',
        ]);

        $user = $request->user();
        $storeId = (int) ($user?->store_id ?? 0);
        if ($storeId <= 0) {
            return response()->json(['success' => false, 'message' => 'Your account is not assigned to a store.'], 422);
        }

        $year = (int) ($validated['year'] ?? now()->year);
        $yearStart = CarbonImmutable::create($year, 1, 1)->startOfDay();
        $yearEnd = CarbonImmutable::create($year, 12, 31)->endOfDay();
        $budgets = FinanceBudget::query()
            ->where('store_id', $storeId)
            ->where(function ($query) {
                $query->whereNull('department')->orWhereRaw('LOWER(department) LIKE ?', ['procurement%']);
            })
            ->whereDate('period_start', '<=', $yearEnd->toDateString())
            ->whereDate('period_end', '>=', $yearStart->toDateString())
            ->get();

        $poQuery = PurchaseOrder::query()
            ->where('store_id', $storeId)
            ->whereIn('status', self::COMMITTED_PO_STATUSES)
            ->whereBetween('order_date', [$yearStart->toDateString(), $yearEnd->toDateString()]);

        $monthlyActual = (clone $poQuery)
            ->groupByRaw("DATE_FORMAT(order_date, '%Y-%m')")
            ->selectRaw("DATE_FORMAT(order_date, '%Y-%m') as month")
            ->selectRaw('SUM(total_amount) as actual')
            ->get()
            ->keyBy('month');

        $actualByCategory = DB::table('purchase_order_items as items')
            ->join('purchase_orders as orders', 'orders.id', '=', 'items.purchase_order_id')
            ->leftJoin('products', 'products.id', '=', 'items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('orders.store_id', $storeId)
            ->whereIn('orders.status', self::COMMITTED_PO_STATUSES)
            ->whereBetween('orders.order_date', [$yearStart->toDateString(), $yearEnd->toDateString()])
            ->groupBy('categories.category_name')
            ->selectRaw("COALESCE(categories.category_name, 'Uncategorized') as category_name")
            ->selectRaw('SUM(items.line_total) as spent')
            ->get();

        $budgetByCategory = [];
        $monthlyBudget = array_fill(1, 12, 0.0);
        $annualBudget = 0.0;

        foreach ($budgets as $budget) {
            $periodStart = CarbonImmutable::parse($budget->period_start)->startOfDay();
            $periodEnd = CarbonImmutable::parse($budget->period_end)->endOfDay();
            $periodDays = max(1, $periodStart->diffInDays($periodEnd) + 1);
            $amount = (float) $budget->amount;
            $categoryName = trim((string) $budget->category) ?: 'Uncategorized';
            $categoryKey = mb_strtolower($categoryName);

            $categoryOverlapStart = $periodStart->greaterThan($yearStart) ? $periodStart : $yearStart;
            $categoryOverlapEnd = $periodEnd->lessThan($yearEnd) ? $periodEnd : $yearEnd;
            $overlapDays = max(0, $categoryOverlapStart->diffInDays($categoryOverlapEnd) + 1);
            $allocatedAmount = $amount * ($overlapDays / $periodDays);
            $annualBudget += $allocatedAmount;
            $budgetByCategory[$categoryKey] ??= ['category_name' => $categoryName, 'budget' => 0.0];
            $budgetByCategory[$categoryKey]['budget'] += $allocatedAmount;

            for ($month = 1; $month <= 12; $month++) {
                $monthStart = CarbonImmutable::create($year, $month, 1)->startOfDay();
                $monthEnd = $monthStart->endOfMonth();
                $overlapStart = $periodStart->greaterThan($monthStart) ? $periodStart : $monthStart;
                $overlapEnd = $periodEnd->lessThan($monthEnd) ? $periodEnd : $monthEnd;

                if ($overlapStart->lessThanOrEqualTo($overlapEnd)) {
                    $daysInOverlap = $overlapStart->diffInDays($overlapEnd) + 1;
                    $monthlyBudget[$month] += $amount * ($daysInOverlap / $periodDays);
                }
            }
        }

        $spentByCategory = [];
        foreach ($actualByCategory as $row) {
            $name = trim((string) $row->category_name) ?: 'Uncategorized';
            $spentByCategory[mb_strtolower($name)] = [
                'category_name' => $name,
                'spent' => (float) $row->spent,
            ];
        }

        $categoryKeys = array_unique(array_merge(array_keys($budgetByCategory), array_keys($spentByCategory)));
        $categories = collect($categoryKeys)
            ->map(function (string $key) use ($budgetByCategory, $spentByCategory) {
                $name = $budgetByCategory[$key]['category_name'] ?? $spentByCategory[$key]['category_name'];
                $budget = (float) ($budgetByCategory[$key]['budget'] ?? 0);
                $spent = (float) ($spentByCategory[$key]['spent'] ?? 0);
                $percentage = $budget > 0 ? round(($spent / $budget) * 100, 1) : ($spent > 0 ? null : 0.0);

                return [
                    'category_name' => $name,
                    'budget' => round($budget, 2),
                    'spent' => round($spent, 2),
                    'remaining' => round($budget - $spent, 2),
                    'percentage' => $percentage,
                    'status' => $budget <= 0 && $spent > 0
                        ? 'unbudgeted'
                        : ($percentage >= 100 ? 'over_budget' : ($percentage >= 80 ? 'at_risk' : 'on_track')),
                ];
            })
            ->sortByDesc('spent')
            ->values();

        $monthly = collect(range(1, 12))->map(function (int $month) use ($year, $monthlyBudget, $monthlyActual) {
            $key = sprintf('%d-%02d', $year, $month);
            return [
                'month' => $key,
                'budgeted' => round($monthlyBudget[$month], 2),
                'actual' => round((float) ($monthlyActual->get($key)?->actual ?? 0), 2),
            ];
        });

        $periodSpend = (float) $poQuery->sum('total_amount');
        $asOf = $year < now()->year
            ? $yearEnd->toDateString()
            : ($year === now()->year ? now()->toDateString() : null);
        $ytdSpend = $asOf
            ? (float) (clone $poQuery)->whereDate('order_date', '<=', $asOf)->sum('total_amount')
            : 0.0;
        $currentMonthSpend = $year === now()->year
            ? (float) ($monthlyActual->get(now()->format('Y-m'))?->actual ?? 0)
            : 0.0;
        $utilization = $annualBudget > 0 ? ($ytdSpend / $annualBudget) * 100 : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'annual_budget' => round($annualBudget, 2),
                'monthly_budget' => round($annualBudget / 12, 2),
                'ytd_spend' => round($ytdSpend, 2),
                'period_spend' => round($periodSpend, 2),
                'current_month_spend' => round($currentMonthSpend, 2),
                'remaining_budget' => round($annualBudget - $ytdSpend, 2),
                'utilization_percentage' => round($utilization, 1),
                'budget_status' => $annualBudget <= 0 && $periodSpend > 0
                    ? 'Unbudgeted'
                    : ($utilization >= 100 ? 'Over Budget' : ($utilization >= 80 ? 'At Risk' : 'On Track')),
                'categories' => $categories,
                'monthly' => $monthly,
            ],
        ]);
    }
}
