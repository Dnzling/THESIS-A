<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardTrendController extends Controller
{
    public function sales(Request $request): JsonResponse
    {
        [$period, $start, $points] = $this->period($request);
        $storeId = $this->storeId($request);
        $pos = DB::table('sales_pos_orders')->where('store_id', $storeId)
            ->whereIn('payment_status', ['paid', 'succeeded', 'completed']);
        $ecommerce = DB::table('ecommerce_orders')->where('store_id', $storeId)
            ->whereIn('payment_status', ['paid', 'succeeded', 'completed']);
        $this->add($points, 'sales', $pos, $start, $period, 'total_amount', 'created_at', true);
        $this->add($points, 'sales', $ecommerce, $start, $period, 'total_amount', 'created_at', true);
        return $this->respond($period, $points);
    }

    public function ecommerce(Request $request): JsonResponse
    {
        [$period, $start, $points] = $this->period($request);
        $query = DB::table('ecommerce_orders')->where('store_id', $this->storeId($request))
            ->whereNotIn('status', ['cancelled', 'rejected']);
        $this->add($points, 'value', $query, $start, $period, 'total_amount');
        return $this->respond($period, $points);
    }

    public function ownerSales(Request $request): JsonResponse
    {
        [$period, $start, $points] = $this->period($request);
        $this->add($points, 'value', DB::table('sales_pos_orders')->where('store_id', $this->storeId($request)), $start, $period, 'total_amount');
        return $this->respond($period, $points);
    }

    public function branchSales(Request $request): JsonResponse
    {
        [$period, $start, $points] = $this->period($request);
        $storeId = $this->storeId($request);
        $branchId = (int) ($request->user()?->branch_id ?: $request->user()?->employee?->branch_id);
        abort_if($branchId <= 0 || !DB::table('branches')->where('id', $branchId)->where('store_id', $storeId)->exists(), 422, 'An assigned branch is required.');
        $this->add($points, 'value', DB::table('ecommerce_orders')->where('store_id', $storeId)->where('assigned_branch_id', $branchId), $start, $period, 'total_amount');
        $this->add($points, 'value', DB::table('sales_pos_orders')->where('store_id', $storeId)->where('branch_id', $branchId), $start, $period, 'total_amount');
        return $this->respond($period, $points);
    }

    public function finance(Request $request): JsonResponse
    {
        [$period, $start, $points] = $this->period($request);
        $storeId = $this->storeId($request);
        if (Schema::hasTable('invoices')) {
            $this->add($points, 'invoices', DB::table('invoices')->where('store_id', $storeId)->whereNull('deleted_at'), $start, $period, 'net_amount', 'due_date');
        }
        if (Schema::hasTable('finance_expenses')) {
            $this->add($points, 'expenses', DB::table('finance_expenses')->where('store_id', $storeId), $start, $period, 'amount');
        }
        if (Schema::hasTable('payrolls')) {
            $this->add($points, 'payroll', DB::table('payrolls')->join('employees', 'employees.id', '=', 'payrolls.employee_id')
                ->where('employees.store_id', $storeId), $start, $period, 'payrolls.net_salary', 'payrolls.created_at');
        }
        return $this->respond($period, $points);
    }

    private function period(Request $request): array
    {
        $period = $request->validate(['period' => ['required', 'in:week,month,year']])['period'];
        $now = now();
        $count = $period === 'week' ? 7 : ($period === 'month' ? 12 : 5);
        $start = $period === 'week' ? $now->copy()->subDays(6)->startOfDay()
            : ($period === 'month' ? $now->copy()->subMonths(11)->startOfMonth() : $now->copy()->subYears(4)->startOfYear());
        $points = [];
        for ($i = 0; $i < $count; $i++) {
            $date = $period === 'week' ? $start->copy()->addDays($i)
                : ($period === 'month' ? $start->copy()->addMonths($i) : $start->copy()->addYears($i));
            $key = $period === 'week' ? $date->toDateString() : ($period === 'month' ? $date->format('Y-m') : $date->format('Y'));
            $points[$key] = ['label' => $period === 'week' ? $date->format('M j') : ($period === 'month' ? $date->format('M Y') : $key), 'value' => 0, 'sales' => 0, 'orders' => 0, 'invoices' => 0, 'expenses' => 0, 'payroll' => 0];
        }
        return [$period, $start, $points];
    }

    private function add(array &$points, string $field, Builder $query, $start, string $period, string $amount, string $date = 'created_at', bool $orders = false): void
    {
        $driver = DB::connection()->getDriverName();
        $bucket = $period === 'week' ? "DATE({$date})" : ($driver === 'sqlite'
            ? ($period === 'month' ? "strftime('%Y-%m', {$date})" : "strftime('%Y', {$date})")
            : ($period === 'month' ? "DATE_FORMAT({$date}, '%Y-%m')" : "YEAR({$date})"));
        foreach ($query->where($date, '>=', $start)->selectRaw("{$bucket} as bucket, SUM({$amount}) as total, COUNT(*) as count")
            ->groupByRaw($bucket)->get() as $row) {
            $key = (string) $row->bucket;
            if (!isset($points[$key])) continue;
            $points[$key][$field] += (float) $row->total;
            if ($orders) $points[$key]['orders'] += (int) $row->count;
        }
    }

    private function storeId(Request $request): int
    {
        $storeId = (int) ($request->user()?->store_id ?: $request->user()?->employee?->store_id);
        abort_if($storeId <= 0, 422, 'A store context is required.');
        return $storeId;
    }

    private function respond(string $period, array $points): JsonResponse
    {
        return response()->json(['success' => true, 'data' => ['period' => $period, 'points' => array_values($points)]]);
    }
}
