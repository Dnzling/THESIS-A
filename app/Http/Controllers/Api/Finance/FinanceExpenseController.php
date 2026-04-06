<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceExpense;
use App\Services\Finance\CashflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = FinanceExpense::with(['requestedBy', 'approvedBy', 'paidBy'])
            ->where('store_id', auth()->user()->store_id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('reference_type')) {
            $query->where('reference_type', $request->reference_type);
        }

        if ($request->filled('reference_id')) {
            $query->where('reference_id', $request->reference_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('expense_date', [$request->start_date, $request->end_date]);
        }

        $expenses = $query->orderBy('expense_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $expenses,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $expense = FinanceExpense::with(['requestedBy', 'approvedBy', 'paidBy'])
            ->where('store_id', auth()->user()->store_id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $expense,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department' => 'nullable|string',
            'category' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $autoApprove = $this->userHasAllPermissionSets([
            ['finance.expenses.manage'],
            ['finance.expenses.approve'],
        ]);

        $expense = FinanceExpense::create([
            'store_id' => auth()->user()->store_id,
            'department' => $validated['department'] ?? null,
            'category' => $validated['category'],
            'amount' => $validated['amount'],
            'expense_date' => $validated['expense_date'],
            'description' => $validated['description'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending_approval',
            'requested_by' => auth()->id(),
        ]);

        if ($autoApprove && $expense->status === 'pending_approval') {
            $expense->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $autoApprove
                ? 'Expense created and auto-approved'
                : 'Expense created successfully',
            'data' => $expense->fresh(),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $expense = FinanceExpense::where('store_id', auth()->user()->store_id)->findOrFail($id);

        if (!in_array($expense->status, ['draft', 'pending_approval'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft or pending expenses can be updated',
            ], 422);
        }

        $validated = $request->validate([
            'department' => 'nullable|string',
            'category' => 'sometimes|required|string',
            'amount' => 'sometimes|required|numeric|min:0',
            'expense_date' => 'sometimes|required|date',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $expense->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Expense updated successfully',
            'data' => $expense->fresh(),
        ]);
    }

    public function approve(int $id): JsonResponse
    {
        $expense = FinanceExpense::where('store_id', auth()->user()->store_id)->findOrFail($id);

        if ($expense->status !== 'pending_approval') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending expenses can be approved',
            ], 422);
        }

        $expense->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Expense approved successfully',
            'data' => $expense->fresh(),
        ]);
    }

    public function markPaid(Request $request, int $id): JsonResponse
    {
        $expense = FinanceExpense::where('store_id', auth()->user()->store_id)->findOrFail($id);

        if (!in_array($expense->status, ['approved', 'pending_approval'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only approved expenses can be marked as paid',
            ], 422);
        }

        $validated = $request->validate([
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
            'reference_number' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($expense, $validated) {
                $cashflow = new CashflowService();
                $cashflow->debit(
                    (int) $expense->store_id,
                    (float) $expense->amount,
                    'finance_expense',
                    (int) $expense->id,
                    auth()->id(),
                    'Expense payment #' . $expense->id,
                    $validated['payment_method'] ?? null,
                    [
                        'category' => $expense->category,
                        'department' => $expense->department,
                    ]
                );

                $expense->update([
                    'status' => 'paid',
                    'payment_method' => $validated['payment_method'],
                    'payment_date' => $validated['payment_date'],
                    'reference_number' => $validated['reference_number'] ?? null,
                    'paid_by' => auth()->id(),
                    'paid_at' => now(),
                ]);
            });
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Expense marked as paid',
            'data' => $expense->fresh(),
        ]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $expense = FinanceExpense::where('store_id', auth()->user()->store_id)->findOrFail($id);

        if ($expense->status !== 'pending_approval') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending expenses can be rejected',
            ], 422);
        }

        $validated = $request->validate([
            'notes' => 'required|string',
        ]);

        $expense->update([
            'status' => 'rejected',
            'notes' => trim(($expense->notes ?? '') . "\n\nRejection: " . $validated['notes']),
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Expense rejected',
            'data' => $expense->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $expense = FinanceExpense::where('store_id', auth()->user()->store_id)->findOrFail($id);
        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted successfully',
        ]);
    }
}
