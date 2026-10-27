<?php

use App\Http\Controllers\Api\Ecommerce\EcommerceController;
use App\Http\Controllers\Api\Finance\FinanceRefundController;
use App\Http\Controllers\Api\Sales\SalesRefundController;

uses(Tests\TestCase::class);

it('registers customer, sales, and finance refund actions without accessing the database', function () {
    config()->set('database.default', 'refund_routes_no_database');

    $expected = [
        ['PUT', 'api/ecommerce/orders/{id}/refund-payment-method', EcommerceController::class . '@updateCancellationRefundPaymentMethod'],
        ['POST', 'api/sales/refunds', SalesRefundController::class . '@store'],
        ['PUT', 'api/finance/refunds/{refund}/status', FinanceRefundController::class . '@updateStatus'],
        ['POST', 'api/finance/refunds/{refund}/send', FinanceRefundController::class . '@markSent'],
        ['POST', 'api/finance/refunds/{refund}/test-wallet-transfer', FinanceRefundController::class . '@startTestWalletTransfer'],
        ['POST', 'api/finance/refunds/{refund}/test-wallet-transfer/check', FinanceRefundController::class . '@refreshTestWalletTransfer'],
    ];

    foreach ($expected as [$method, $uri, $action]) {
        $route = collect(app('router')->getRoutes()->getRoutes())->first(
            fn ($candidate) => $candidate->uri() === $uri && in_array($method, $candidate->methods(), true)
        );

        expect($route)->not->toBeNull()
            ->and($route->getActionName())->toBe($action);
    }
});
