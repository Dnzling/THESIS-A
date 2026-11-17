<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\CRM\EcommerceOrderReturn;
use App\Models\Sales\SalesRefund;
use App\Models\Sales\SalesOrder;
use App\Services\Finance\CashflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesRefundController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $storeId = $user?->store_id;

        if (!$storeId) {
            return response()->json(['data' => []]);
        }

        $query = SalesRefund::query()
            ->where('store_id', $storeId)
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $term = $request->string('search');
            $query->where(function ($q) use ($term) {
                $q->where('order_number', 'like', "%{$term}%")
                    ->orWhere('customer_name', 'like', "%{$term}%")
                    ->orWhere('reason', 'like', "%{$term}%");
            });
        }

        return response()->json([
            'data' => $query->paginate(20),
        ]);
    }

    public function show(Request $request, SalesRefund $refund)
    {
        if ((int) $refund->store_id !== (int) $request->user()?->store_id) {
            abort(403, 'Unauthorized access to refund.');
        }

        return response()->json([
            'data' => $refund,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:2000'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'refund_method' => ['required', 'in:gcash,maya,bank_transfer,card'],
            'refund_account_name' => ['required', 'string', 'max:255'],
            'refund_account_number' => ['required', 'string', 'max:100'],
        ]);

        $user = $request->user();
        $sale = SalesOrder::query()->where('store_id', $user->store_id)
            ->where('payment_status', 'paid')->find($data['order_id']);
        if (!$sale || (float) $data['amount'] > (float) $sale->total_amount) {
            throw \Illuminate\Validation\ValidationException::withMessages(['order_id' => ['Select a paid sale from this store with enough refundable value.']]);
        }
        if (SalesRefund::query()->where('store_id', $sale->store_id)->where('order_type', 'sales')
            ->where('order_id', $sale->id)->whereNotIn('status', ['rejected'])->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['order_id' => ['A refund request already exists for this sale.']]);
        }

        $refund = SalesRefund::create([
            'store_id' => $sale->store_id,
            'branch_id' => $sale->branch_id,
            'order_type' => 'sales',
            'order_id' => $sale->id,
            'order_number' => $sale->order_number,
            'customer_name' => $sale->customer_name ?: 'Walk-in customer',
            'reason' => $data['reason'],
            'amount' => $data['amount'],
            'refund_method' => $data['refund_method'],
            'refund_account_name' => $data['refund_account_name'],
            'refund_account_number' => $data['refund_account_number'],
            'status' => 'pending',
            'requested_by' => $user?->id,
        ]);

        return response()->json([
            'message' => 'Refund request created.',
            'data' => $refund,
        ]);
    }

    public function updateStatus(Request $request, SalesRefund $refund, CashflowService $cashflow)
    {
        if ((int) $refund->store_id !== (int) $request->user()?->store_id) {
            abort(403, 'Unauthorized access to refund.');
        }

        $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        $status = $request->string('status')->toString();
        $linkedReturn = null;

        DB::transaction(function () use ($refund, $request, $user, $status, $cashflow, &$linkedReturn): void {
            $refund = SalesRefund::query()->lockForUpdate()->findOrFail($refund->id);
            if ((string) $refund->status !== 'pending') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'status' => ['Only a pending refund can be processed by Finance.'],
                ]);
            }

            if ($status === 'approved') {
                try {
                    $cashflow->debit(
                        (int) $refund->store_id,
                        (float) $refund->amount,
                        'sales_refund',
                        (int) $refund->id,
                        $user?->id,
                        'Customer refund for ' . ($refund->order_number ?: ('request #' . $refund->id)),
                        null,
                        ['order_type' => $refund->order_type, 'order_id' => (int) $refund->order_id]
                    );
                } catch (\RuntimeException $exception) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'amount' => [$exception->getMessage()],
                    ]);
                }
            }

            $refund->update([
                'status' => $status,
                'notes' => $request->string('notes')->toString(),
                'processed_by' => $user?->id,
                'processed_at' => now(),
            ]);

            if ($status === 'approved' && $refund->order_type === 'ecommerce_return') {
                $linkedReturn = EcommerceOrderReturn::query()
                    ->where('store_id', (int) $refund->store_id)
                    ->lockForUpdate()
                    ->find((int) $refund->order_id);

                if ($linkedReturn && in_array((string) $linkedReturn->status, ['received', 'refund_pending'], true)) {
                    $linkedReturn->status = 'refunded';
                    $linkedReturn->resolved_at = now();
                    $linkedReturn->save();
                }
            }
        });

        $refund->refresh();

        if ($status === 'approved' && $linkedReturn?->user_id) {
            $this->notify((int) $linkedReturn->user_id, [
                'module' => 'ecommerce',
                'entity_type' => 'ecommerce_order_return',
                'entity_id' => (int) $linkedReturn->id,
                'title' => 'Refund approved',
                'message' => 'Your refund of ₱' . number_format((float) $refund->amount, 2) . ' has been approved.',
                'severity' => 'success',
                'store_id' => (int) $refund->store_id,
            ]);
        }

        return response()->json([
            'message' => $status === 'approved'
                ? 'Refund approved and recorded in Finance cashflow.'
                : 'Refund rejected by Finance.',
            'data' => $refund,
        ]);
    }
}
