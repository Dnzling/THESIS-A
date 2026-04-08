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

            // Monthly revenue (sum of payments this month) fallback to payments table
            $monthlyRevenue = 0;
            if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'amount')) {
                $monthlyRevenue = DB::table('payments')
                    ->whereRaw("MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")
                    ->sum('amount');
            } elseif (Schema::hasTable('sales') && Schema::hasColumn('sales', 'total_amount')) {
                $monthlyRevenue = DB::table('sales')
                    ->whereRaw("MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")
                    ->sum('total_amount');
            }
            $data['monthly_revenue'] = (float) $monthlyRevenue;

            // Revenue growth vs last month
            $data['revenue_growth'] = 0;
            if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'amount')) {
                $currentMonth = DB::table('payments')
                    ->whereRaw("MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")
                    ->sum('amount');
                $prevMonth = DB::table('payments')
                    ->whereRaw("MONTH(created_at) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) AND YEAR(created_at) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)")
                    ->sum('amount');
                if ($prevMonth > 0) {
                    $data['revenue_growth'] = round((($currentMonth - $prevMonth) / $prevMonth) * 100, 2);
                }
            } elseif (Schema::hasTable('sales') && Schema::hasColumn('sales', 'total_amount')) {
                $currentMonth = DB::table('sales')
                    ->whereRaw("MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")
                    ->sum('total_amount');
                $prevMonth = DB::table('sales')
                    ->whereRaw("MONTH(created_at) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) AND YEAR(created_at) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)")
                    ->sum('total_amount');
                if ($prevMonth > 0) {
                    $data['revenue_growth'] = round((($currentMonth - $prevMonth) / $prevMonth) * 100, 2);
                }
            }

            // Total platform revenue
            $totalRevenue = 0;
            if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'amount')) {
                $totalRevenue = DB::table('payments')->sum('amount');
            } elseif (Schema::hasTable('sales') && Schema::hasColumn('sales', 'total_amount')) {
                $totalRevenue = DB::table('sales')->sum('total_amount');
            }
            $data['total_platform_revenue'] = (float) $totalRevenue;

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

        for ($i = 11; $i >= 0; $i--) {
            $label = now()->copy()->subMonths($i)->format('M');
            $monthKey = now()->copy()->subMonths($i)->format('Y-m');
            $monthlyLabels[] = $label;
            $monthlyPlatform[$monthKey] = 0;
            $monthlySubscription[$monthKey] = 0;
        }

        if (Schema::hasTable('sales') && Schema::hasColumn('sales', 'total_amount')) {
            $rows = DB::table('sales')
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(total_amount) as total")
                ->where('created_at', '>=', now()->copy()->subMonths(11)->startOfMonth())
                ->groupBy('ym')
                ->pluck('total', 'ym');
            foreach ($rows as $ym => $total) {
                if (array_key_exists($ym, $monthlyPlatform)) {
                    $monthlyPlatform[$ym] = (float) $total;
                }
            }
        }

        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'amount')) {
            $rows = DB::table('payments')
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(amount) as total")
                ->where('created_at', '>=', now()->copy()->subMonths(11)->startOfMonth())
                ->groupBy('ym')
                ->pluck('total', 'ym');
            foreach ($rows as $ym => $total) {
                if (array_key_exists($ym, $monthlySubscription)) {
                    $monthlySubscription[$ym] = (float) $total;
                }
            }
        }

        // If no sales data, mirror payments to platform series
        if (array_sum($monthlyPlatform) === 0 && array_sum($monthlySubscription) > 0) {
            $monthlyPlatform = $monthlySubscription;
        }

        $yearlyLabels = [];
        $yearlyPlatform = [];
        $yearlySubscription = [];
        for ($i = 4; $i >= 0; $i--) {
            $year = (int) now()->copy()->subYears($i)->format('Y');
            $yearlyLabels[] = (string) $year;
            $yearlyPlatform[$year] = 0;
            $yearlySubscription[$year] = 0;
        }

        if (Schema::hasTable('sales') && Schema::hasColumn('sales', 'total_amount')) {
            $rows = DB::table('sales')
                ->selectRaw('YEAR(created_at) as yr, SUM(total_amount) as total')
                ->where('created_at', '>=', now()->copy()->subYears(4)->startOfYear())
                ->groupBy('yr')
                ->pluck('total', 'yr');
            foreach ($rows as $yr => $total) {
                if (array_key_exists((int) $yr, $yearlyPlatform)) {
                    $yearlyPlatform[(int) $yr] = (float) $total;
                }
            }
        }

        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'amount')) {
            $rows = DB::table('payments')
                ->selectRaw('YEAR(created_at) as yr, SUM(amount) as total')
                ->where('created_at', '>=', now()->copy()->subYears(4)->startOfYear())
                ->groupBy('yr')
                ->pluck('total', 'yr');
            foreach ($rows as $yr => $total) {
                if (array_key_exists((int) $yr, $yearlySubscription)) {
                    $yearlySubscription[(int) $yr] = (float) $total;
                }
            }
        }

        if (array_sum($yearlyPlatform) === 0 && array_sum($yearlySubscription) > 0) {
            $yearlyPlatform = $yearlySubscription;
        }

        return [
            'monthly' => [
                'labels' => $monthlyLabels,
                'platformRevenue' => array_values($monthlyPlatform),
                'subscriptionRevenue' => array_values($monthlySubscription),
            ],
            'yearly' => [
                'labels' => $yearlyLabels,
                'platformRevenue' => array_values($yearlyPlatform),
                'subscriptionRevenue' => array_values($yearlySubscription),
            ],
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
}
