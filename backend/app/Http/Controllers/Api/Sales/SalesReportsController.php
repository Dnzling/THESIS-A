<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReportsController extends Controller
{
    public function summary(Request $request)
    {
        $user = $request->user();
        $storeId = $user?->store_id;

        if (!$storeId) {
            return response()->json(['data' => []]);
        }

        $start = $request->input('start_date');
        $end = $request->input('end_date');

        $posQuery = DB::table('sales_pos_orders')->where('store_id', $storeId);
        $ecomQuery = DB::table('ecommerce_orders')->where('store_id', $storeId);

        if ($start) {
            $posQuery->whereDate('created_at', '>=', $start);
            $ecomQuery->whereDate('created_at', '>=', $start);
        }
        if ($end) {
            $posQuery->whereDate('created_at', '<=', $end);
            $ecomQuery->whereDate('created_at', '<=', $end);
        }

        $posTotal = (float) $posQuery->sum('total_amount');
        $ecomTotal = (float) $ecomQuery->sum('total_amount');
        $totalOrders = (int) $posQuery->count() + (int) $ecomQuery->count();

        $data = [
            'pos_total' => $posTotal,
            'ecommerce_total' => $ecomTotal,
            'grand_total' => $posTotal + $ecomTotal,
            'orders_count' => $totalOrders,
        ];

        return response()->json(['data' => $data]);
    }
}
