<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceExpense;
use App\Models\Store\BranchExpenseRequest;
use App\Services\Finance\CashflowService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $storeId = (int) auth()->user()->store_id;
        $query = FinanceExpense::with([
            'requestedBy',
            'approvedBy',
            'paidBy',
            'branch:id,name',
            'branchExpenseRequest:id,finance_expense_id,reference_number',
        ])
            ->where('store_id', $storeId);

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

        if ($request->filled('search')) {
            $term = trim($request->string('search')->toString());
            $query->where(function ($expenses) use ($term) {
                $expenses->where('reference_number', 'like', "%{$term}%")
                    ->orWhere('payment_reference', 'like', "%{$term}%")
                    ->orWhere('category', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhereHas('branchExpenseRequest', fn ($requests) => $requests->where('reference_number', 'like', "%{$term}%"));
            });
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('expense_date', [$request->start_date, $request->end_date]);
        }

        $expenses = $query->orderBy('expense_date', 'desc')
            ->paginate($request->get('per_page', 15));

        $periodEnd = now()->endOfDay();
        $periodStart = $periodEnd->copy()->subDays(27)->startOfDay();
        $previousEnd = $periodStart->copy()->subDay()->endOfDay();
        $previousStart = $previousEnd->copy()->subDays(27)->startOfDay();
        $summarizePeriod = static function (Carbon $start, Carbon $end) use ($storeId): array {
            $summary = FinanceExpense::query()
                ->where('store_id', $storeId)
                ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
                ->selectRaw(
                    'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as paid, COALESCE(SUM(amount), 0) as total_amount',
                    ['pending_approval', 'approved', 'paid']
                )
                ->first();

            return [
                'pending' => (int) ($summary->pending ?? 0),
                'approved' => (int) ($summary->approved ?? 0),
                'paid' => (int) ($summary->paid ?? 0),
                'total_amount' => (float) ($summary->total_amount ?? 0),
            ];
        };

        return response()->json([
            'success' => true,
            'data' => $expenses,
            'summary' => [
                'current' => $summarizePeriod($periodStart, $periodEnd),
                'previous' => $summarizePeriod($previousStart, $previousEnd),
                'period' => [
                    'start' => $periodStart->toDateString(),
                    'end' => $periodEnd->toDateString(),
                    'previous_start' => $previousStart->toDateString(),
                    'previous_end' => $previousEnd->toDateString(),
                ],
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $expense = FinanceExpense::with(['requestedBy', 'approvedBy', 'paidBy', 'branch:id,name'])
            ->where('store_id', auth()->user()->store_id)
            ->findOrFail($id);

        $branchRequest = $expense->reference_type === 'branch_expense_request'
            ? BranchExpenseRequest::query()
                ->where('store_id', $expense->store_id)
                ->whereKey($expense->reference_id)
                ->where('finance_expense_id', $expense->id)
                ->first(['id', 'finance_expense_id', 'reference_number', 'title', 'category', 'requested_date', 'estimated_amount', 'actual_amount', 'receipt_path'])
            : null;
        $data = $expense->toArray();
        if ($branchRequest) {
            $receiptPath = $branchRequest->receipt_path;
            $data['branch_expense_request'] = [
                'reference_number' => $branchRequest->reference_number,
                'title' => $branchRequest->title,
                'category' => $branchRequest->category,
                'requested_date' => $branchRequest->requested_date,
                'estimated_amount' => $branchRequest->estimated_amount,
                'actual_amount' => $branchRequest->actual_amount,
                'receipt_url' => $receiptPath ? url("/api/finance/expenses/{$expense->id}/receipt") : null,
                'receipt_mime' => $receiptPath && \Illuminate\Support\Facades\Storage::disk('local')->exists($receiptPath)
                    ? \Illuminate\Support\Facades\Storage::disk('local')->mimeType($receiptPath)
                    : null,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function benefitRequest(Request $request, int $id): JsonResponse
    {
        $benefitRequest = \App\Models\Hr\EmployeeBenefitRequest::query()
            ->where('store_id', (int) $request->user()->store_id)
            ->with(['employee.user:id,fname,lname', 'deductionType:id,name', 'reviewer:id,fname,lname'])
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $benefitRequest]);
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

        $expense = DB::transaction(function () use ($validated) {
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

            $expense->update([
                'reference_number' => sprintf('EXP-%s-%06d', now()->format('Ymd-His'), $expense->id),
            ]);

            return $expense->fresh();
        });

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
        if (!$this->userHasAllPermissionSets([['finance.expenses.approve']])) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to approve expenses.'], 403);
        }

        $expense = FinanceExpense::where('store_id', auth()->user()->store_id)->findOrFail($id);

        if ($expense->status !== 'pending_approval') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending expenses can be approved',
            ], 422);
        }

        DB::transaction(function () use ($expense): void {
            $expense->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
            if ($expense->reference_type === 'branch_expense_request') {
                BranchExpenseRequest::query()
                    ->where('store_id', $expense->store_id)
                    ->whereKey($expense->reference_id)
                    ->where('finance_expense_id', $expense->id)
                    ->update(['status' => 'finance_approved']);
            }
        });

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
        if ($expense->reference_type === 'branch_expense_request' && $expense->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Approve this branch expense before recording its payment.',
            ], 422);
        }

        $validated = $request->validate([
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
            'payment_reference' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:255',
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
                    'payment_reference' => $validated['payment_reference'] ?? $validated['reference_number'] ?? null,
                    'paid_by' => auth()->id(),
                    'paid_at' => now(),
                ]);
                if ($expense->reference_type === 'branch_expense_request') {
                    BranchExpenseRequest::query()
                        ->where('store_id', $expense->store_id)
                        ->whereKey($expense->reference_id)
                        ->where('finance_expense_id', $expense->id)
                        ->update(['status' => 'paid']);
                }
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
        if (!$this->userHasAllPermissionSets([['finance.expenses.approve']])) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to reject expenses.'], 403);
        }

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

        DB::transaction(function () use ($expense, $validated): void {
            $expense->update([
                'status' => 'rejected',
                'notes' => trim(($expense->notes ?? '') . "\n\nRejection: " . $validated['notes']),
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
            if ($expense->reference_type === 'branch_expense_request') {
                BranchExpenseRequest::query()
                    ->where('store_id', $expense->store_id)
                    ->whereKey($expense->reference_id)
                    ->where('finance_expense_id', $expense->id)
                    ->update(['status' => 'finance_rejected']);
            }
        });

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
