<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceCashflowTransaction;
use App\Services\Finance\CashflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinanceCashflowController extends Controller
{
    public function accountSummary(Request $request): JsonResponse
    {
        $user = Auth::user();
        $storeId = (int) ($user?->store_id ?? 0);
        $service = new CashflowService();
        $account = $service->getOrCreateOperatingAccount($storeId, Auth::id());

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

        $user = Auth::user();
        $storeId = (int) ($user?->store_id ?? 0);
        $service = new CashflowService();
        $transaction = $service->topUp(
            $storeId,
            (float) $validated['amount'],
            Auth::id(),
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
        $user = Auth::user();
        $storeId = (int) ($user?->store_id ?? 0);

        $query = FinanceCashflowTransaction::where('store_id', $storeId)
            ->orderByDesc('id');

        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }

        if ($request->filled('reference_type')) {
            $query->where('reference_type', $request->reference_type);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($builder) use ($search) {
                $builder->where('description', 'like', "%{$search}%")
                    ->orWhere('payment_method', 'like', "%{$search}%")
                    ->orWhere('reference_type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->paginate($request->integer('per_page', 50));

        $summaryQuery = FinanceCashflowTransaction::where('store_id', $storeId);
        if ($request->filled('reference_type')) {
            $summaryQuery->where('reference_type', $request->reference_type);
        }
        if ($request->filled('date_from')) {
            $summaryQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $summaryQuery->whereDate('created_at', '<=', $request->date_to);
        }

        $totalIncoming = (float) (clone $summaryQuery)->where('direction', 'in')->sum('amount');
        $totalOutgoing = (float) (clone $summaryQuery)->where('direction', 'out')->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $transactions,
                'summary' => [
                    'incoming' => $totalIncoming,
                    'outgoing' => $totalOutgoing,
                    'net' => $totalIncoming - $totalOutgoing,
                ],
            ],
        ]);
    }

    public function adjust(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'direction' => 'required|in:in,out',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Enforce PayMongo-only funding source for top-ups.
        if (($validated['direction'] ?? '') === 'in') {
            $validated['payment_method'] = 'paymongo_gcash';
        }

        $user = Auth::user();
        $storeId = (int) ($user?->store_id ?? 0);
        $service = new CashflowService();

        try {
            $transaction = $validated['direction'] === 'in'
                ? $service->credit(
                    $storeId,
                    (float) $validated['amount'],
                    'manual_adjustment',
                    0,
                    Auth::id(),
                    $validated['description'] ?? 'Manual budget adjustment (add)',
                    $validated['payment_method'] ?? null,
                    ['notes' => $validated['notes'] ?? null]
                )
                : $service->debit(
                    $storeId,
                    (float) $validated['amount'],
                    'manual_adjustment',
                    0,
                    Auth::id(),
                    $validated['description'] ?? 'Manual budget adjustment (deduct)',
                    $validated['payment_method'] ?? null,
                    ['notes' => $validated['notes'] ?? null]
                );
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Budget adjustment recorded successfully.',
            'data' => [
                'transaction' => $transaction,
                'available_balance' => $service->getAvailableBalance($storeId),
            ],
        ]);
    }
}
