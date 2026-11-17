<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceExpense;
use App\Models\Store\Branch;
use App\Models\Store\BranchExpenseRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BranchExpenseController extends Controller
{
    private const CATEGORIES = ['maintenance', 'utilities', 'supplies', 'rent', 'transport', 'other'];

    public function index(Request $request): JsonResponse
    {
        $branch = $this->assignedBranch($request, 'store.expenses.view');
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:pending_approval,approved,rejected,submitted_to_finance,finance_approved,finance_rejected,paid'],
            'category' => ['nullable', 'string', 'in:' . implode(',', self::CATEGORIES)],
            'search' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $query = BranchExpenseRequest::query()
            ->where('store_id', $branch->store_id)
            ->where('branch_id', $branch->id)
            ->with(['creator:id,fname,lname', 'financeExpense:id,amount,status,payment_date']);

        if (!empty($validated['status'])) $query->where('status', $validated['status']);
        if (!empty($validated['category'])) $query->where('category', $validated['category']);
        if (!empty($validated['start_date'])) $query->whereDate('requested_date', '>=', $validated['start_date']);
        if (!empty($validated['end_date'])) $query->whereDate('requested_date', '<=', $validated['end_date']);
        if (!empty($validated['search'])) {
            $term = trim($validated['search']);
            $query->where(fn ($q) => $q->where('reference_number', 'like', "%{$term}%")
                ->orWhere('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%"));
        }

        $paidExpenses = FinanceExpense::query()
            ->where('store_id', $branch->store_id)
            ->where('branch_id', $branch->id)
            ->where('status', 'paid');
        if (!empty($validated['start_date'])) $paidExpenses->whereDate('expense_date', '>=', $validated['start_date']);
        if (!empty($validated['end_date'])) $paidExpenses->whereDate('expense_date', '<=', $validated['end_date']);

        return response()->json([
            'success' => true,
            'branch' => ['id' => $branch->id, 'name' => $branch->name],
            'summary' => [
                'paid_total' => (float) (clone $paidExpenses)->sum('amount'),
                'paid_count' => (int) (clone $paidExpenses)->count(),
                'pending_count' => (clone $query)->whereIn('status', ['pending_approval', 'submitted_to_finance'])->count(),
                'approved_count' => (clone $query)->where('status', 'approved')->count(),
            ],
            'data' => $query->orderByDesc('requested_date')->orderByDesc('id')->paginate((int) ($validated['per_page'] ?? 15)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $branch = $this->assignedBranch($request, 'store.expenses.manage');
        $autoApprove = $request->user()->hasPermissionTo('store.expenses.approve', (int) $branch->store_id);
        $validated = $request->validate([
            'category' => ['required', 'string', 'in:' . implode(',', self::CATEGORIES)],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:5000'],
            'requested_date' => ['required', 'date', 'after_or_equal:today'],
            'estimated_amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ]);

        $expenseRequest = DB::transaction(function () use ($validated, $request, $branch, $autoApprove) {
            $row = BranchExpenseRequest::query()->create([
                'reference_number' => 'TEMP-' . bin2hex(random_bytes(8)),
                'store_id' => $branch->store_id,
                'branch_id' => $branch->id,
                'category' => $validated['category'],
                'title' => trim($validated['title']),
                'description' => trim($validated['description']),
                'requested_date' => $validated['requested_date'],
                'estimated_amount' => $validated['estimated_amount'],
                'status' => $autoApprove ? 'approved' : 'pending_approval',
                'created_by' => $request->user()->id,
                'reviewed_by' => $autoApprove ? $request->user()->id : null,
                'reviewed_at' => $autoApprove ? now() : null,
            ]);
            $row->update(['reference_number' => sprintf('BEX-%s-%06d', now()->format('Ymd-His'), $row->id)]);

            $paths = collect($request->file('attachments', []))
                ->map(fn ($file) => $file->store("branch-expenses/{$branch->store_id}/{$branch->id}/{$row->id}", 'local'))
                ->all();
            if ($paths) $row->update(['attachments' => $paths]);

            return $row;
        });

        return response()->json([
            'success' => true,
            'message' => $autoApprove
                ? 'Expense request created and branch-approved. Submit the actual cost and receipt after completion.'
                : 'Expense request submitted for branch approval.',
            'data' => $this->formatRequest($expenseRequest->fresh(['creator:id,fname,lname', 'reviewer:id,fname,lname']), $request),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $branch = $this->assignedBranch($request, 'store.expenses.view');
        $row = $this->requestQuery($branch)->findOrFail($id);

        return response()->json(['success' => true, 'data' => $this->formatRequest($row, $request)]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $branch = $this->assignedBranch($request, 'store.expenses.approve');
        $notes = $request->validate(['notes' => ['nullable', 'string', 'max:2000']])['notes'] ?? null;
        DB::transaction(function () use ($branch, $id, $request, $notes) {
            $row = $this->requestQuery($branch)->lockForUpdate()->findOrFail($id);
            abort_unless($row->status === 'pending_approval', 422, 'Only requests awaiting branch approval can be approved.');
            $row->update([
                'status' => 'approved',
                'review_notes' => $notes,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Expense request approved.', 'data' => $this->formatRequest($this->requestQuery($branch)->findOrFail($id), $request)]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $branch = $this->assignedBranch($request, 'store.expenses.approve');
        $validated = $request->validate(['notes' => ['required', 'string', 'max:2000']]);
        DB::transaction(function () use ($branch, $id, $request, $validated) {
            $row = $this->requestQuery($branch)->lockForUpdate()->findOrFail($id);
            abort_unless($row->status === 'pending_approval', 422, 'Only requests awaiting branch approval can be rejected.');
            $row->update([
                'status' => 'rejected',
                'review_notes' => trim($validated['notes']),
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Expense request rejected.', 'data' => $this->formatRequest($this->requestQuery($branch)->findOrFail($id), $request)]);
    }

    public function submitActual(Request $request, int $id): JsonResponse
    {
        $branch = $this->assignedBranch($request, 'store.expenses.manage');
        $validated = $request->validate([
            'actual_amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'actual_expense_date' => ['required', 'date', 'before_or_equal:today'],
            'completion_notes' => ['nullable', 'string', 'max:3000'],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ]);
        $row = $this->requestQuery($branch)->findOrFail($id);
        abort_unless($row->status === 'approved', 422, 'Only approved requests can be submitted to Finance.');
        if ((float) $validated['actual_amount'] > (float) $row->estimated_amount && blank($validated['completion_notes'] ?? null)) {
            throw ValidationException::withMessages([
                'completion_notes' => 'Explain why the actual cost exceeded the approved estimate.',
            ]);
        }
        $receiptPath = $request->file('receipt')->store("branch-expenses/{$branch->store_id}/{$branch->id}/{$row->id}/receipts", 'local');
        $autoApproveFinance = $request->user()->hasPermissionTo('finance.expenses.approve', (int) $branch->store_id);
        try {
            DB::transaction(function () use ($id, $branch, $validated, $request, $receiptPath, $autoApproveFinance) {
                $row = $this->requestQuery($branch)->lockForUpdate()->findOrFail($id);
                abort_unless($row->status === 'approved', 422, 'Only approved requests can be submitted to Finance.');

                $row->update([
                    'actual_amount' => $validated['actual_amount'],
                    'actual_expense_date' => $validated['actual_expense_date'],
                    'completion_notes' => $validated['completion_notes'] ?? null,
                    'receipt_path' => $receiptPath,
                    'status' => $autoApproveFinance ? 'finance_approved' : 'submitted_to_finance',
                ]);

                $financeExpense = FinanceExpense::query()->create([
                    'store_id' => $branch->store_id,
                    'branch_id' => $branch->id,
                    'department' => 'Branch Operations',
                    'category' => $row->category,
                    'amount' => $validated['actual_amount'],
                    'expense_date' => $validated['actual_expense_date'],
                    'reference_number' => $row->reference_number,
                    'status' => $autoApproveFinance ? 'approved' : 'pending_approval',
                    'reference_type' => 'branch_expense_request',
                    'reference_id' => $row->id,
                    'description' => $row->title . ': ' . $row->description,
                    'notes' => $validated['completion_notes'] ?? null,
                    'requested_by' => $request->user()->id,
                    'approved_by' => $autoApproveFinance ? $request->user()->id : null,
                    'approved_at' => $autoApproveFinance ? now() : null,
                ]);
                $row->update(['finance_expense_id' => $financeExpense->id]);
            });
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($receiptPath);
            throw $error;
        }

        return response()->json([
            'success' => true,
            'message' => $autoApproveFinance
                ? 'Actual expense and receipt submitted to Finance and auto-approved. Finance can now record payment.'
                : 'Actual expense and receipt submitted to Finance for review.',
            'data' => $this->formatRequest($this->requestQuery($branch)->findOrFail($id), $request),
        ]);
    }

    public function attachment(Request $request, int $id, int $index): StreamedResponse
    {
        $branch = $this->assignedBranch($request, 'store.expenses.view');
        $row = $this->requestQuery($branch)->findOrFail($id);
        $paths = $row->attachments ?? [];
        abort_unless(isset($paths[$index]), 404);
        abort_unless(Storage::disk('local')->exists($paths[$index]), 404);

        return Storage::disk('local')->response($paths[$index]);
    }

    public function receipt(Request $request, int $id): StreamedResponse
    {
        $branch = $this->assignedBranch($request, 'store.expenses.view');
        $row = $this->requestQuery($branch)->findOrFail($id);
        abort_unless($row->receipt_path && Storage::disk('local')->exists($row->receipt_path), 404);

        return Storage::disk('local')->response($row->receipt_path);
    }

    public function financeReceipt(Request $request, int $financeExpenseId): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user && $user->store_id, 403);
        abort_unless($user->hasPermissionTo('finance.expenses.view', (int) $user->store_id), 403);

        $row = BranchExpenseRequest::query()
            ->where('store_id', (int) $user->store_id)
            ->where('finance_expense_id', $financeExpenseId)
            ->firstOrFail();
        abort_unless($row->receipt_path && Storage::disk('local')->exists($row->receipt_path), 404);

        return Storage::disk('local')->response($row->receipt_path);
    }

    private function assignedBranch(Request $request, string $permission): Branch
    {
        $user = $request->user();
        abort_unless($user && $user->store_id && $user->branch_id, 403, 'Your account must be assigned to a store branch.');
        $hasPermission = $user->hasPermissionTo($permission, (int) $user->store_id);
        if (in_array($permission, ['store.expenses.view', 'store.expenses.manage'], true)) {
            $hasPermission = $hasPermission || $user->hasPermissionTo('admin.maintenance.manage', (int) $user->store_id);
        }
        abort_unless($hasPermission, 403, 'You are not authorized to access branch expenses.');

        $branch = Branch::query()
            ->whereKey((int) $user->branch_id)
            ->where('store_id', (int) $user->store_id)
            ->where('status', 'active')
            ->firstOrFail();
        abort_if(strtolower((string) $branch->branch_type) === 'warehouse', 403, 'Branch expenses are only available for store branches.');

        return $branch;
    }

    private function requestQuery(Branch $branch)
    {
        return BranchExpenseRequest::query()
            ->where('store_id', $branch->store_id)
            ->where('branch_id', $branch->id)
            ->with(['creator:id,fname,lname', 'reviewer:id,fname,lname', 'financeExpense:id,amount,status,payment_date,payment_method,reference_number']);
    }

    private function formatRequest(BranchExpenseRequest $row, Request $request): array
    {
        $attachments = collect($row->attachments ?? [])->values()->map(fn ($path, $index) => [
            'name' => basename($path),
            'mime' => Storage::disk('local')->exists($path) ? Storage::disk('local')->mimeType($path) : null,
            'url' => url("/api/store-module/expenses/{$row->id}/attachments/{$index}"),
        ])->all();

        return [
            ...$row->toArray(),
            'branch' => ['id' => $row->branch_id, 'name' => $request->user()->branch?->name],
            'attachments' => $attachments,
            'receipt_url' => $row->receipt_path ? url("/api/store-module/expenses/{$row->id}/receipt") : null,
            'receipt_mime' => $row->receipt_path && Storage::disk('local')->exists($row->receipt_path)
                ? Storage::disk('local')->mimeType($row->receipt_path)
                : null,
            'can_approve' => $request->user()->hasPermissionTo('store.expenses.approve', (int) $row->store_id),
            'can_manage' => $request->user()->hasPermissionTo('admin.maintenance.manage', (int) $row->store_id)
                || $request->user()->hasPermissionTo('store.expenses.manage', (int) $row->store_id),
        ];
    }
}
