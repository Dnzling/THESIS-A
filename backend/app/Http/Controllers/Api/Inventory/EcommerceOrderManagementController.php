<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\EcommerceDeliveryLog;
use App\Models\Ecommerce\EcommerceOrder;
use App\Models\Ecommerce\EcommerceOrderDelivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EcommerceOrderManagementController extends Controller
{
    private const ORDER_STATUSES = [
        'pending',
        'processing',
        'packed',
        'shipped',
        'in_transit',
        'out_for_delivery',
        'delivered',
        'cancelled',
    ];

    public function index(Request $request): JsonResponse
    {
        $query = EcommerceOrder::query()
            ->with([
                'user:id,fname,lname,email',
                'store',
                'delivery:id,order_id,status,vehicle_id,tracking_number,courier_name,estimated_delivery_at,dispatched_at,delivered_at',
            ])
            ->withCount('items');

        $this->applyStoreScope($request, $query);

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('shipping_name', 'like', "%{$search}%")
                    ->orWhere('shipping_phone', 'like', "%{$search}%");
            });
        }

        $orders = $query->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json(['success' => true, 'data' => $orders]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $query = EcommerceOrder::query()
            ->with([
                'user:id,fname,lname,email',
                'store',
                'items:id,order_id,product_id,product_name,sku,quantity,unit_price,line_total',
                'items.product:id,product_name,sku',
                'delivery.vehicle:id,vehicle_name,plate_number,vehicle_type,status',
                'delivery.logs:id,delivery_id,order_id,event_type,status_from,status_to,message,created_by,created_at',
                'delivery.logs.creator:id,fname,lname',
            ]);

        $this->applyStoreScope($request, $query);
        $order = $query->findOrFail($id);

        $data = $order->toArray();
        $data['timeline'] = $this->formatOrderTimeline($order);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(self::ORDER_STATUSES)],
            'delivery' => 'nullable|array',
            'delivery.vehicle_id' => 'nullable|exists:ecommerce_delivery_vehicles,id',
            'delivery.tracking_number' => 'nullable|string|max:80',
            'delivery.courier_name' => 'nullable|string|max:120',
            'delivery.courier_contact' => 'nullable|string|max:50',
            'delivery.estimated_delivery_at' => 'nullable|date',
            'delivery.notes' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
        ]);

        $query = EcommerceOrder::query()->with('delivery');
        $this->applyStoreScope($request, $query);
        $order = $query->findOrFail($id);

        $targetStatus = (string) $validated['status'];
        $this->ensureValidTransition($order->status, $targetStatus);

        DB::transaction(function () use ($request, $order, $validated, $targetStatus) {
            $previousOrderStatus = (string) $order->status;
            $order->status = $targetStatus;

            if (!empty($validated['notes'])) {
                $current = trim((string) $order->notes);
                $line = '[' . now()->format('Y-m-d H:i') . '] ' . trim((string) $validated['notes']);
                $order->notes = $current === '' ? $line : $current . PHP_EOL . $line;
            }

            if ($targetStatus === 'delivered' && $order->payment_method === 'cod' && $order->payment_status === 'unpaid') {
                $order->payment_status = 'paid';
            }

            $order->save();

            $deliveryPayload = $validated['delivery'] ?? [];
            $needsDelivery = in_array($targetStatus, ['packed', 'shipped', 'in_transit', 'out_for_delivery', 'delivered'], true) || !empty($deliveryPayload);

            if ($needsDelivery) {
                $delivery = EcommerceOrderDelivery::query()->firstOrNew(['order_id' => $order->id], [
                    'store_id' => $order->store_id,
                    'created_by' => $request->user()->id,
                ]);
                $isNewDelivery = !$delivery->exists;
                $previousDeliveryStatus = (string) ($delivery->status ?: 'assigned');

                $delivery->store_id = $order->store_id;
                $delivery->vehicle_id = $deliveryPayload['vehicle_id'] ?? $delivery->vehicle_id;
                $delivery->tracking_number = $deliveryPayload['tracking_number'] ?? $delivery->tracking_number;
                $delivery->courier_name = $deliveryPayload['courier_name'] ?? $delivery->courier_name;
                $delivery->courier_contact = $deliveryPayload['courier_contact'] ?? $delivery->courier_contact;
                $delivery->estimated_delivery_at = $deliveryPayload['estimated_delivery_at'] ?? $delivery->estimated_delivery_at;
                $delivery->notes = $deliveryPayload['notes'] ?? $delivery->notes;
                $delivery->updated_by = $request->user()->id;

                $deliveryStatus = match ($targetStatus) {
                    'packed' => 'packed',
                    'shipped', 'in_transit' => 'in_transit',
                    'out_for_delivery' => 'out_for_delivery',
                    'delivered' => 'delivered',
                    'cancelled' => 'cancelled',
                    default => $delivery->status ?: 'assigned',
                };
                $delivery->status = $deliveryStatus;

                if (in_array($targetStatus, ['shipped', 'in_transit'], true) && !$delivery->dispatched_at) {
                    $delivery->dispatched_at = now();
                }
                if ($targetStatus === 'out_for_delivery' && !$delivery->out_for_delivery_at) {
                    $delivery->out_for_delivery_at = now();
                }
                if ($targetStatus === 'delivered' && !$delivery->delivered_at) {
                    $delivery->delivered_at = now();
                }

                $delivery->save();

                if ($isNewDelivery) {
                    EcommerceDeliveryLog::query()->create([
                        'delivery_id' => $delivery->id,
                        'order_id' => $order->id,
                        'store_id' => $order->store_id,
                        'event_type' => 'created',
                        'status_to' => $delivery->status,
                        'message' => 'Delivery record created.',
                        'created_by' => $request->user()->id,
                    ]);
                }

                if ($previousDeliveryStatus !== (string) $delivery->status) {
                    EcommerceDeliveryLog::query()->create([
                        'delivery_id' => $delivery->id,
                        'order_id' => $order->id,
                        'store_id' => $order->store_id,
                        'event_type' => 'status_updated',
                        'status_from' => $previousDeliveryStatus,
                        'status_to' => (string) $delivery->status,
                        'message' => "Delivery status updated from {$previousDeliveryStatus} to {$delivery->status}.",
                        'created_by' => $request->user()->id,
                    ]);
                }
            }

            if ($previousOrderStatus !== $targetStatus && $order->delivery) {
                EcommerceDeliveryLog::query()->create([
                    'delivery_id' => $order->delivery->id,
                    'order_id' => $order->id,
                    'store_id' => $order->store_id,
                    'event_type' => 'status_updated',
                    'status_from' => $previousOrderStatus,
                    'status_to' => $targetStatus,
                    'message' => "Order status updated from {$previousOrderStatus} to {$targetStatus}.",
                    'created_by' => $request->user()->id,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => EcommerceOrder::query()->with(['delivery.vehicle'])->find($order->id),
        ]);
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

    private function ensureValidTransition(string $current, string $target): void
    {
        if ($current === $target) {
            return;
        }

        $flow = [
            'pending' => ['processing', 'cancelled'],
            'processing' => ['packed', 'cancelled'],
            'packed' => ['shipped', 'cancelled'],
            'shipped' => ['in_transit', 'out_for_delivery', 'delivered', 'cancelled'],
            'in_transit' => ['out_for_delivery', 'delivered', 'cancelled'],
            'out_for_delivery' => ['delivered', 'cancelled'],
            'delivered' => [],
            'cancelled' => [],
        ];

        $allowedTargets = $flow[$current] ?? [];
        if (!in_array($target, $allowedTargets, true)) {
            abort(response()->json([
                'success' => false,
                'message' => "Invalid status transition from '{$current}' to '{$target}'.",
            ], 422));
        }
    }

    private function formatOrderTimeline(EcommerceOrder $order): array
    {
        $timeline = [[
            'type' => 'order_created',
            'title' => 'Order placed',
            'description' => 'Customer placed the order.',
            'status_to' => (string) $order->status,
            'actor' => trim((string) (($order->user?->fname ?? '') . ' ' . ($order->user?->lname ?? ''))) ?: 'Customer',
            'created_at' => $order->placed_at ?? $order->created_at,
        ]];

        $deliveryLogs = collect($order->delivery?->logs ?? [])->sortBy('created_at');

        foreach ($deliveryLogs as $log) {
            $actor = trim((string) (($log->creator?->fname ?? '') . ' ' . ($log->creator?->lname ?? '')));
            $timeline[] = [
                'type' => $log->event_type ?: 'update',
                'title' => $this->timelineTitleFromLog($log->event_type, $log->status_to),
                'description' => $log->message ?: 'Order updated.',
                'status_from' => $log->status_from,
                'status_to' => $log->status_to,
                'actor' => $actor !== '' ? $actor : 'System',
                'created_at' => $log->created_at,
            ];
        }

        return collect($timeline)
            ->sortByDesc('created_at')
            ->values()
            ->all();
    }

    private function timelineTitleFromLog(?string $eventType, ?string $statusTo): string
    {
        if ($eventType === 'status_updated' && $statusTo) {
            return 'Status: ' . str($statusTo)->replace('_', ' ')->title();
        }

        return match ($eventType) {
            'created' => 'Delivery created',
            'driver_assigned' => 'Driver assigned',
            'proof_uploaded' => 'Proof uploaded',
            default => 'Order update',
        };
    }
}
