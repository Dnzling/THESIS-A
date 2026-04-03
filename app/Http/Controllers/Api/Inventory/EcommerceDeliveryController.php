<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Core\User;
use App\Models\Ecommerce\EcommerceDeliveryLog;
use App\Models\Ecommerce\EcommerceOrderDelivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EcommerceDeliveryController extends Controller
{
    private const DELIVERY_STATUSES = [
        'assigned',
        'packed',
        'in_transit',
        'out_for_delivery',
        'delivered',
        'failed_delivery',
        'cancelled',
    ];

    public function index(Request $request): JsonResponse
    {
        $query = EcommerceOrderDelivery::query()
            ->with([
                'order:id,order_number,status,total_amount,shipping_name,shipping_phone,shipping_address',
                'vehicle:id,vehicle_name,plate_number,vehicle_type,status',
                'driver:id,fname,lname,email',
            ]);

        $this->applyStoreScope($request, $query);

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                    ->orWhere('courier_name', 'like', "%{$search}%")
                    ->orWhereHas('order', function ($orderQuery) use ($search) {
                        $orderQuery->where('order_number', 'like', "%{$search}%")
                            ->orWhere('shipping_name', 'like', "%{$search}%");
                    });
            });
        }

        $deliveries = $query->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json(['success' => true, 'data' => $deliveries]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $query = EcommerceOrderDelivery::query()
            ->with([
                'order:id,order_number,status,total_amount,payment_method,payment_status,shipping_name,shipping_phone,shipping_address,created_at',
                'order.items:id,order_id,product_name,sku,quantity,unit_price,line_total',
                'vehicle:id,vehicle_name,plate_number,vehicle_type,status,capacity_kg,max_orders_per_trip',
                'driver:id,fname,lname,email',
                'logs:id,delivery_id,order_id,event_type,status_from,status_to,message,meta,created_by,created_at',
                'logs.creator:id,fname,lname,email',
            ]);

        $this->applyStoreScope($request, $query);
        $delivery = $query->findOrFail($id);

        $data = $delivery->toArray();
        $data['proof_photo_url'] = $delivery->proof_of_delivery_path ? Storage::disk('public')->url($delivery->proof_of_delivery_path) : null;
        $data['proof_signature_url'] = $delivery->proof_signature_path ? Storage::disk('public')->url($delivery->proof_signature_path) : null;

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(self::DELIVERY_STATUSES)],
            'failed_reason' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
        ]);

        $query = EcommerceOrderDelivery::query()->with('order');
        $this->applyStoreScope($request, $query);
        $delivery = $query->findOrFail($id);
        $previousStatus = (string) $delivery->status;

        $delivery->status = $validated['status'];
        $delivery->failed_reason = $validated['failed_reason'] ?? $delivery->failed_reason;
        $delivery->notes = $validated['notes'] ?? $delivery->notes;
        $delivery->updated_by = $request->user()->id;

        if ($validated['status'] === 'in_transit' && !$delivery->dispatched_at) {
            $delivery->dispatched_at = now();
        }
        if ($validated['status'] === 'out_for_delivery' && !$delivery->out_for_delivery_at) {
            $delivery->out_for_delivery_at = now();
        }
        if ($validated['status'] === 'delivered' && !$delivery->delivered_at) {
            $delivery->delivered_at = now();
        }

        $delivery->save();
        $this->logEvent(
            $delivery,
            'status_updated',
            "Delivery status updated from {$previousStatus} to {$validated['status']}.",
            $request->user()->id,
            $previousStatus,
            $validated['status']
        );

        $orderStatus = match ($validated['status']) {
            'packed' => 'packed',
            'in_transit' => 'in_transit',
            'out_for_delivery' => 'out_for_delivery',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            default => $delivery->order->status,
        };

        if ($orderStatus !== $delivery->order->status) {
            $delivery->order->status = $orderStatus;
            if ($orderStatus === 'delivered' && $delivery->order->payment_method === 'cod' && $delivery->order->payment_status === 'unpaid') {
                $delivery->order->payment_status = 'paid';
            }
            $delivery->order->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Delivery updated successfully.',
            'data' => $delivery->fresh(['order', 'vehicle', 'driver', 'logs.creator']),
        ]);
    }

    public function drivers(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->store_id && !$user->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'No store assigned.'], 422);
        }

        $storeId = $user->hasRole('super_admin') && $request->filled('store_id')
            ? (int) $request->input('store_id')
            : (int) $user->store_id;
        $branchId = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;

        $roleIds = DB::table('role_permissions')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->whereIn('permissions.name', ['logistics.deliveries.view', 'logistics.deliveries.manage'])
            ->pluck('role_permissions.role_id')
            ->unique();

        $drivers = User::query()
            ->with(['role:id,name,display_name', 'employee:id,user_id,branch_id,phone,status'])
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->when($roleIds->isNotEmpty(), fn ($q) => $q->whereIn('role_id', $roleIds))
            ->when($branchId, fn ($q) => $q->whereHas('employee', fn ($employee) => $employee->where('branch_id', $branchId)))
            ->orderBy('fname')
            ->orderBy('lname')
            ->get()
            ->map(fn(User $driver) => [
                'id' => $driver->id,
                'name' => trim(($driver->fname ?? '') . ' ' . ($driver->lname ?? '')),
                'email' => $driver->email,
                'contact' => $driver->employee?->phone ?? $driver->phone_number,
                'branch_id' => $driver->employee?->branch_id,
                'role' => $driver->role?->display_name ?? $driver->role?->name ?? 'N/A',
            ])
            ->values();

        return response()->json(['success' => true, 'data' => $drivers]);
    }

    public function assignDriver(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'driver_user_id' => 'required|exists:users,id',
        ]);

        $query = EcommerceOrderDelivery::query()->with('order');
        $this->applyStoreScope($request, $query);
        $delivery = $query->findOrFail($id);

        $roleIds = DB::table('role_permissions')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->whereIn('permissions.name', ['logistics.deliveries.view', 'logistics.deliveries.manage'])
            ->pluck('role_permissions.role_id')
            ->unique();

        $driver = User::query()
            ->where('id', (int) $validated['driver_user_id'])
            ->where('store_id', $delivery->store_id)
            ->where('is_active', true)
            ->when($roleIds->isNotEmpty(), fn ($q) => $q->whereIn('role_id', $roleIds))
            ->firstOrFail();

        $delivery->driver_user_id = $driver->id;
        $delivery->updated_by = $request->user()->id;
        $delivery->save();

        $this->logEvent(
            $delivery,
            'driver_assigned',
            'Driver assigned: ' . trim(($driver->fname ?? '') . ' ' . ($driver->lname ?? '')),
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Driver assigned successfully.',
            'data' => $delivery->fresh(['order', 'vehicle', 'driver']),
        ]);
    }

    public function uploadProof(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'photo' => 'nullable|image|max:5120',
            'signature' => 'nullable|image|max:5120',
            'notes' => 'nullable|string|max:1000',
        ]);

        if (!$request->hasFile('photo') && !$request->hasFile('signature')) {
            return response()->json(['success' => false, 'message' => 'Photo or signature is required.'], 422);
        }

        $query = EcommerceOrderDelivery::query()->with('order');
        $this->applyStoreScope($request, $query);
        $delivery = $query->findOrFail($id);

        if ($request->hasFile('photo')) {
            $delivery->proof_of_delivery_path = $request->file('photo')->store('ecommerce/delivery-proofs/photos', 'public');
        }
        if ($request->hasFile('signature')) {
            $delivery->proof_signature_path = $request->file('signature')->store('ecommerce/delivery-proofs/signatures', 'public');
        }
        if (!empty($validated['notes'])) {
            $delivery->notes = trim((string) $validated['notes']);
        }
        $previousStatus = (string) $delivery->status;
        $delivery->status = 'delivered';
        $delivery->delivered_at = $delivery->delivered_at ?: now();
        $delivery->updated_by = $request->user()->id;
        $delivery->save();

        if ($delivery->order->status !== 'delivered') {
            $delivery->order->status = 'delivered';
            if ($delivery->order->payment_method === 'cod' && $delivery->order->payment_status === 'unpaid') {
                $delivery->order->payment_status = 'paid';
            }
            $delivery->order->save();
        }

        $proofPhotoUrl = $delivery->proof_of_delivery_path ? Storage::disk('public')->url($delivery->proof_of_delivery_path) : null;
        $proofSignatureUrl = $delivery->proof_signature_path ? Storage::disk('public')->url($delivery->proof_signature_path) : null;

        $this->logEvent(
            $delivery,
            'proof_uploaded',
            'Delivery proof uploaded.',
            $request->user()->id,
            null,
            null,
            [
                'proof_photo_url' => $proofPhotoUrl,
                'proof_signature_url' => $proofSignatureUrl,
            ],
        );

        if ($previousStatus !== 'delivered') {
            $this->logEvent(
                $delivery,
                'status_updated',
                "Delivery status updated from {$previousStatus} to delivered.",
                $request->user()->id,
                $previousStatus,
                'delivered'
            );
            $this->logEvent(
                $delivery,
                'note',
                'Delivery marked as Delivered after proof submission.',
                $request->user()->id
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Delivery proof uploaded successfully.',
            'data' => [
                'proof_photo_url' => $proofPhotoUrl,
                'proof_signature_url' => $proofSignatureUrl,
                'status' => $delivery->status,
            ],
        ]);
    }

    public function logs(Request $request, int $id): JsonResponse
    {
        $query = EcommerceOrderDelivery::query();
        $this->applyStoreScope($request, $query);
        $delivery = $query->findOrFail($id);

        $logs = EcommerceDeliveryLog::query()
            ->with('creator:id,fname,lname,email')
            ->where('delivery_id', $delivery->id)
            ->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json(['success' => true, 'data' => $logs]);
    }

    public function addLog(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $query = EcommerceOrderDelivery::query();
        $this->applyStoreScope($request, $query);
        $delivery = $query->findOrFail($id);

        $log = $this->logEvent(
            $delivery,
            'note',
            (string) $validated['message'],
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Log added.',
            'data' => $log->load('creator:id,fname,lname,email'),
        ], 201);
    }

    private function applyStoreScope(Request $request, $query): void
    {
        $user = $request->user();
        if (!$user->hasRole('super_admin')) {
            $query->where('store_id', $user->store_id);
            return;
        }

        if ($request->filled('store_id')) {
            $query->where('store_id', (int) $request->input('store_id'));
        }
    }

    private function logEvent(
        EcommerceOrderDelivery $delivery,
        string $eventType,
        string $message,
        ?int $createdBy = null,
        ?string $statusFrom = null,
        ?string $statusTo = null,
        ?array $meta = null,
    ): EcommerceDeliveryLog {
        return EcommerceDeliveryLog::query()->create([
            'delivery_id' => $delivery->id,
            'order_id' => $delivery->order_id,
            'store_id' => $delivery->store_id,
            'event_type' => $eventType,
            'status_from' => $statusFrom,
            'status_to' => $statusTo,
            'message' => $message,
            'meta' => $meta,
            'created_by' => $createdBy,
        ]);
    }
}
