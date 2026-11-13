<?php

use App\Http\Controllers\Api\CRM\ReturnController;
use App\Http\Controllers\Api\Ecommerce\EcommerceController;
use App\Http\Controllers\Api\Logistics\ReplacementController;
use App\Http\Controllers\Api\Logistics\ReturnPickupController;
use App\Http\Controllers\Api\Logistics\DeliveryTripController;

uses(Tests\TestCase::class);

it('registers the customer return and replacement workflow without accessing a database', function () {
    config()->set('database.default', 'replacement_routes_no_database');

    $expectedRoutes = [
        ['POST', 'api/ecommerce/order-items/{itemId}/return-requests', EcommerceController::class . '@requestOrderReturn'],
        ['PUT', 'api/crm/returns/{return}/status', ReturnController::class . '@updateStatus'],
        ['POST', 'api/crm/returns/{return}/pickup', ReturnController::class . '@createPickup'],
        ['POST', 'api/inventory/returns/customer/{return}/inspect', ReturnController::class . '@receive'],
        ['POST', 'api/warehouse/returns/{return}/inspect', ReturnController::class . '@receive'],
        ['POST', 'api/logistics/return-pickups/{pickup}/assign-driver', ReturnPickupController::class . '@assignDriver'],
        ['POST', 'api/logistics/return-pickups/{pickup}/proof', ReturnPickupController::class . '@uploadProof'],
        ['POST', 'api/logistics/replacements/{id}/reserve', ReplacementController::class . '@reserve'],
        ['POST', 'api/logistics/replacements/{id}/deliver', ReplacementController::class . '@deliver'],
        ['GET', 'api/logistics/delivery-orders/{source}/{orderId}', \App\Http\Controllers\Api\Logistics\UnifiedDeliveryController::class . '@orderDetail'],
        ['POST', 'api/logistics/delivery-orders/{source}/{orderId}/location', \App\Http\Controllers\Api\Logistics\UnifiedDeliveryController::class . '@updateLocation'],
        ['POST', 'api/logistics/replacements/{id}/fail', ReplacementController::class . '@fail'],
        ['POST', 'api/logistics/replacements/{id}/reattempt', ReplacementController::class . '@reattempt'],
        ['POST', 'api/logistics/replacements/{id}/disposition', ReplacementController::class . '@disposition'],
        ['GET', 'api/logistics/trips/compatible', DeliveryTripController::class . '@compatibleForOrder'],
        ['POST', 'api/logistics/trips/{id}/orders', DeliveryTripController::class . '@addOrders'],
    ];

    $routes = collect(app('router')->getRoutes()->getRoutes());

    foreach ($expectedRoutes as [$method, $uri, $action]) {
        $route = $routes->first(
            fn ($candidate) => $candidate->uri() === $uri && in_array($method, $candidate->methods(), true)
        );

        expect($route)->not->toBeNull()
            ->and($route->getActionName())->toBe($action);
    }
});

