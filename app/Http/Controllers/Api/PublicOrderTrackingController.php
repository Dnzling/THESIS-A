<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\EcommerceOrderDelivery;
use App\Models\Sales\SalesOrderDelivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicOrderTrackingController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tracking_number' => ['required', 'string', 'max:100'],
        ]);

        $trackingNumber = trim($validated['tracking_number']);
        $ecommerceDelivery = EcommerceOrderDelivery::query()
            ->with('order:id,order_number,status,requested_fulfillment_date,customer_latitude,customer_longitude')
            ->where('tracking_number', $trackingNumber)
            ->first();
        $salesDelivery = $ecommerceDelivery ? null : SalesOrderDelivery::query()
            ->with('order:id,order_number,status,delivery_latitude,delivery_longitude')
            ->where('tracking_number', $trackingNumber)
            ->first();

        $delivery = $ecommerceDelivery ?? $salesDelivery;
        $order = $delivery?->order;

        $orderStatus = strtolower((string) ($order?->status ?? ''));
        if (!$delivery || !$order || in_array(str_replace('-', '_', $orderStatus), ['cancelled', 'canceled'], true)
            || in_array(strtolower((string) $delivery->status), ['cancelled', 'canceled'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'We could not find an active delivery with that tracking number.',
            ], 404);
        }

        $outForDelivery = strtolower((string) $delivery->status) === 'out_for_delivery'
            && !in_array(str_replace('-', '_', $orderStatus), ['cancelled', 'canceled'], true);
        $isEcommerce = $delivery instanceof EcommerceOrderDelivery;

        return response()->json([
            'success' => true,
            'data' => [
                'order_number' => (string) $order->order_number,
                'tracking_number' => (string) $delivery->tracking_number,
                'status' => strtolower((string) $delivery->status),
                'order_status' => $orderStatus,
                'expected_delivery_date' => $isEcommerce
                    ? $order->requested_fulfillment_date?->toDateString() ?? $delivery->estimated_delivery_at?->toDateString()
                    : $delivery->scheduled_delivery_at?->toDateString(),
                'live_tracking' => $outForDelivery ? [
                    'destination' => $this->coordinates(
                        $isEcommerce ? $order->customer_latitude : $order->delivery_latitude,
                        $isEcommerce ? $order->customer_longitude : $order->delivery_longitude,
                    ),
                    'driver' => $this->coordinates($delivery->current_latitude, $delivery->current_longitude),
                    'last_updated_at' => $delivery->updated_at?->toIso8601String(),
                ] : null,
            ],
        ]);
    }

    private function coordinates(mixed $latitude, mixed $longitude): ?array
    {
        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            return null;
        }

        $latitude = (float) $latitude;
        $longitude = (float) $longitude;
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180
            || ($latitude === 0.0 && $longitude === 0.0)) {
            return null;
        }

        return ['latitude' => $latitude, 'longitude' => $longitude];
    }
}
