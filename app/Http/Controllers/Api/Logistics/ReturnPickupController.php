<?php

namespace App\Http\Controllers\Api\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Core\User;
use App\Models\Ecommerce\EcommerceDeliveryVehicle;
use App\Models\Logistics\ReturnPickup;
use App\Models\Logistics\ReturnPickupLog;
use App\Models\Store\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ReturnPickupController extends Controller
{
    private const STATUSES = ['ready_for_dispatch', 'scheduled', 'assigned', 'picked_up', 'out_for_delivery', 'delivered', 'cancelled'];

    public function branches(Request $request): JsonResponse
    {
        $rows = Branch::query()->where('store_id', $request->user()->store_id)->where('status', 'active')
            ->orderBy('name')->get(['id', 'name', 'branch_type', 'address', 'city', 'province', 'latitude', 'longitude']);
        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function warehouseReturns(Request $request): JsonResponse { return $this->destinationReturns($request, 'warehouse'); }
    public function inventoryReturns(Request $request): JsonResponse { return $this->destinationReturns($request, 'storefront'); }

    private function destinationReturns(Request $request, string $branchType): JsonResponse
    {
        $dateFilters = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $query = ReturnPickup::query()->with([
            'destinationBranch:id,name,branch_type,address', 'driver:id,fname,lname',
            'returnRequest:id,return_number,order_id,order_item_id,requested_quantity,reason,status,return_type',
            'returnRequest.order:id,order_number,shipping_name,shipping_phone',
            'returnRequest.orderItem:id,product_name,sku,unit_price',
        ])->where('store_id', $request->user()->store_id)
            ->whereHas('destinationBranch', fn ($q) => $q->where('branch_type', $branchType))
            ->whereNotIn('status', ['ready_for_dispatch', 'scheduled', 'cancelled']);

        $branchId = $request->user()->employee?->branch_id;
        if ($branchId && ! $request->user()->hasRole('store_admin')) $query->where('destination_branch_id', $branchId);
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('search')) {
            $term = trim($request->string('search')->toString());
            $query->where(fn ($q) => $q->whereHas('returnRequest', fn ($r) => $r->where('return_number', 'like', "%{$term}%"))
                ->orWhereHas('returnRequest.order', fn ($o) => $o->where('order_number', 'like', "%{$term}%")->orWhere('shipping_name', 'like', "%{$term}%"))
                ->orWhereHas('returnRequest.orderItem', fn ($i) => $i->where('product_name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%")));
        }

        if (!empty($dateFilters['start_date'])) $query->whereDate('created_at', '>=', $dateFilters['start_date']);
        if (!empty($dateFilters['end_date'])) $query->whereDate('created_at', '<=', $dateFilters['end_date']);

        $sortBy = (string) $request->input('sort_by', 'created_at');
        $sortBy = in_array($sortBy, ['created_at', 'scheduled_at', 'status'], true) ? $sortBy : 'created_at';
        $sortOrder = strtolower((string) $request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';

        return response()->json(['success' => true, 'data' => $query->orderBy($sortBy, $sortOrder)->get()]);
    }

    public function warehouseShow(Request $request, ReturnPickup $pickup): JsonResponse
    {
        $pickup->loadMissing('destinationBranch:id,branch_type');
        if ((int) $pickup->store_id !== (int) $request->user()->store_id || $pickup->destinationBranch?->branch_type !== 'warehouse') {
            return response()->json(['success' => false, 'message' => 'Warehouse return not found.'], 404);
        }

        $branchId = (int) ($request->user()->employee?->branch_id ?? $request->user()->branch_id ?? 0);
        if ($branchId > 0 && $branchId !== (int) $pickup->destination_branch_id && ! $request->user()->hasRole('store_admin')) {
            return response()->json(['success' => false, 'message' => 'This return belongs to another warehouse branch.'], 403);
        }

        return $this->show($request, $pickup);
    }

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
            'driver:id,fname,lname,email,phone_number',
            'vehicle:id,vehicle_name,vehicle_type,plate_number,brand,model',
            'destinationBranch:id,name,address,city,province,latitude,longitude,contact_number,email',
            'logs.creator:id,fname,lname,email',
            'returnRequest:id,return_number,order_id,order_item_id,store_id,user_id,requested_quantity,reason,details,evidence_urls,status,return_type,product_condition,inventory_disposition,received_quantity,inspected_by,inspected_at,inspection_notes,replacement_status,created_at,review_notes,reviewed_at,reviewed_by',
            'returnRequest.order:id,order_number,store_id,assigned_branch_id,user_id,shipping_name,shipping_phone,shipping_email,shipping_address,customer_latitude,customer_longitude,total_amount,status,created_at',
            'returnRequest.order.assignedBranch:id,name,address,latitude,longitude',
            'returnRequest.orderItem:id,order_id,product_id,branch_inventory_id,product_name,sku,quantity,unit_price',
            'returnRequest.orderItem.product:id,product_name,sku,weight_kg',
            'returnRequest.orderItem.branchInventory:id,variation_id',
            'returnRequest.orderItem.branchInventory.variation:id,variation_name,variation_sku,color,size,material,finish',
            'returnRequest.user:id,fname,lname,email',
            'returnRequest.reviewer:id,fname,lname,email',
            'returnRequest.inspector:id,fname,lname,email',
        ]);

        $data = $pickup->toArray();
        $data['assistants'] = User::query()
            ->whereIn('id', $pickup->assistant_user_ids ?? [])
            ->get(['id', 'fname', 'lname', 'email'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')) ?: $user->email,
            ])
            ->values();
        $data['proof_photo_url'] = $pickup->proof_photo_path ? Storage::disk('public')->url($pickup->proof_photo_path) : null;
        $data['proof_signature_url'] = null;
        $data['logs'] = $pickup->logs->map(function ($log) {
            $row = $log->toArray();
            $row['proof_photo_url'] = $log->proof_photo_path ? Storage::disk('public')->url($log->proof_photo_path) : null;
            return $row;
        });

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
            'vehicle_id' => ['required', 'exists:ecommerce_delivery_vehicles,id'],
            'destination_branch_id' => ['required', 'exists:branches,id'],
            'assistant_user_ids' => ['nullable', 'array'],
            'assistant_user_ids.*' => ['integer', 'exists:users,id'],
            'distance_km' => ['nullable', 'numeric', 'min:0'],
            'estimated_fee' => ['nullable', 'numeric', 'min:0'],
            'scheduled_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (!in_array((string) $pickup->status, ['ready_for_dispatch', 'scheduled'], true)) {
            return response()->json(['success' => false, 'message' => 'This return pickup is no longer available for assignment.'], 422);
        }

        $driver = User::query()
            ->with(['role:id,name', 'employee:id,user_id,role_id,status', 'employee.role:id,name'])
            ->where('id', (int) $validated['driver_user_id'])
            ->when($storeId > 0, fn ($q) => $q->where('store_id', $storeId))
            ->where('is_active', true)
            ->first();
        if (!$driver || $driver->employee?->status !== 'active'
            || (strtolower((string) $driver->role?->name) !== 'driver'
                && strtolower((string) $driver->employee?->role?->name) !== 'driver')) {
            return response()->json(['success' => false, 'message' => 'Please select an active employee with the Driver role.'], 422);
        }

        $vehicle = EcommerceDeliveryVehicle::query()
            ->where('id', (int) $validated['vehicle_id'])
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->firstOrFail();
        $branch = Branch::query()->where('id', $validated['destination_branch_id'])->where('store_id', $storeId)->firstOrFail();

        $assistantIds = collect($validated['assistant_user_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->reject(fn ($id) => $id === (int) $driver->id)->values();
        $validAssistantCount = User::query()->where('store_id', $storeId)->where('is_active', true)->whereIn('id', $assistantIds)->count();
        if ($validAssistantCount !== $assistantIds->count()) {
            return response()->json(['success' => false, 'message' => 'One or more delivery assistants are invalid.'], 422);
        }

        $pickup->driver_user_id = $driver->id;
        $pickup->vehicle_id = $vehicle->id;
        $pickup->destination_branch_id = $branch->id;
        $pickup->assistant_user_ids = $assistantIds->all();
        $pickup->distance_km = $validated['distance_km'] ?? null;
        $pickup->estimated_fee = $validated['estimated_fee'] ?? null;
        $pickup->scheduled_at = $validated['scheduled_at'] ?? $pickup->scheduled_at;
        $pickup->notes = $validated['notes'] ?? $pickup->notes;
        $pickup->updated_by = $request->user()->id;
        if (in_array($pickup->status, ['ready_for_dispatch', 'scheduled'], true)) {
            $pickup->status = 'assigned';
        }
        $pickup->save();
        ReturnPickupLog::create(['return_pickup_id' => $pickup->id, 'event_type' => 'assigned', 'status_to' => $pickup->status, 'message' => "Return pickup assigned for delivery to {$branch->name}.", 'created_by' => $request->user()->id]);

        return response()->json([
            'success' => true,
            'message' => 'Driver assigned.',
            'data' => $pickup->fresh(['driver', 'vehicle']),
        ]);
    }

    public function updateStatus(Request $request, ReturnPickup $pickup): JsonResponse
    {
        $storeId = (int) ($request->user()?->store_id ?? 0);
        if ($storeId > 0 && (int) $pickup->store_id !== $storeId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }
        if ($request->user()->hasRole('driver') && (int) $pickup->driver_user_id !== (int) $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'This return pickup is not assigned to you.'], 403);
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
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location_address' => ['nullable', 'string', 'max:2000'],
            'photo' => [Rule::requiredIf(fn () => $request->user()->hasRole('driver') && in_array((string) $request->input('status'), ['picked_up', 'delivered'], true)), 'nullable', 'image', 'max:5120'],
        ]);

        if (isset($validated['status']) && $validated['status'] !== $pickup->status) {
            $allowed = [
                'ready_for_dispatch' => ['assigned'], 'scheduled' => ['assigned'],
                'assigned' => ['picked_up'], 'picked_up' => ['out_for_delivery'],
                'out_for_delivery' => ['delivered'],
            ];
            $canCancel = $validated['status'] === 'cancelled' && $pickup->status !== 'delivered';
            if (!$canCancel && !in_array($validated['status'], $allowed[$pickup->status] ?? [], true)) {
                return response()->json(['success' => false, 'message' => 'Invalid return delivery status transition.'], 422);
            }
        }

        $wasScheduledAt = $pickup->scheduled_at;
        $from = $pickup->status;
        $pickup->fill(collect($validated)->except(['latitude', 'longitude', 'location_address', 'photo'])->all());
        if (($validated['status'] ?? null) === 'picked_up' && !$pickup->picked_up_at) {
            $pickup->picked_up_at = now();
        }
        if (($validated['status'] ?? null) === 'out_for_delivery' && !$pickup->out_for_delivery_at) $pickup->out_for_delivery_at = now();
        if (($validated['status'] ?? null) === 'delivered' && !$pickup->delivered_at) $pickup->delivered_at = now();
        if (isset($validated['latitude'], $validated['longitude'])) {
            $pickup->current_latitude = $validated['latitude'];
            $pickup->current_longitude = $validated['longitude'];
            $pickup->current_address = $validated['location_address'] ?? $pickup->current_address;
        }
        $pickup->updated_by = $request->user()->id;
        $pickup->save();

        if (isset($validated['status']) && $validated['status'] !== $from) {
            $photoPath = $request->hasFile('photo') ? $request->file('photo')->store('logistics/returns/delivery/proofs', 'public') : null;
            ReturnPickupLog::create([
                'return_pickup_id' => $pickup->id, 'event_type' => 'status_updated', 'status_from' => $from,
                'status_to' => $validated['status'], 'message' => "Delivery status updated from {$from} to {$validated['status']}.",
                'latitude' => $validated['latitude'] ?? null, 'longitude' => $validated['longitude'] ?? null,
                'location_address' => $validated['location_address'] ?? null, 'proof_photo_path' => $photoPath,
                'notes' => $validated['notes'] ?? null, 'created_by' => $request->user()->id,
            ]);
        }

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
            'data' => $pickup->fresh(['driver', 'destinationBranch', 'logs.creator']),
        ]);
    }

    public function updateLocation(Request $request, ReturnPickup $pickup): JsonResponse
    {
        if ((int) $pickup->store_id !== (int) $request->user()->store_id || ($request->user()->hasRole('driver') && (int) $pickup->driver_user_id !== (int) $request->user()->id)) abort(403);
        $data = $request->validate(['latitude' => 'required|numeric|between:-90,90', 'longitude' => 'required|numeric|between:-180,180', 'location_address' => 'nullable|string|max:2000']);
        if (!in_array($pickup->status, ['assigned', 'picked_up', 'out_for_delivery'], true)) return response()->json(['success' => false, 'message' => 'Live tracking is only available while the return pickup is assigned or in transit.'], 422);
        $pickup->update(['current_latitude' => $data['latitude'], 'current_longitude' => $data['longitude'], 'current_address' => $data['location_address'] ?? $pickup->current_address]);
        return response()->json(['success' => true, 'data' => $pickup->fresh()]);
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
