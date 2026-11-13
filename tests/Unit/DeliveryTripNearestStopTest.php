<?php

use App\Http\Controllers\Api\Logistics\DeliveryTripController;
use App\Models\Ecommerce\EcommerceDeliveryVehicle;
use App\Models\Logistics\DeliveryTrip;
use App\Models\Store\Branch;

it('suggests the nearest first stop and chains following stops without city grouping', function () {
    $controller = (new ReflectionClass(DeliveryTripController::class))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod(DeliveryTripController::class, 'orderByNearestNextStop');
    $vehicle = new EcommerceDeliveryVehicle();
    $vehicle->setRelation('branch', new Branch(['latitude' => 14.0, 'longitude' => 121.0]));
    $trip = new DeliveryTrip();
    $trip->setRelation('vehicle', $vehicle);

    $rows = [
        ['order_number' => 'FAR', 'area_key' => 'other city', 'origin_latitude' => 14.0, 'origin_longitude' => 121.0, 'destination_latitude' => 14.0, 'destination_longitude' => 121.04, 'distance_km' => 4.3],
        ['order_number' => 'FIRST', 'area_key' => 'city one', 'origin_latitude' => 14.0, 'origin_longitude' => 121.0, 'destination_latitude' => 14.0, 'destination_longitude' => 121.01, 'distance_km' => 1.1],
        ['order_number' => 'NEXT', 'area_key' => 'city two', 'origin_latitude' => 14.0, 'origin_longitude' => 121.0, 'destination_latitude' => 14.0, 'destination_longitude' => 121.011, 'distance_km' => 1.2],
    ];

    $ordered = $method->invoke($controller, $rows, $trip);

    expect(array_column($ordered, 'order_number'))->toBe(['FIRST', 'NEXT', 'FAR'])
        ->and($ordered[1]['distance_km'])->toBeLessThan(1.0)
        ->and($ordered)->toHaveCount(3)
        ->and($ordered[0])->not->toHaveKey('destination_latitude');
});
