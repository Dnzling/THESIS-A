<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesPayment;
use App\Models\CRM\CrmLead;
use App\Models\CRM\EcommerceChatThread;
use App\Models\CRM\EcommerceChatMessage;
use App\Models\CRM\EcommerceOrderReturn;
use App\Models\CRM\SalesReview;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CrmDashboardController extends Controller
{
    public function activity(Request $request): JsonResponse
    {
        $validated = $request->validate(['period' => ['required', 'in:week,month,year']]);
        $storeId = (int) ($request->user()?->store_id ?? 0);
        if ($storeId <= 0) {
            return response()->json(['message' => 'A store context is required.'], 422);
        }

        $period = $validated['period'];
        $now = now();
        $count = $period === 'week' ? 7 : ($period === 'month' ? 12 : 5);
        $start = $period === 'week' ? $now->copy()->subDays(6)->startOfDay()
            : ($period === 'month' ? $now->copy()->subMonths(11)->startOfMonth()
                : $now->copy()->subYears(4)->startOfYear());
        $buckets = [];
        for ($i = 0; $i < $count; $i++) {
            $date = $period === 'week' ? $start->copy()->addDays($i)
                : ($period === 'month' ? $start->copy()->addMonths($i) : $start->copy()->addYears($i));
            $key = $period === 'week' ? $date->toDateString()
                : ($period === 'month' ? $date->format('Y-m') : $date->format('Y'));
            $label = $period === 'week' ? $date->format('M j')
                : ($period === 'month' ? $date->format('M Y') : $key);
            $buckets[$key] = ['label' => $label, 'leads' => 0, 'chats' => 0, 'reviews' => 0, 'returns' => 0];
        }

        $driver = DB::connection()->getDriverName();
        $bucketSql = $period === 'week' ? 'DATE(created_at)'
            : ($driver === 'sqlite'
                ? ($period === 'month' ? "strftime('%Y-%m', created_at)" : "strftime('%Y', created_at)")
                : ($period === 'month' ? "DATE_FORMAT(created_at, '%Y-%m')" : 'YEAR(created_at)'));
        foreach ([
            'leads' => CrmLead::query(),
            'chats' => EcommerceChatThread::query(),
            'reviews' => SalesReview::query(),
            'returns' => EcommerceOrderReturn::query(),
        ] as $key => $query) {
            $totals = $query->where('store_id', $storeId)->where('created_at', '>=', $start)
                ->selectRaw("{$bucketSql} as bucket, COUNT(*) as total")
                ->groupByRaw($bucketSql)->pluck('total', 'bucket');
            foreach ($totals as $bucket => $total) {
                if (isset($buckets[(string) $bucket])) $buckets[(string) $bucket][$key] = (int) $total;
            }
        }

        return response()->json(['success' => true, 'data' => [
            'period' => $period,
            'points' => array_values($buckets),
        ]]);
    }

    public function overview(Request $request): JsonResponse
    {
        $days = (int) $request->integer('days', 30);
        if (!in_array($days, [7, 30, 90], true)) {
            return response()->json(['message' => 'Choose 7, 30, or 90 days.'], 422);
        }

        $storeId = (int) ($request->user()?->store_id ?? 0);
        if ($storeId <= 0) {
            return response()->json(['message' => 'A store context is required.'], 422);
        }

        $from = now()->subDays($days - 1)->startOfDay();
        $leads = CrmLead::query()->where('store_id', $storeId);
        $chats = EcommerceChatThread::query()->where('store_id', $storeId);
        $returns = EcommerceOrderReturn::query()->where('store_id', $storeId);
        $reviews = SalesReview::query()->where('store_id', $storeId);

        $stageCounts = (clone $leads)->selectRaw('stage, COUNT(*) as total')->groupBy('stage')->pluck('total', 'stage');
        $ratingCounts = (clone $reviews)->selectRaw('rating, COUNT(*) as total')->groupBy('rating')->pluck('total', 'rating');
        $returnCounts = (clone $returns)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $unreadChats = EcommerceChatMessage::query()->whereHas('thread', fn ($q) => $q->where('store_id', $storeId))
            ->where('sender_role', 'customer')->whereNull('read_at')->count();

        return response()->json(['success' => true, 'data' => [
            'days' => $days,
            'summary' => [
                'leads' => (clone $leads)->count(),
                'new_leads' => (clone $leads)->where('created_at', '>=', $from)->count(),
                'open_leads' => (clone $leads)->whereNotIn('stage', ['won', 'lost'])->count(),
                'chats' => (clone $chats)->count(),
                'unread_messages' => $unreadChats,
                'reviews' => (clone $reviews)->count(),
                'pending_reviews' => (clone $reviews)->where('status', 'pending')->count(),
                'average_rating' => round((float) ((clone $reviews)->avg('rating') ?? 0), 1),
                'returns' => (clone $returns)->count(),
                'open_returns' => (clone $returns)->whereNotIn('status', ['rejected', 'completed', 'cancelled', 'resolved'])->count(),
            ],
            'stages' => $stageCounts,
            'ratings' => $ratingCounts,
            'return_statuses' => $returnCounts,
            'recent_leads' => (clone $leads)->latest()->limit(5)->get(['id', 'lead_code', 'full_name', 'stage', 'created_at']),
            'recent_returns' => (clone $returns)->latest()->limit(5)->get(['id', 'return_number', 'status', 'created_at']),
        ]]);
    }

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
