<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceCashAdvance;
use App\Services\Finance\CashflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinanceLiquidationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = FinanceCashAdvance::query()
            ->with(['branch:id,name', 'requester:id,fname,lname'])
            ->where('store_id', $request->user()->store_id);

        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('search')) {
            $term = trim($request->string('search')->toString());
            $query->where(fn ($q) => $q->where('advance_number', 'like', "%{$term}%")
                ->orWhere('purpose', 'like', "%{$term}%")
                ->orWhereHas('requester', fn ($user) => $user->where('fname', 'like', "%{$term}%")
                    ->orWhere('lname', 'like', "%{$term}%")));
        }
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date('date_from'));
        if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->date('date_to'));

        return response()->json([
            'success' => true,
            'data' => $query->latest()->paginate($request->integer('per_page', 15)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'purpose' => ['required', 'string', 'max:255'],
            'advance_amount' => ['required', 'numeric', 'min:0.01'],
            'needed_date' => ['required', 'date', 'after_or_equal:today'],
            'payment_method' => ['nullable', 'in:cash,bank_transfer,check,gcash'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $advance = DB::transaction(function () use ($request, $validated) {
            $record = FinanceCashAdvance::create([
                ...$validated,
                'store_id' => $request->user()->store_id,
                'branch_id' => $request->user()->employee?->branch_id,
                'advance_number' => 'TEMP-' . str()->uuid(),
                'status' => 'pending_approval',
                'requested_by' => $request->user()->id,
            ]);
            $record->update(['advance_number' => 'CA-' . now()->format('Ymd') . '-' . str_pad((string) $record->id, 5, '0', STR_PAD_LEFT)]);
            return $record;
        });

        return response()->json(['success' => true, 'message' => 'Cash advance submitted for approval.', 'data' => $advance], 201);
    }

    public function show(Request $request, FinanceCashAdvance $liquidation): JsonResponse
    {
        $this->authorizeStore($request, $liquidation);
        $liquidation->load([
            'branch:id,name,address,contact_number', 'items',
            'requester:id,fname,lname,email', 'approver:id,fname,lname,email',
            'releaser:id,fname,lname,email', 'submitter:id,fname,lname,email', 'settler:id,fname,lname,email',
        ]);
        return response()->json(['success' => true, 'data' => $liquidation]);
    }

    public function approve(Request $request, FinanceCashAdvance $liquidation): JsonResponse
    {
        $this->authorizeStore($request, $liquidation);
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:2000']]);
        $this->requireStatus($liquidation, ['pending_approval']);
        $liquidation->update([
            'status' => 'approved', 'review_notes' => $data['notes'] ?? null,
            'approved_by' => $request->user()->id, 'approved_at' => now(),
        ]);
        return response()->json(['success' => true, 'message' => 'Cash advance approved.', 'data' => $liquidation->fresh()]);
    }

    public function reject(Request $request, FinanceCashAdvance $liquidation): JsonResponse
    {
        $this->authorizeStore($request, $liquidation);
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        $this->requireStatus($liquidation, ['pending_approval', 'approved']);
        $liquidation->update([
            'status' => 'rejected', 'review_notes' => $data['reason'],
            'approved_by' => $request->user()->id, 'approved_at' => now(),
        ]);
        return response()->json(['success' => true, 'message' => 'Cash advance rejected.', 'data' => $liquidation->fresh()]);
    }

    public function release(Request $request, FinanceCashAdvance $liquidation, CashflowService $cashflow): JsonResponse
    {
        $this->authorizeStore($request, $liquidation);
        $this->requireStatus($liquidation, ['approved']);

        DB::transaction(function () use ($request, $liquidation, $cashflow) {
            $locked = FinanceCashAdvance::query()->lockForUpdate()->findOrFail($liquidation->id);
            $this->requireStatus($locked, ['approved']);
            try {
                $cashflow->debit((int) $locked->store_id, (float) $locked->advance_amount, 'cash_advance', $locked->id,
                    $request->user()->id, 'Cash advance ' . $locked->advance_number, $locked->payment_method);
            } catch (\RuntimeException $exception) {
                throw ValidationException::withMessages(['advance_amount' => [$exception->getMessage()]]);
            }
            $locked->update(['status' => 'released', 'released_by' => $request->user()->id, 'released_at' => now()]);
        });

        return response()->json(['success' => true, 'message' => 'Cash advance released and posted to cashflow.', 'data' => $liquidation->fresh()]);
    }

    public function submit(Request $request, FinanceCashAdvance $liquidation): JsonResponse
    {
        $this->authorizeStore($request, $liquidation);
        $this->requireStatus($liquidation, ['released']);
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.expense_date' => ['required', 'date'],
            'items.*.category' => ['required', 'string', 'max:100'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.amount' => ['required', 'numeric', 'min:0.01'],
            'items.*.receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $liquidation, $validated) {
            $liquidation->items()->delete();
            $total = 0;
            foreach ($validated['items'] as $index => $item) {
                $file = $request->file("items.{$index}.receipt");
                $path = $file?->store("finance/liquidations/{$liquidation->id}", 'public');
                $liquidation->items()->create([
                    'expense_date' => $item['expense_date'], 'category' => $item['category'],
                    'description' => $item['description'], 'amount' => $item['amount'],
                    'receipt_path' => $path, 'receipt_name' => $file?->getClientOriginalName(),
                ]);
                $total += (float) $item['amount'];
            }
            $advance = (float) $liquidation->advance_amount;
            $liquidation->update([
                'status' => 'liquidation_submitted', 'liquidated_amount' => round($total, 2),
                'cash_returned' => round(max(0, $advance - $total), 2),
                'reimbursement_amount' => round(max(0, $total - $advance), 2),
                'notes' => $validated['notes'] ?? $liquidation->notes,
                'submitted_by' => $request->user()->id, 'submitted_at' => now(),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Liquidation submitted for Finance review.', 'data' => $liquidation->fresh('items')]);
    }

    public function settle(Request $request, FinanceCashAdvance $liquidation, CashflowService $cashflow): JsonResponse
    {
        $this->authorizeStore($request, $liquidation);
        $this->requireStatus($liquidation, ['liquidation_submitted']);
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:2000']]);

        DB::transaction(function () use ($request, $liquidation, $cashflow, $data) {
            $locked = FinanceCashAdvance::query()->lockForUpdate()->findOrFail($liquidation->id);
            $this->requireStatus($locked, ['liquidation_submitted']);
            try {
                if ((float) $locked->cash_returned > 0) {
                    $cashflow->credit((int) $locked->store_id, (float) $locked->cash_returned, 'cash_advance_return', $locked->id,
                        $request->user()->id, 'Unused cash returned for ' . $locked->advance_number, $locked->payment_method);
                }
                if ((float) $locked->reimbursement_amount > 0) {
                    $cashflow->debit((int) $locked->store_id, (float) $locked->reimbursement_amount, 'liquidation_reimbursement', $locked->id,
                        $request->user()->id, 'Liquidation reimbursement for ' . $locked->advance_number, $locked->payment_method);
                }
            } catch (\RuntimeException $exception) {
                throw ValidationException::withMessages(['settlement' => [$exception->getMessage()]]);
            }
            $locked->update([
                'status' => 'settled', 'review_notes' => $data['notes'] ?? $locked->review_notes,
                'settled_by' => $request->user()->id, 'settled_at' => now(),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Liquidation settled and posted to cashflow.', 'data' => $liquidation->fresh()]);
    }

    private function authorizeStore(Request $request, FinanceCashAdvance $liquidation): void
    {
        abort_unless((int) $liquidation->store_id === (int) $request->user()->store_id, 403, 'Unauthorized access to liquidation.');
    }

    private function requireStatus(FinanceCashAdvance $liquidation, array $statuses): void
    {
        if (!in_array((string) $liquidation->status, $statuses, true)) {
            throw ValidationException::withMessages(['status' => ['This action is not allowed for the current status.']]);
        }
    }
}
