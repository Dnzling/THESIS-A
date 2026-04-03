<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Hr\Payroll;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancePayrollController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Payroll::with(['employee', 'payPeriod'])
            ->byUserStore();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('pay_period_id')) {
            $query->where('pay_period_id', $request->pay_period_id);
        }

        $payrolls = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $payrolls,
        ]);
    }
}
