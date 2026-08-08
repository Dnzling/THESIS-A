<?php

namespace App\Http\Controllers\Api\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Core\User;
use App\Models\Logistics\ReturnPickup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ReturnPickupController extends Controller
{
    private const STATUSES = ['scheduled', 'assigned', 'picked_up', 'cancelled'];

    public function index(Request $request): JsonResponse
    {
        $storeId = (int) ($request->user()?->store_id ?? 0);

        $query = ReturnPickup::query()
            ->with([
                'driver:id,fname,lname,email',
                'returnRequest:id,order_id,order_item_id,store_id,user_id,requested_quantity,reason,status,created_at',
                'returnRequest.order:id,order_number,store_id,user_id,shipping_name,shipping_phone,shipping_address',
                'returnRequest.user:id,fname,lname,email',
            ])
            ->when($storeId > 0, fn ($q) => $q->where('store_id', $storeId));

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->whereHas('returnRequest.order', fn ($oq) => $oq->where('order_number', 'like', "%{$search}%"))
                    ->orWhereHas('returnRequest.user', fn ($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhere('pickup_name', 'like', "%{$search}%")
                    ->orWhere('pickup_phone', 'like', "%{$search}%");
            });
        }

        $sortBy = (string) $request->input('sort_by', 'scheduled_at');
        $sortOrder = strtolower((string) $request->input('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';
        if (!in_array($sortBy, ['scheduled_at', 'created_at', 'status', 'id'], true)) {
            $sortBy = 'scheduled_at';
        }

        $query->orderBy($sortBy, $sortOrder);

        $pickups = $query->paginate((int) $request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $pickups->items(),
            'meta' => [
                'total' => $pickups->total(),
                'per_page' => $pickups->perPage(),
                'current_page' => $pickups->currentPage(),
                'last_page' => $pickups->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, ReturnPickup $pickup): JsonResponse
    {
        $storeId = (int) ($request->user()?->store_id ?? 0);
        if ($storeId > 0 && (int) $pickup->store_id !== $storeId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $pickup->load([
            'driver:id,fname,lname,email',
            'returnRequest:id,order_id,order_item_id,store_id,user_id,requested_quantity,reason,details,evidence_urls,status,created_at,review_notes,reviewed_at,reviewed_by',
            'returnRequest.order:id,order_number,store_id,user_id,shipping_name,shipping_phone,shipping_address,customer_latitude,customer_longitude,total_amount,status,created_at',
            'returnRequest.orderItem:id,order_id,product_id,product_name,sku,quantity,unit_price',
            'returnRequest.orderItem.product:id,product_name,sku',
            'returnRequest.user:id,fname,lname,email',
            'returnRequest.reviewer:id,fname,lname,email',
        ]);

        $data = $pickup->toArray();
        $data['proof_photo_url'] = $pickup->proof_photo_path ? Storage::disk('public')->url($pickup->proof_photo_path) : null;
        $data['proof_signature_url'] = null;

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function assignDriver(Request $request, ReturnPickup $pickup): JsonResponse
    {
        $storeId = (int) ($request->user()?->store_id ?? 0);
        if ($storeId > 0 && (int) $pickup->store_id !== $storeId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'driver_user_id' => ['required', 'exists:users,id'],
        ]);

        $driver = User::query()
            ->where('id', (int) $validated['driver_user_id'])
            ->when($storeId > 0, fn ($q) => $q->where('store_id', $storeId))
            ->where('is_active', true)
            ->firstOrFail();

        $pickup->driver_user_id = $driver->id;
        $pickup->updated_by = $request->user()->id;
        if ($pickup->status === 'scheduled') {
            $pickup->status = 'assigned';
        }
        $pickup->save();

        return response()->json([
            'success' => true,
            'message' => 'Driver assigned.',
            'data' => $pickup->fresh(['driver']),
        ]);
    }

    public function updateStatus(Request $request, ReturnPickup $pickup): JsonResponse
    {
        $storeId = (int) ($request->user()?->store_id ?? 0);
        if ($storeId > 0 && (int) $pickup->store_id !== $storeId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            // Schedule/contact edits keep the current workflow status when the
            // client is not explicitly performing a status transition.
            'status' => ['sometimes', Rule::in(self::STATUSES)],
            'scheduled_at' => ['nullable', 'date'],
            'pickup_name' => ['nullable', 'string', 'max:255'],
            'pickup_phone' => ['nullable', 'string', 'max:255'],
            'pickup_address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $wasScheduledAt = $pickup->scheduled_at;
        $pickup->fill($validated);
        $pickup->updated_by = $request->user()->id;
        $pickup->save();

        // Notify customer when schedule is issued/updated.
        $scheduledAtChanged = array_key_exists('scheduled_at', $validated) && (string) ($validated['scheduled_at'] ?? '') !== (string) ($wasScheduledAt ?? '');
        if ($scheduledAtChanged && $pickup->scheduled_at) {
            $pickup->loadMissing(['returnRequest:id,user_id,store_id,order_id', 'returnRequest.order:id,order_number']);
            $returnRequest = $pickup->returnRequest;
            if ($returnRequest && $returnRequest->user_id) {
                $orderNumber = $returnRequest->order?->order_number ?? ('Order #' . (int) $returnRequest->order_id);
                $this->notify((int) $returnRequest->user_id, [
                    'module' => 'ecommerce',
                    'entity_type' => 'return_pickup',
                    'entity_id' => (int) $pickup->id,
                    'title' => 'Return pickup scheduled',
                    'message' => "Your return pickup for {$orderNumber} has been scheduled on " . $pickup->scheduled_at->format('M d, Y h:i A') . '.',
                    'severity' => 'info',
                    'store_id' => (int) $returnRequest->store_id,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Pickup updated.',
            'data' => $pickup->fresh(['driver']),
        ]);
    }

    public function uploadProof(Request $request, ReturnPickup $pickup): JsonResponse
    {
        $storeId = (int) ($request->user()?->store_id ?? 0);
        if ($storeId > 0 && (int) $pickup->store_id !== $storeId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $pickup->proof_photo_path = $request->file('photo')->store('logistics/returns/pickup/photos', 'public');
        if (!empty($validated['notes'])) {
            $pickup->notes = trim((string) $validated['notes']);
        }
        $pickup->picked_up_at = $pickup->picked_up_at ?: now();
        $pickup->status = 'picked_up';
        $pickup->updated_by = $request->user()->id;
        $pickup->save();

        $data = $pickup->fresh(['driver', 'returnRequest.order', 'returnRequest.user'])->toArray();
        $data['proof_photo_url'] = Storage::disk('public')->url($pickup->proof_photo_path);
        $data['proof_signature_url'] = null;

        return response()->json([
            'success' => true,
            'message' => 'Proof uploaded. Pickup marked as picked up.',
            'data' => $data,
        ]);
    }
}
