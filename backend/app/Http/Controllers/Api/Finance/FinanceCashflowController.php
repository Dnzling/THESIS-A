<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceCashflowTransaction;
use App\Services\Finance\CashflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceCashflowController extends Controller
{
    public function accountSummary(Request $request): JsonResponse
    {
        $storeId = (int) auth()->user()->store_id;
        $service = new CashflowService();
        $account = $service->getOrCreateOperatingAccount($storeId, auth()->id());

        $recent = FinanceCashflowTransaction::where('store_id', $storeId)
            ->latest('id')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'account' => $account,
                'available_balance' => (float) $account->current_balance,
                'recent_transactions' => $recent,
            ],
        ]);
    }

    public function topUp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $storeId = (int) auth()->user()->store_id;
        $service = new CashflowService();
        $transaction = $service->topUp(
            $storeId,
            (float) $validated['amount'],
            auth()->id(),
            $validated['description'] ?? 'Manual top-up',
            $validated['payment_method'] ?? null,
            ['notes' => $validated['notes'] ?? null]
        );

        $balance = $service->getAvailableBalance($storeId);

        return response()->json([
            'success' => true,
            'message' => 'Cashflow top-up recorded successfully',
            'data' => [
                'transaction' => $transaction,
                'available_balance' => $balance,
            ],
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $storeId = (int) auth()->user()->store_id;

        $query = FinanceCashflowTransaction::where('store_id', $storeId)
            ->orderByDesc('id');

        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }

        if ($request->filled('reference_type')) {
            $query->where('reference_type', $request->reference_type);
        }

        $transactions = $query->paginate($request->integer('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }
}
