<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Return aggregated stats for admin dashboard
     */
    public function index(Request $request)
    {
        try {
            $data = [];

            $now = now();

            // Stores count
            $data['stores_count'] = Schema::hasTable('stores') ? DB::table('stores')->count() : 0;

            $data['active_stores'] = $data['stores_count'];
            if (Schema::hasTable('stores') && Schema::hasColumn('stores', 'status')) {
                $data['active_stores'] = DB::table('stores')->where('status', 'active')->count();
            }

            // New stores this week
            $data['new_stores_this_week'] = Schema::hasTable('stores')
                ? DB::table('stores')->where('created_at', '>=', $now->copy()->subDays(7))->count()
                : 0;

            // Active subscriptions count (if subscriptions table exists)
            $data['active_subscriptions'] = Schema::hasTable('subscriptions') ? DB::table('subscriptions')->where('status', 'active')->count() : 0;

            // Subscription growth vs last month (fallback to 0)
            $data['subscription_growth'] = 0;
            if (Schema::hasTable('subscriptions') && Schema::hasColumn('subscriptions', 'created_at')) {
                $currentMonth = DB::table('subscriptions')
                    ->whereRaw("MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")
                    ->count();
                $prevMonth = DB::table('subscriptions')
                    ->whereRaw("MONTH(created_at) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) AND YEAR(created_at) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)")
                    ->count();
                if ($prevMonth > 0) {
                    $data['subscription_growth'] = round((($currentMonth - $prevMonth) / $prevMonth) * 100, 2);
                }
            }

            // Pending validations (store or supplier portals)
            $pending = 0;
            if (Schema::hasTable('store_verifications')) {
                if (Schema::hasColumn('store_verifications', 'status')) {
                    $pending += DB::table('store_verifications')->where('status', 'pending')->count();
                } else {
                    // fallback: count all rows as pending if status column is missing
                    $pending += DB::table('store_verifications')->count();
                }
            }
            if (Schema::hasTable('supplier_portals')) {
                if (Schema::hasColumn('supplier_portals', 'status')) {
                    $pending += DB::table('supplier_portals')->where('status', 'pending')->count();
                } else {
                    $pending += DB::table('supplier_portals')->count();
                }
            }
            $data['pending_validations'] = $pending;

            // Only the platform revenue ledger represents money earned by the
            // platform. Store order totals and unrelated payments are not
            // platform income. A populated paid_at marks collected revenue.
            $revenue = $this->buildRevenueSummary();
            $data = array_merge($data, $revenue);

            // Revenue series (monthly + yearly)
            $data['revenue_series'] = $this->buildRevenueSeries();

            // Store growth series
            $data['store_growth_series'] = $this->buildStoreGrowthSeries();

            // recent activities (simple fetch from activity_logs if exists)
            $recentActivities = [];
            if (Schema::hasTable('activity_logs')) {
                $recentActivities = DB::table('activity_logs')
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function ($row) {
                        return [
                            'time' => $row->created_at,
                            'action' => $row->action ?? ($row->type ?? 'Activity'),
                            'description' => $row->description ?? null,
                            'status' => $row->status ?? 'Completed'
                        ];
                    })->toArray();
            }

            $data['recent_activities'] = $recentActivities;

            $data['recent_payments'] = $this->buildRecentPayments();

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function buildRevenueSeries(): array
    {
        $monthlyLabels = [];
        $monthlyPlatform = [];
        $monthlySubscription = [];
        $monthlyCommission = [];

        for ($i = 11; $i >= 0; $i--) {
            $label = now()->copy()->subMonths($i)->format('M');
            $monthKey = now()->copy()->subMonths($i)->format('Y-m');
            $monthlyLabels[] = $label;
            $monthlyPlatform[$monthKey] = 0;
            $monthlySubscription[$monthKey] = 0;
            $monthlyCommission[$monthKey] = 0;
        }

        if (Schema::hasTable('platform_revenues')) {
            $rows = DB::table('platform_revenues')
                ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as ym, source, SUM(amount) as total")
                ->whereNotNull('paid_at')
                ->where('paid_at', '>=', now()->copy()->subMonths(11)->startOfMonth())
                ->whereIn('source', ['subscription_upgrade', 'order_commission'])
                ->groupBy('ym', 'source')
                ->get();
            foreach ($rows as $row) {
                if (!array_key_exists($row->ym, $monthlyPlatform)) {
                    continue;
                }
                $amount = (float) $row->total;
                $monthlyPlatform[$row->ym] += $amount;
                if ($row->source === 'subscription_upgrade') {
                    $monthlySubscription[$row->ym] += $amount;
                } else {
                    $monthlyCommission[$row->ym] += $amount;
                }
            }
        }

        $yearlyLabels = [];
        $yearlyPlatform = [];
        $yearlySubscription = [];
        $yearlyCommission = [];
        for ($i = 4; $i >= 0; $i--) {
            $year = (int) now()->copy()->subYears($i)->format('Y');
            $yearlyLabels[] = (string) $year;
            $yearlyPlatform[$year] = 0;
            $yearlySubscription[$year] = 0;
            $yearlyCommission[$year] = 0;
        }

        if (Schema::hasTable('platform_revenues')) {
            $rows = DB::table('platform_revenues')
                ->selectRaw('YEAR(paid_at) as yr, source, SUM(amount) as total')
                ->whereNotNull('paid_at')
                ->where('paid_at', '>=', now()->copy()->subYears(4)->startOfYear())
                ->whereIn('source', ['subscription_upgrade', 'order_commission'])
                ->groupBy('yr', 'source')
                ->get();
            foreach ($rows as $row) {
                $year = (int) $row->yr;
                if (!array_key_exists($year, $yearlyPlatform)) {
                    continue;
                }
                $amount = (float) $row->total;
                $yearlyPlatform[$year] += $amount;
                if ($row->source === 'subscription_upgrade') {
                    $yearlySubscription[$year] += $amount;
                } else {
                    $yearlyCommission[$year] += $amount;
                }
            }
        }

        return [
            'monthly' => [
                'labels' => $monthlyLabels,
                'platformRevenue' => array_values($monthlyPlatform),
                'subscriptionRevenue' => array_values($monthlySubscription),
                'commissionRevenue' => array_values($monthlyCommission),
            ],
            'yearly' => [
                'labels' => $yearlyLabels,
                'platformRevenue' => array_values($yearlyPlatform),
                'subscriptionRevenue' => array_values($yearlySubscription),
                'commissionRevenue' => array_values($yearlyCommission),
            ],
        ];
    }

    private function buildRevenueSummary(): array
    {
        $empty = [
            'commission_revenue' => 0.0,
            'subscription_revenue' => 0.0,
            'monthly_commission_revenue' => 0.0,
            'monthly_subscription_revenue' => 0.0,
            'monthly_revenue' => 0.0,
            'revenue_growth' => 0.0,
            'total_platform_revenue' => 0.0,
        ];

        if (!Schema::hasTable('platform_revenues')) {
            return $empty;
        }

        $base = DB::table('platform_revenues')
            ->whereNotNull('paid_at')
            ->whereIn('source', ['subscription_upgrade', 'order_commission']);
        $monthStart = now()->startOfMonth();
        $nextMonthStart = now()->copy()->addMonth()->startOfMonth();
        $previousMonthStart = now()->copy()->subMonth()->startOfMonth();

        $commission = (float) (clone $base)->where('source', 'order_commission')->sum('amount');
        $subscription = (float) (clone $base)->where('source', 'subscription_upgrade')->sum('amount');
        $monthlyCommission = (float) (clone $base)->where('source', 'order_commission')
            ->where('paid_at', '>=', $monthStart)
            ->where('paid_at', '<', $nextMonthStart)
            ->sum('amount');
        $monthlySubscription = (float) (clone $base)->where('source', 'subscription_upgrade')
            ->where('paid_at', '>=', $monthStart)
            ->where('paid_at', '<', $nextMonthStart)
            ->sum('amount');
        $monthlyTotal = $monthlyCommission + $monthlySubscription;
        $previousMonthTotal = (float) (clone $base)
            ->where('paid_at', '>=', $previousMonthStart)
            ->where('paid_at', '<', $monthStart)
            ->sum('amount');

        return [
            'commission_revenue' => $commission,
            'subscription_revenue' => $subscription,
            'monthly_commission_revenue' => $monthlyCommission,
            'monthly_subscription_revenue' => $monthlySubscription,
            'monthly_revenue' => $monthlyTotal,
            'revenue_growth' => $previousMonthTotal > 0
                ? round((($monthlyTotal - $previousMonthTotal) / $previousMonthTotal) * 100, 2)
                : 0.0,
            'total_platform_revenue' => $commission + $subscription,
        ];
    }

    private function buildStoreGrowthSeries(): array
    {
        $labels = [];
        $newStores = [];
        $activeStores = [];

        for ($i = 5; $i >= 0; $i--) {
            $labels[] = now()->copy()->subMonths($i)->format('M');
            $newStores[] = 0;
            $activeStores[] = 0;
        }

        if (!Schema::hasTable('stores')) {
            return [
                'labels' => $labels,
                'newStores' => $newStores,
                'activeStores' => $activeStores,
            ];
        }

        $start = now()->copy()->subMonths(5)->startOfMonth();
        $rows = DB::table('stores')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as total")
            ->where('created_at', '>=', $start)
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $index = 0;
        for ($i = 5; $i >= 0; $i--) {
            $key = now()->copy()->subMonths($i)->format('Y-m');
            $newStores[$index] = (int) ($rows[$key] ?? 0);
            $index++;
        }

        $running = 0;
        foreach ($newStores as $count) {
            $running += $count;
            $activeStores[] = $running;
        }

        $activeStores = array_slice($activeStores, count($newStores));

        return [
            'labels' => $labels,
            'newStores' => $newStores,
            'activeStores' => $activeStores,
        ];
    }

    private function buildRecentPayments(): array
    {
        if (!Schema::hasTable('paymongo_intents')) {
            return [];
        }

        $query = DB::table('paymongo_intents as pi')
            ->leftJoin('stores as s', 's.id', '=', 'pi.store_id')
            ->select([
                'pi.payment_intent_id',
                'pi.amount',
                'pi.currency',
                'pi.status',
                'pi.payable_type',
                'pi.payable_id',
                'pi.created_at',
                DB::raw("COALESCE(s.name, 'Unknown Store') as store_name"),
                DB::raw("COALESCE(s.status, 'unknown') as store_status"),
            ])
            ->orderByDesc('pi.created_at')
            ->limit(10);

        return $query->get()->map(function ($row) {
            $status = strtolower((string) ($row->status ?? 'unknown'));
            $payableType = (string) ($row->payable_type ?? '');

            return [
                'store' => $row->store_name,
                'store_status' => $row->store_status,
                'amount' => ((float) ($row->amount ?? 0)) / 100,
                'currency' => $row->currency ?: 'PHP',
                'status' => $status,
                'date' => $row->created_at,
                'payment_intent_id' => $row->payment_intent_id,
                'type' => $this->humanizePaymentType($payableType),
            ];
        })->toArray();
    }

    private function humanizePaymentType(string $type): string
    {
        return match ($type) {
            'subscription_upgrade' => 'Subscription',
            'invoice' => 'Invoice',
            'sales_order' => 'Sales Order',
            'ecommerce_order' => 'Ecommerce Order',
            'cashflow_topup' => 'Cashflow Top-up',
            default => $type !== '' ? str_replace('_', ' ', ucwords($type, '_')) : 'Payment',
        };
    }
}
