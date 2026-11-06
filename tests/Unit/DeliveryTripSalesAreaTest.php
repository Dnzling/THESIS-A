<?php

use App\Http\Controllers\Api\Logistics\DeliveryTripController;
use App\Models\Sales\SalesOrder;

it('groups sales orders by their recorded province and city regardless of address ordering', function () {
    $controller = (new ReflectionClass(DeliveryTripController::class))->newInstanceWithoutConstructor();
    $areaKey = new ReflectionMethod(DeliveryTripController::class, 'salesAreaKey');

    $pos = new SalesOrder([
        'delivery_province' => 'Cavite',
        'delivery_city' => 'Magallanes',
        'delivery_address' => 'Cavite, Magallanes, Pacheco, Block 28 Lot 30 R5',
    ]);
    $wholesale = new SalesOrder([
        'delivery_province' => 'Cavite',
        'delivery_city' => 'Magallanes',
        'delivery_address' => 'Block 28 Lot 30 R5, Tua, Magallanes, Cavite',
    ]);

    expect($areaKey->invoke($controller, $pos))->toBe('cavite, magallanes')
        ->and($areaKey->invoke($controller, $wholesale))->toBe('cavite, magallanes');
});

it('uses the trailing city and province for older detailed sales addresses', function () {
    $controller = (new ReflectionClass(DeliveryTripController::class))->newInstanceWithoutConstructor();
    $areaKey = new ReflectionMethod(DeliveryTripController::class, 'salesAreaKey');

    $order = new SalesOrder(['delivery_address' => 'Block 28 Lot 30, Tua, Magallanes, Cavite']);

    expect($areaKey->invoke($controller, $order))->toBe('cavite, magallanes');
});
