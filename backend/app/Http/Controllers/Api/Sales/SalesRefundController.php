<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesRefund;
use Illuminate\Http\Request;

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

    public function show(SalesRefund $refund)
    {
        return response()->json([
            'data' => $refund,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_type' => ['required', 'string', 'max:20'],
            'order_id' => ['required', 'integer'],
            'order_number' => ['nullable', 'string', 'max:100'],
            'customer_name' => ['nullable', 'string', 'max:190'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $user = $request->user();

        $refund = SalesRefund::create([
            'store_id' => $user?->store_id,
            'branch_id' => $user?->branch_id,
            'order_type' => $request->string('order_type'),
            'order_id' => $request->integer('order_id'),
            'order_number' => $request->string('order_number')->toString(),
            'customer_name' => $request->string('customer_name')->toString(),
            'reason' => $request->string('reason')->toString(),
            'amount' => $request->input('amount'),
            'status' => 'pending',
            'requested_by' => $user?->id,
        ]);

        return response()->json([
            'message' => 'Refund request created.',
            'data' => $refund,
        ]);
    }

    public function updateStatus(Request $request, SalesRefund $refund)
    {
        $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        $refund->update([
            'status' => $request->string('status'),
            'notes' => $request->string('notes')->toString(),
            'processed_by' => $user?->id,
            'processed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Refund status updated.',
            'data' => $refund,
        ]);
    }
}
