<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\EcommerceOrderReturn;
use App\Models\Logistics\ReturnPickup;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Sales\SalesRefund;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesReturnController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $storeId = (int) (auth()->user()?->store_id ?? 0);

        $query = EcommerceOrderReturn::query()
            ->with([
                'order:id,order_number,status,store_id,user_id,shipping_name,shipping_phone,shipping_address,created_at',
                'orderItem:id,order_id,product_id,product_name,sku,quantity,unit_price',
                'orderItem.product:id,product_name,sku',
                'user:id,fname,lname,email',
                'pickup:id,store_id,return_id,status,scheduled_at,driver_user_id,picked_up_at,created_at',
                'pickup.driver:id,fname,lname,email',
            ])
            ->when($storeId > 0, fn ($q) => $q->where('store_id', $storeId));

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->string('start_date')->toString() . ' 00:00:00',
                $request->string('end_date')->toString() . ' 23:59:59',
            ]);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->whereHas('order', function ($oq) use ($search) {
                    $oq->where('order_number', 'like', "%{$search}%");
                })
                ->orWhereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhereHas('orderItem.product', function ($pq) use ($search) {
                    $pq->where('product_name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                })
                ->orWhereHas('orderItem', function ($iq) use ($search) {
                    $iq->where('product_name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            });
        }

        $sortBy = $request->string('sort_by', 'created_at')->toString();
        $sortOrder = strtolower($request->string('sort_order', 'desc')->toString()) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['created_at', 'status', 'requested_quantity', 'id'];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        $query->orderBy($sortBy, $sortOrder);

        $perPage = (int) $request->input('per_page', 15);
        $returns = $query->paginate(max(1, min(100, $perPage)));

        return response()->json([
            'success' => true,
            'data' => $returns->items(),
            'meta' => [
                'total' => $returns->total(),
                'per_page' => $returns->perPage(),
                'current_page' => $returns->currentPage(),
                'last_page' => $returns->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, EcommerceOrderReturn $return): JsonResponse
    {
        $storeId = (int) (auth()->user()?->store_id ?? 0);
        if ($storeId > 0 && (int) $return->store_id !== $storeId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to return request.',
            ], 403);
        }

        $return->load([
            'order:id,order_number,status,store_id,user_id,shipping_name,shipping_phone,shipping_address,created_at',
            'orderItem:id,order_id,product_id,product_name,sku,quantity,unit_price',
            'orderItem.product:id,product_name,sku',
            'user:id,fname,lname,email',
            'reviewer:id,fname,lname,email',
            'pickup:id,store_id,return_id,status,scheduled_at,pickup_name,pickup_phone,pickup_address,driver_user_id,picked_up_at,created_at',
            'pickup.driver:id,fname,lname,email',
        ]);

        return response()->json([
            'success' => true,
            'data' => $return,
        ]);
    }

    public function updateStatus(Request $request, EcommerceOrderReturn $return): JsonResponse
    {
        $storeId = (int) (auth()->user()?->store_id ?? 0);
        if ($storeId > 0 && (int) $return->store_id !== $storeId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to return request.',
            ], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $newStatus = $validated['status'];
        $currentStatus = (string) $return->status;

        if ($newStatus === 'rejected' && empty(trim((string) ($validated['review_notes'] ?? '')))) {
            return response()->json([
                'success' => false,
                'message' => 'Review notes are required when rejecting a return.',
            ], 422);
        }

        $allowedTransitions = [
            'pending_verification' => ['approved', 'rejected'],
            'approved' => ['rejected'],
            'rejected' => [],
            'received' => [],
            'refunded' => [],
        ];

        $currentAllowed = $allowedTransitions[$currentStatus] ?? [];
        if ($newStatus !== $currentStatus && !in_array($newStatus, $currentAllowed, true)) {
            return response()->json([
                'success' => false,
                'message' => "Invalid status transition from '{$currentStatus}' to '{$newStatus}'.",
            ], 422);
        }

        DB::transaction(function () use ($return, $newStatus, $validated): void {
            $return->status = $newStatus;
            if (array_key_exists('review_notes', $validated)) {
                $return->review_notes = $validated['review_notes'];
            }

            $return->reviewed_by = auth()->id();
            $return->reviewed_at = Carbon::now();
            $return->save();

            // When approved, ensure a logistics pickup job exists (Logistics will schedule it).
            if ($newStatus === 'approved') {
                $return->loadMissing(['order']);
                ReturnPickup::query()->firstOrCreate(
                    ['return_id' => (int) $return->id],
                    [
                        'store_id' => (int) $return->store_id,
                        'status' => 'scheduled',
                        'scheduled_at' => null,
                        'pickup_name' => $return->order?->shipping_name,
                        'pickup_phone' => $return->order?->shipping_phone,
                        'pickup_address' => $return->order?->shipping_address,
                        'notes' => 'Created from approved return.',
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]
                );
            }
        });

        $return->load([
            'order:id,order_number,status,store_id,user_id,shipping_name,shipping_phone,shipping_address,created_at',
            'orderItem:id,order_id,product_id,product_name,sku,quantity,unit_price',
            'orderItem.product:id,product_name,sku',
            'user:id,fname,lname,email',
            'reviewer:id,fname,lname,email',
            'pickup:id,store_id,return_id,status,scheduled_at,pickup_name,pickup_phone,pickup_address,driver_user_id,picked_up_at,created_at',
            'pickup.driver:id,fname,lname,email',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Return status updated.',
            'data' => $return,
        ]);
    }

    public function createPickup(Request $request, EcommerceOrderReturn $return): JsonResponse
    {
        $storeId = (int) (auth()->user()?->store_id ?? 0);
        if ($storeId > 0 && (int) $return->store_id !== $storeId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to return request.',
            ], 403);
        }

        $validated = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'pickup_name' => ['nullable', 'string', 'max:255'],
            'pickup_phone' => ['nullable', 'string', 'max:255'],
            'pickup_address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ((string) $return->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Pickup can only be scheduled for approved returns.',
            ], 422);
        }

        $pickup = null;
        DB::transaction(function () use (&$pickup, $return, $validated): void {
            $pickup = ReturnPickup::query()->firstOrCreate(
                ['return_id' => (int) $return->id],
                [
                    'store_id' => (int) $return->store_id,
                    'status' => 'scheduled',
                    'scheduled_at' => null,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]
            );

            $pickup->scheduled_at = Carbon::parse($validated['scheduled_at']);
            $pickup->pickup_name = $validated['pickup_name'] ?? $pickup->pickup_name;
            $pickup->pickup_phone = $validated['pickup_phone'] ?? $pickup->pickup_phone;
            $pickup->pickup_address = $validated['pickup_address'] ?? $pickup->pickup_address;
            $pickup->notes = $validated['notes'] ?? $pickup->notes;
            $pickup->updated_by = auth()->id();
            $pickup->save();
        });

        $return->load([
            'pickup:id,store_id,return_id,status,scheduled_at,pickup_name,pickup_phone,pickup_address,driver_user_id,picked_up_at,created_at',
            'pickup.driver:id,fname,lname,email',
        ]);

        // Notify customer that pickup is scheduled.
        if ($pickup && $return->user_id) {
            $return->loadMissing(['order:id,order_number', 'user:id,email']);
            $orderNumber = $return->order?->order_number ?? ('Order #' . (int) $return->order_id);
            $this->notify((int) $return->user_id, [
                'module' => 'ecommerce',
                'entity_type' => 'return_pickup',
                'entity_id' => (int) $pickup->id,
                'title' => 'Return pickup scheduled',
                'message' => "Your return pickup for {$orderNumber} has been scheduled on " . $pickup->scheduled_at?->format('M d, Y h:i A') . '.',
                'severity' => 'info',
                'store_id' => (int) $return->store_id,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pickup scheduled.',
            'data' => [
                'return' => $return,
                'pickup' => $return->pickup,
            ],
        ]);
    }

    public function receive(Request $request, EcommerceOrderReturn $return): JsonResponse
    {
        $storeId = (int) (auth()->user()?->store_id ?? 0);
        if ($storeId > 0 && (int) $return->store_id !== $storeId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'received_quantity' => ['required', 'integer', 'min:1'],
            'condition' => ['required', 'in:resellable,damaged'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $maxQty = (int) ($return->requested_quantity ?? 1);
        $receivedQty = (int) $validated['received_quantity'];
        if ($receivedQty > $maxQty) {
            return response()->json(['success' => false, 'message' => "Received quantity must be between 1 and {$maxQty}."], 422);
        }

        if ((string) $return->status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Return must be approved before receiving.'], 422);
        }

        $user = $request->user();
        $employeeId = (int) ($user?->employee?->id ?? 0);
        $branchId = (int) ($user?->branch_id ?? $user?->employee?->branch_id ?? 0);
        if ($employeeId <= 0 || $branchId <= 0) {
            return response()->json(['success' => false, 'message' => 'User must be linked to an employee + branch to post inventory receive.'], 422);
        }

        $return->loadMissing(['orderItem:id,order_id,product_id,unit_price']);
        $productId = (int) ($return->orderItem?->product_id ?? 0);
        if ($productId <= 0) {
            return response()->json(['success' => false, 'message' => 'Return item product is missing.'], 422);
        }

        DB::transaction(function () use ($return, $validated, $receivedQty, $productId, $branchId, $employeeId): void {
            InventoryTransaction::query()->create([
                'transaction_number' => 'INVTX-RET-' . (int) $return->id . '-' . now()->format('YmdHis'),
                'store_id' => (int) $return->store_id,
                'branch_id' => $branchId,
                'product_id' => $productId,
                'variation_id' => null,
                'transaction_type' => $validated['condition'] === 'damaged' ? 'damage' : 'customer_return',
                'quantity_before' => 0,
                'quantity_change' => $receivedQty,
                'quantity_after' => 0,
                'reference_type' => 'ecommerce_order_return',
                'reference_id' => (int) $return->id,
                'notes' => $validated['notes'] ?? ('Received from return #' . (int) $return->id),
                'unit_cost' => $return->orderItem?->unit_price,
                'total_value' => (float) ($return->orderItem?->unit_price ?? 0) * $receivedQty,
                'created_by' => $employeeId,
                'transaction_date' => now(),
            ]);

            $return->status = 'received';
            $return->save();
        });

        return response()->json([
            'success' => true,
            'message' => 'Return received and inventory transaction posted.',
            'data' => $return->fresh(),
        ]);
    }

    public function refund(Request $request, EcommerceOrderReturn $return): JsonResponse
    {
        $storeId = (int) (auth()->user()?->store_id ?? 0);
        if ($storeId > 0 && (int) $return->store_id !== $storeId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'mark_as_approved' => ['nullable', 'boolean'],
        ]);

        if ((string) $return->status !== 'received') {
            return response()->json(['success' => false, 'message' => 'Return must be received before processing refund.'], 422);
        }

        $user = $request->user();
        $branchId = (int) ($user?->branch_id ?? $user?->employee?->branch_id ?? 0);

        $return->loadMissing(['order:id,order_number,shipping_name']);

        $refund = null;
        DB::transaction(function () use (&$refund, $return, $validated, $branchId, $user): void {
            $refund = SalesRefund::query()->create([
                'store_id' => (int) $return->store_id,
                'branch_id' => $branchId ?: null,
                'order_type' => 'ecommerce_return',
                'order_id' => (int) $return->id,
                'order_number' => $return->order?->order_number,
                'customer_name' => $return->order?->shipping_name,
                'reason' => $validated['reason'] ?? null,
                'amount' => (float) $validated['amount'],
                'status' => !empty($validated['mark_as_approved']) ? 'approved' : 'pending',
                'requested_by' => (int) ($user?->id ?? 0) ?: null,
                'processed_by' => !empty($validated['mark_as_approved']) ? (int) ($user?->id ?? 0) : null,
                'processed_at' => !empty($validated['mark_as_approved']) ? now() : null,
                'notes' => $validated['notes'] ?? null,
            ]);

            if (!empty($validated['mark_as_approved'])) {
                $return->status = 'refunded';
                $return->save();
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Refund record created.',
            'data' => [
                'refund' => $refund,
                'return' => $return->fresh(),
            ],
        ]);
    }
}
