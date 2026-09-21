<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\CRM\EcommerceOrderReturn;
use App\Models\Finance\FinanceRefund;
use App\Services\Finance\CashflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinanceRefundController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = FinanceRefund::query()->with(['branch:id,name', 'requester:id,fname,lname'])
            ->where('store_id', $request->user()->store_id);

        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('source') && $request->string('source')->toString() !== 'all') $query->where('order_type', $request->string('source'));
        if ($request->filled('search')) {
            $term = trim($request->string('search')->toString());
            $query->where(fn ($q) => $q->where('order_number', 'like', "%{$term}%")
                ->orWhere('customer_name', 'like', "%{$term}%")->orWhere('reason', 'like', "%{$term}%"));
        }

        return response()->json(['data' => $query->latest()->get()]);
    }

    public function show(Request $request, FinanceRefund $refund): JsonResponse
    {
        $this->authorizeStore($request, $refund);
        $refund->load([
            'branch:id,name,address,contact_number', 'requester:id,fname,lname,email', 'processor:id,fname,lname,email',
            'ecommerceReturn' => fn ($q) => $q->with([
                'order:id,order_number,shipping_name,shipping_phone,shipping_email,shipping_address,total_amount,status',
                'orderItem:id,order_id,product_id,product_name,sku,quantity,unit_price',
                'pickup:id,return_id,status,destination_branch_id,delivered_at',
                'pickup.destinationBranch:id,name,address',
            ]),
        ]);
        if ($refund->order_type !== 'ecommerce_return') {
            $refund->setRelation('ecommerceReturn', null);
        }
        return response()->json(['data' => $refund]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_type' => 'required|in:ecommerce_return,ecommerce,sales,manual', 'order_id' => 'required|integer|min:1',
            'order_number' => 'nullable|string|max:100', 'customer_name' => 'required|string|max:190',
            'reason' => 'required|string|max:2000', 'amount' => 'required|numeric|min:0.01', 'notes' => 'nullable|string|max:2000',
        ]);
        $refund = FinanceRefund::create([...$data, 'store_id' => $request->user()->store_id,
            'branch_id' => $request->user()->employee?->branch_id, 'status' => 'pending', 'requested_by' => $request->user()->id]);
        return response()->json(['message' => 'Refund request created.', 'data' => $refund], 201);
    }

    public function updateStatus(Request $request, FinanceRefund $refund, CashflowService $cashflow): JsonResponse
    {
        $this->authorizeStore($request, $refund);
        $data = $request->validate(['status' => 'required|in:approved,rejected', 'notes' => 'nullable|string|max:2000']);
        $linkedReturn = null;

        DB::transaction(function () use ($request, $refund, $cashflow, $data, &$linkedReturn) {
            $refund = FinanceRefund::query()->lockForUpdate()->findOrFail($refund->id);
            if ($refund->status !== 'pending') throw ValidationException::withMessages(['status' => ['Only a pending refund can be processed.']]);
            if ($data['status'] === 'approved') {
                try {
                    $cashflow->debit((int) $refund->store_id, (float) $refund->amount, 'finance_refund', (int) $refund->id,
                        $request->user()->id, 'Customer refund for ' . ($refund->order_number ?: "request #{$refund->id}"));
                } catch (\RuntimeException $e) { throw ValidationException::withMessages(['amount' => [$e->getMessage()]]); }
            }
            $refund->update(['status' => $data['status'], 'notes' => $data['notes'] ?? $refund->notes,
                'processed_by' => $request->user()->id, 'processed_at' => now()]);
            if ($data['status'] === 'approved' && $refund->order_type === 'ecommerce_return') {
                $linkedReturn = EcommerceOrderReturn::query()->where('store_id', $refund->store_id)->lockForUpdate()->find($refund->order_id);
                if ($linkedReturn && in_array($linkedReturn->status, ['received', 'refund_pending'], true)) {
                    $linkedReturn->update(['status' => 'refunded', 'resolved_at' => now()]);
                }
            }
        });

        return response()->json(['message' => $data['status'] === 'approved' ? 'Refund approved and posted to cashflow.' : 'Refund rejected.', 'data' => $refund->fresh()]);
    }

    private function authorizeStore(Request $request, FinanceRefund $refund): void
    {
        abort_unless((int) $refund->store_id === (int) $request->user()->store_id, 403, 'Unauthorized access to refund.');
    }
}
