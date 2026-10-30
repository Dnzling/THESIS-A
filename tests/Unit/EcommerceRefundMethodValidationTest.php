<?php

use App\Http\Controllers\Api\Ecommerce\EcommerceController;
use App\Models\Core\User;
use App\Models\CRM\EcommerceOrderReturn;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class);

beforeEach(function () {
    config()->set('database.default', 'refund_validation_no_database');
});

it('rejects bank transfer for a cancelled ecommerce order before accessing the database', function () {
    $request = Request::create('/api/ecommerce/orders/1/refund-payment-method', 'PUT', [
        'refund_method' => 'bank_transfer',
        'refund_account_name' => 'Customer Name',
        'refund_account_number' => '09123456789',
    ]);

    expect(fn () => app(EcommerceController::class)->updateCancellationRefundPaymentMethod($request, 1))
        ->toThrow(ValidationException::class);
});

it('rejects an invalid mobile number for a return refund before accessing the database', function () {
    $user = new User();
    $user->id = 1;
    $request = Request::create('/api/ecommerce/returns/1/refund-payment-method', 'PUT', [
        'refund_method' => 'maya',
        'refund_account_name' => 'Customer Name',
        'refund_account_number' => '12345',
    ]);
    $request->setUserResolver(fn () => $user);
    $return = new EcommerceOrderReturn(['user_id' => 1]);

    expect(fn () => app(EcommerceController::class)->updateRefundPaymentMethod($request, $return))
        ->toThrow(ValidationException::class);
});

it('rejects a full card number for a cancelled order refund before accessing the database', function () {
    $request = Request::create('/api/ecommerce/orders/1/refund-payment-method', 'PUT', [
        'refund_method' => 'card',
        'refund_account_name' => 'Customer Name',
        'refund_account_number' => '4111 1111 1111 1111',
    ]);

    expect(fn () => app(EcommerceController::class)->updateCancellationRefundPaymentMethod($request, 1))
        ->toThrow(ValidationException::class);
});

it('requires a cardholder name alongside the last four digits for a return refund', function () {
    $user = new User();
    $user->id = 1;
    $request = Request::create('/api/ecommerce/returns/1/refund-payment-method', 'PUT', [
        'refund_method' => 'card',
        'refund_account_number' => '1234',
    ]);
    $request->setUserResolver(fn () => $user);
    $return = new EcommerceOrderReturn(['user_id' => 1]);

    expect(fn () => app(EcommerceController::class)->updateRefundPaymentMethod($request, $return))
        ->toThrow(ValidationException::class);
});
