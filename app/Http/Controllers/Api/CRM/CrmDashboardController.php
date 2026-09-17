<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesPayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CrmDashboardController extends Controller
{
    public function paymentAnalytics(Request $request): JsonResponse
    {
        $paymentQuery = SalesPayment::query();
        $this->applyStoreScope($request, $paymentQuery);

        $from = $request->date('from', now()->subDays(30)->startOfDay())?->startOfDay()
            ?? now()->subDays(30)->startOfDay();
        $to = $request->date('to', now()->endOfDay())?->endOfDay()
            ?? now()->endOfDay();

        $rangeQuery = (clone $paymentQuery)->whereBetween('created_at', [$from, $to]);
        $paidCount = (clone $rangeQuery)->where('status', 'paid')->count();
        $allCount = (clone $rangeQuery)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'total_payments' => $allCount,
                'paid_payments' => $paidCount,
                'failed_payments' => (clone $rangeQuery)->where('status', 'failed')->count(),
                'pending_payments' => (clone $rangeQuery)
                    ->whereIn('status', ['pending', 'processing', 'awaiting_payment_method'])
                    ->count(),
                'paid_amount' => (float) (clone $rangeQuery)->where('status', 'paid')->sum('amount'),
                'conversion_rate' => $allCount > 0 ? round(($paidCount / $allCount) * 100, 2) : 0,
                'method_breakdown' => (clone $rangeQuery)
                    ->select(
                        'payment_method',
                        DB::raw('COUNT(*) as total'),
                        DB::raw('SUM(CASE WHEN status = "paid" THEN amount ELSE 0 END) as paid_amount')
                    )
                    ->groupBy('payment_method')
                    ->get(),
                'daily_paid' => (clone $rangeQuery)
                    ->where('status', 'paid')
                    ->select(
                        DB::raw('DATE(COALESCE(paid_at, created_at)) as date'),
                        DB::raw('SUM(amount) as total')
                    )
                    ->groupBy(DB::raw('DATE(COALESCE(paid_at, created_at))'))
                    ->orderBy('date')
                    ->get(),
            ],
        ]);
    }

    private function applyStoreScope(Request $request, Builder $query): void
    {
        $user = $request->user();
        if ($user?->hasRole('super_admin')) {
            if ($request->filled('store_id')) {
                $query->where('store_id', (int) $request->input('store_id'));
            }
            return;
        }

        $query->where('store_id', (int) ($user?->store_id ?? 0));
    }
}
