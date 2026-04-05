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

            // Stores count
            $data['stores_count'] = Schema::hasTable('stores') ? DB::table('stores')->count() : 0;

            // Active subscriptions count (if subscriptions table exists)
            $data['active_subscriptions'] = Schema::hasTable('subscriptions') ? DB::table('subscriptions')->where('status', 'active')->count() : 0;

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
            if (Schema::hasTable('payments')) {
                $monthlyRevenue = DB::table('payments')
                    ->whereRaw("MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")
                    ->sum('amount');
            } elseif (Schema::hasTable('sales')) {
                $monthlyRevenue = DB::table('sales')
                    ->whereRaw("MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")
                    ->sum('total_amount');
            }
            $data['monthly_revenue'] = $monthlyRevenue;

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
}
