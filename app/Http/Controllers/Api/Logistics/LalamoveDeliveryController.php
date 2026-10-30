<?php

namespace App\Http\Controllers\Api\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\EcommerceDeliveryLog;
use App\Models\Ecommerce\EcommerceOrder;
use App\Models\Ecommerce\EcommerceOrderDelivery;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesOrderDelivery;
use App\Models\Sales\SalesOrderDeliveryLog;
use App\Services\Logistics\LalamoveClient;
use App\Services\Logistics\DistanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use RuntimeException;
use Throwable;

class LalamoveDeliveryController extends Controller
{
    public function __construct(private readonly LalamoveClient $lalamove)
    {
    }

    public function services(Request $request): JsonResponse
    {
        $this->authorizeManager($request);

        try {
            $cities = $this->lalamove->cities();
            $mapped = collect($cities)->map(fn (array $city) => [
                'locode' => $city['locode'] ?? null,
                'name' => $city['name'] ?? null,
                'services' => collect($city['services'] ?? [])->map(fn (array $service) => [
                    'key' => $service['key'] ?? null,
                    'description' => $service['description'] ?? null,
                    'dimensions' => $service['dimensions'] ?? null,
                    'load' => $service['load'] ?? null,
                ])->filter(fn (array $service) => filled($service['key']))->values(),
            ])->filter(fn (array $city) => filled($city['locode']))->values();

            return response()->json(['success' => true, 'data' => $mapped]);
        } catch (Throwable $error) {
            return $this->failed($error);
        }
    }

    public function quote(
        Request $request,
        string $source,
        int $orderId,
        DistanceService $distanceService,
    ): JsonResponse
    {
        $this->authorizeManager($request);
        $source = $this->validateSource($source);
        $validated = $request->validate([
            'service_type' => ['required', 'string', 'max:50'],
        ]);

        try {
            [$order, $delivery, $branch] = $this->loadOrder($request, $source, $orderId);
            $this->assertOrderReady($order, $source, $delivery);

            $coordinates = $this->coordinates($order, $source, $branch, $distanceService);
            $payload = [
                'serviceType' => $validated['service_type'],
                'language' => config('services.lalamove.language', 'en_PH'),
                'stops' => [
                    [
                        'coordinates' => ['lat' => (string) $coordinates['pickup_lat'], 'lng' => (string) $coordinates['pickup_lng']],
                        'address' => $coordinates['pickup_address'],
                    ],
                    [
                        'coordinates' => ['lat' => (string) $coordinates['dropoff_lat'], 'lng' => (string) $coordinates['dropoff_lng']],
                        'address' => $coordinates['dropoff_address'],
                    ],
                ],
                'isRouteOptimized' => false,
            ];

            $quotation = $this->lalamove->quotation($payload);
            $stops = $quotation['stops'] ?? [];
            if (empty($quotation['quotationId']) || count($stops) < 2 || empty($stops[0]['stopId']) || empty($stops[1]['stopId'])) {
                throw new RuntimeException('Lalamove returned an incomplete quotation. Please request a new quote.');
            }

            $delivery ??= $this->newDelivery($order, $source, $request);
            $delivery->fill([
                'courier_provider' => 'lalamove',
                'status' => in_array(strtolower((string) $delivery->status), ['failed_delivery', 'cancelled'], true) ? 'pending' : $delivery->status,
                'lalamove_quotation_id' => (string) $quotation['quotationId'],
                'lalamove_order_id' => null,
                'lalamove_share_link' => null,
                'lalamove_status' => null,
                'lalamove_service_type' => $validated['service_type'],
                'lalamove_pickup_stop_id' => (string) $stops[0]['stopId'],
                'lalamove_dropoff_stop_id' => (string) $stops[1]['stopId'],
                'lalamove_price_breakdown' => $quotation['priceBreakdown'] ?? [],
                'lalamove_quote_expires_at' => isset($quotation['expiresAt'])
                    ? Carbon::parse($quotation['expiresAt'], 'UTC')->setTimezone(config('app.timezone'))
                    : now()->addMinutes(5),
                'updated_by' => $request->user()->id,
            ])->save();

            $this->log($source, $delivery, $order, 'lalamove_quote', 'Lalamove sandbox quotation received.', $request, [
                'quotation_id' => $quotation['quotationId'],
                'price_breakdown' => $quotation['priceBreakdown'] ?? [],
                'service_type' => $validated['service_type'],
            ]);

            return response()->json(['success' => true, 'data' => [
                'quotation_id' => $delivery->lalamove_quotation_id,
                'service_type' => $delivery->lalamove_service_type,
                'price_breakdown' => $delivery->lalamove_price_breakdown,
                'expires_at' => $delivery->lalamove_quote_expires_at,
            ]]);
        } catch (Throwable $error) {
            return $this->failed($error);
        }
    }

    public function book(Request $request, string $source, int $orderId): JsonResponse
    {
        $this->authorizeManager($request);
        $source = $this->validateSource($source);

        try {
            [$order, $delivery, $branch] = $this->loadOrder($request, $source, $orderId);
            if ($delivery?->lalamove_order_id) {
                return response()->json(['success' => true, 'message' => 'Lalamove delivery is already booked.', 'data' => $this->bookingData($delivery)]);
            }
            $this->assertOrderReady($order, $source, $delivery);
            if (!$delivery || $delivery->courier_provider !== 'lalamove' || !$delivery->lalamove_quotation_id) {
                return response()->json(['success' => false, 'message' => 'Request a Lalamove quotation before booking.'], 422);
            }
            if ($delivery->lalamove_quote_expires_at?->isPast()) {
                return response()->json(['success' => false, 'message' => 'This quotation expired. Request a new quote before booking.'], 422);
            }

            $pickupContact = $this->contact($branch->contact_number ?: $branch->store?->phone);
            $recipientPhone = $this->contact($source === 'ecommerce' ? $order->shipping_phone : $order->customer_phone);
            $pickupName = trim((string) ($branch->name ?: $branch->store?->name));
            $recipientName = trim((string) ($source === 'ecommerce' ? $order->shipping_name : $order->customer_name));
            if (!$pickupContact || !$recipientPhone || !$pickupName || !$recipientName) {
                return response()->json(['success' => false, 'message' => 'A valid pickup contact and customer name/phone are required. Phone numbers must be valid Philippine numbers.'], 422);
            }

            $response = $this->lalamove->createOrder([
                'quotationId' => $delivery->lalamove_quotation_id,
                'sender' => [
                    'stopId' => $delivery->lalamove_pickup_stop_id,
                    'name' => $pickupName,
                    'phone' => $pickupContact,
                ],
                'recipients' => [[
                    'stopId' => $delivery->lalamove_dropoff_stop_id,
                    'name' => $recipientName,
                    'phone' => $recipientPhone,
                    ...array_filter(['remarks' => $source === 'sales' ? ($order->delivery_notes ?: null) : ($order->notes ?: null)]),
                ]],
                'isPODEnabled' => true,
                'metadata' => [
                    'source' => $source,
                    'orderNumber' => (string) ($order->order_number ?? $order->id),
                ],
            ]);

            if (empty($response['orderId'])) {
                throw new RuntimeException('Lalamove did not return an order ID. Check the sandbox response before retrying.');
            }

            $delivery->fill([
                'courier_provider' => 'lalamove',
                'courier_name' => 'Lalamove',
                'courier_contact' => null,
                'lalamove_order_id' => (string) $response['orderId'],
                'lalamove_status' => (string) ($response['status'] ?? 'ASSIGNING_DRIVER'),
                'lalamove_share_link' => $response['shareLink'] ?? null,
                'lalamove_price_breakdown' => $response['priceBreakdown'] ?? $delivery->lalamove_price_breakdown,
                'status' => 'assigned',
                'updated_by' => $request->user()->id,
            ])->save();

            $this->log($source, $delivery, $order, 'lalamove_booked', 'Delivery booked with Lalamove sandbox.', $request, [
                'lalamove_order_id' => $delivery->lalamove_order_id,
                'lalamove_status' => $delivery->lalamove_status,
                'share_link' => $delivery->lalamove_share_link,
                'price_breakdown' => $delivery->lalamove_price_breakdown,
            ]);

            return response()->json(['success' => true, 'message' => 'Lalamove delivery booked.', 'data' => $this->bookingData($delivery)], 201);
        } catch (Throwable $error) {
            return $this->failed($error);
        }
    }

    public function refresh(Request $request, string $source, int $orderId): JsonResponse
    {
        $this->authorizeManager($request);
        $source = $this->validateSource($source);

        try {
            [$order, $delivery] = $this->loadOrder($request, $source, $orderId);
            if (!$delivery?->lalamove_order_id) {
                return response()->json(['success' => false, 'message' => 'There is no Lalamove booking to refresh.'], 422);
            }

            $response = $this->lalamove->order((string) $delivery->lalamove_order_id);
            $newStatus = strtoupper((string) ($response['status'] ?? $delivery->lalamove_status));
            $oldStatus = strtoupper((string) $delivery->lalamove_status);
            $delivery->lalamove_status = $newStatus;
            $delivery->lalamove_share_link = $response['shareLink'] ?? $delivery->lalamove_share_link;
            $delivery->lalamove_price_breakdown = $response['priceBreakdown'] ?? $delivery->lalamove_price_breakdown;
            $internalStatus = $this->internalStatus($newStatus);
            if ($internalStatus) {
                $delivery->status = $internalStatus;
                if ($internalStatus === 'in_transit' && !$delivery->dispatched_at) $delivery->dispatched_at = now();
                if ($internalStatus === 'out_for_delivery' && !$delivery->out_for_delivery_at) $delivery->out_for_delivery_at = now();
                if ($internalStatus === 'delivered' && !$delivery->delivered_at) $delivery->delivered_at = now();
            }
            $delivery->updated_by = $request->user()->id;
            $delivery->save();

            if ($source === 'ecommerce' && in_array($newStatus, ['PICKED_UP', 'COMPLETED'], true)) {
                $order->status = $newStatus === 'COMPLETED' ? 'delivered' : 'in_transit';
                $order->save();
            }

            if ($newStatus !== $oldStatus) {
                $this->log($source, $delivery, $order, 'lalamove_status', 'Lalamove delivery status updated.', $request, [
                    'lalamove_status_from' => $oldStatus ?: null,
                    'lalamove_status_to' => $newStatus,
                    'price_breakdown' => $delivery->lalamove_price_breakdown,
                ]);
            }

            return response()->json(['success' => true, 'data' => $this->bookingData($delivery)]);
        } catch (Throwable $error) {
            return $this->failed($error);
        }
    }

    private function loadOrder(Request $request, string $source, int $orderId): array
    {
        $storeId = (int) $request->user()->store_id;
        abort_if(!$storeId, 422, 'No store is assigned to this account.');

        if ($source === 'ecommerce') {
            $order = EcommerceOrder::query()->with(['assignedBranch.store', 'delivery'])
                ->where('store_id', $storeId)->findOrFail($orderId);
            return [$order, $order->delivery, $order->assignedBranch];
        }

        $order = SalesOrder::query()->with(['branch.store', 'delivery'])
            ->where('store_id', $storeId)->findOrFail($orderId);
        return [$order, $order->delivery, $order->branch];
    }

    private function assertOrderReady($order, string $source, $delivery): void
    {
        $status = strtolower((string) $order->status);
        $ready = $source === 'ecommerce'
            ? $status === 'ready_for_dispatch'
            : ($order->delivery_required && (in_array($status, ['ready_for_dispatch', 'ready_to_dispatch'], true)
                || ($status === 'completed' && strtolower((string) $order->payment_status) === 'paid')));

        abort_unless($ready, 422, 'The order must be ready for dispatch before booking a courier.');
        abort_if($delivery && $delivery->trip_id, 422, 'This order is already assigned to an in-house delivery trip.');
        abort_if($delivery && $delivery->lalamove_order_id, 422, 'A Lalamove delivery is already booked for this order.');
        $retryableLalamoveFailure = $delivery
            && $delivery->courier_provider === 'lalamove'
            && in_array(strtolower((string) $delivery->status), ['failed_delivery', 'cancelled'], true);
        abort_if($delivery && !in_array(strtolower((string) $delivery->status), ['pending', 'ready_for_dispatch'], true) && !$retryableLalamoveFailure, 422, 'This delivery has already been assigned.');
    }

    private function coordinates(
        $order,
        string $source,
        $branch,
        DistanceService $distanceService,
    ): array
    {
        $pickupAddressParts = array_filter([$branch?->address, $branch?->city, $branch?->province]);
        $pickupAddress = $pickupAddressParts
            ? trim(implode(', ', $pickupAddressParts) . ', Philippines')
            : '';
        $dropoffAddress = $this->customerGeocodingAddress($order, $source);

        [$pickupLatitude, $pickupLongitude] = $this->resolveCoordinates(
            $branch?->latitude,
            $branch?->longitude,
            $pickupAddress,
            'Pickup branch',
            $distanceService,
        );
        [$dropoffLatitude, $dropoffLongitude] = $this->resolveCoordinates(
            $source === 'ecommerce' ? $order->customer_latitude : $order->delivery_latitude,
            $source === 'ecommerce' ? $order->customer_longitude : $order->delivery_longitude,
            $dropoffAddress,
            'Customer delivery address',
            $distanceService,
        );

        $coordinates = [
            'pickup_lat' => $pickupLatitude,
            'pickup_lng' => $pickupLongitude,
            'pickup_address' => $pickupAddress,
            'dropoff_lat' => $dropoffLatitude,
            'dropoff_lng' => $dropoffLongitude,
            'dropoff_address' => $dropoffAddress,
        ];

        foreach (['pickup_address', 'dropoff_address'] as $key) {
            if (!filled($coordinates[$key])) abort(422, 'Pickup and delivery addresses are required.');
        }
        if (!$branch) abort(422, 'The order has no assigned pickup branch.');

        return $coordinates;
    }

    private function customerGeocodingAddress($order, string $source): string
    {
        if ($source === 'sales') {
            $addressParts = array_filter([
                $order->delivery_address_line,
                $order->delivery_barangay,
                $order->delivery_city,
                $order->delivery_province,
            ]);

            if ($addressParts) {
                return implode(', ', $addressParts) . ', Philippines';
            }

            $fallback = trim((string) $order->delivery_address);
            return $fallback !== '' ? $fallback . ', Philippines' : '';
        }

        $parts = array_map('trim', explode(',', (string) $order->shipping_address, 4));
        if (count($parts) >= 3) {
            $orderedParts = array_reverse($parts);
            return implode(', ', array_filter([...$orderedParts, 'Philippines']));
        }

        $fallback = trim((string) $order->shipping_address);
        return $fallback !== '' ? $fallback . ', Philippines' : '';
    }

    private function resolveCoordinates(
        mixed $latitude,
        mixed $longitude,
        string $address,
        string $label,
        DistanceService $distanceService,
    ): array {
        if (is_numeric($latitude) && is_numeric($longitude)) {
            return [(float) $latitude, (float) $longitude];
        }

        if (!filled($address)) {
            abort(422, "{$label} has no saved coordinates or complete address. Add a map pin or complete the address before requesting a Lalamove quote.");
        }

        try {
            $point = $distanceService->geocodeAddress($address);
        } catch (Throwable $error) {
            report($error);
            abort(422, "{$label} coordinates are missing and its address could not be mapped. Add or correct the map pin, and confirm Mapbox is configured.");
        }

        return [$point['lat'], $point['lon']];
    }

    private function newDelivery($order, string $source, Request $request)
    {
        $data = [
            'store_id' => $order->store_id,
            'status' => 'pending',
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ];
        if ($source === 'ecommerce') {
            return EcommerceOrderDelivery::query()->create($data + ['order_id' => $order->id]);
        }
        return SalesOrderDelivery::query()->create($data + ['sales_order_id' => $order->id, 'branch_id' => $order->branch_id]);
    }

    private function log(string $source, $delivery, $order, string $event, string $message, Request $request, array $meta): void
    {
        $storedEvent = $event;
        if ($source === 'ecommerce' && str_starts_with($event, 'lalamove_')) {
            $storedEvent = $event === 'lalamove_status' ? 'status_updated' : 'note';
            $meta['integration_event'] = $event;
        }

        $data = [
            'delivery_id' => $delivery->id,
            'store_id' => $order->store_id,
            'event_type' => $storedEvent,
            'message' => $message,
            'meta' => $meta,
            'created_by' => $request->user()->id,
        ];

        if ($source === 'ecommerce') {
            EcommerceDeliveryLog::query()->create($data + ['order_id' => $order->id]);
        } else {
            SalesOrderDeliveryLog::query()->create($data + ['sales_order_id' => $order->id]);
        }
    }

    private function bookingData($delivery): array
    {
        return [
            'provider' => $delivery->courier_provider,
            'quotation_id' => $delivery->lalamove_quotation_id,
            'order_id' => $delivery->lalamove_order_id,
            'status' => $delivery->lalamove_status,
            'service_type' => $delivery->lalamove_service_type,
            'share_link' => $delivery->lalamove_share_link,
            'price_breakdown' => $delivery->lalamove_price_breakdown,
            'expires_at' => $delivery->lalamove_quote_expires_at,
        ];
    }

    private function internalStatus(string $status): ?string
    {
        return match ($status) {
            'PICKED_UP' => 'in_transit',
            'COMPLETED' => 'delivered',
            'CANCELED' => 'cancelled',
            'REJECTED', 'EXPIRED' => 'failed_delivery',
            default => null,
        };
    }

    private function contact(?string $phone): ?string
    {
        $phone = preg_replace('/[^0-9+]/', '', (string) $phone);
        if (str_starts_with($phone, '+')) return preg_match('/^\+[1-9]\d{7,14}$/', $phone) ? $phone : null;
        $digits = ltrim($phone, '+');
        if (str_starts_with($digits, '0')) $digits = '63' . substr($digits, 1);
        elseif (str_starts_with($digits, '9')) $digits = '63' . $digits;
        return preg_match('/^\+[1-9]\d{7,14}$/', '+' . $digits) ? '+' . $digits : null;
    }

    private function validateSource(string $source): string
    {
        abort_unless(in_array($source, ['ecommerce', 'sales'], true), 404);
        return $source;
    }

    private function authorizeManager(Request $request): void
    {
        abort_unless($request->user()?->hasPermissionTo('logistics.deliveries.manage'), 403);
    }

    private function failed(Throwable $error): JsonResponse
    {
        report($error);
        if ($error instanceof ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'The requested order was not found.'], 404);
        }
        if ($error instanceof HttpExceptionInterface) {
            return response()->json(['success' => false, 'message' => $error->getMessage()], $error->getStatusCode());
        }
        if ($error instanceof RuntimeException && str_contains($error->getMessage(), 'credentials are not configured')) {
            return response()->json(['success' => false, 'message' => $error->getMessage()], 503);
        }
        if ($error instanceof RuntimeException && preg_match('/^Lalamove API \((\d{3})\):\s*(.*)$/s', $error->getMessage(), $matches)) {
            $upstreamStatus = (int) $matches[1];
            $upstreamMessage = trim($matches[2]);

            if ($upstreamStatus === 422 && str_contains(strtolower($upstreamMessage), 'out of service area')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lalamove cannot serve the pickup or delivery coordinates for this vehicle service. Check both map pins or choose another available service.',
                ], 422);
            }

            if ($upstreamStatus === 422) {
                return response()->json(['success' => false, 'message' => $upstreamMessage], 422);
            }

            if ($upstreamStatus === 402) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lalamove reports insufficient wallet credit. Verify that FurniSync is using your sandbox API key and secret with the sandbox host. Do not top up a production wallet unless you intend to make real bookings.',
                ], 402);
            }

            return response()->json(['success' => false, 'message' => $error->getMessage()], 502);
        }
        if ($error instanceof RuntimeException && str_starts_with($error->getMessage(), 'Lalamove returned')) {
            return response()->json(['success' => false, 'message' => $error->getMessage()], 502);
        }
        return response()->json(['success' => false, 'message' => 'Unable to complete the Lalamove request. Please try again or contact support.'], 502);
    }
}
