<?php

use App\Http\Controllers\Api\Finance\FinanceRefundController;
use App\Models\Finance\FinanceRefund;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class);

it('permits manual payout for an ecommerce Maya refund outside test mode', function () {
    config()->set('paymongo.secret', 'sk_live_example');
    $refund = new FinanceRefund(['order_type' => 'ecommerce_return', 'refund_method' => 'maya']);
    $validate = new ReflectionMethod(FinanceRefundController::class, 'validatePayoutProvider');

    expect(fn () => $validate->invoke(new FinanceRefundController(), $refund, 'paymongo'))
        ->toThrow(ValidationException::class);
    expect($validate->invoke(new FinanceRefundController(), $refund, 'manual'))->toBeNull();
});

it('allows a confirmed manual wallet payout when original-method refund is unavailable', function (string $method) {
    config()->set('paymongo.secret', 'sk_test_example');
    $refund = new FinanceRefund(['order_type' => 'ecommerce_order', 'refund_method' => $method, 'status' => 'approved']);
    $validate = new ReflectionMethod(FinanceRefundController::class, 'validatePayoutProvider');

    expect($validate->invoke(new FinanceRefundController(), $refund, 'manual'))->toBeNull();
    expect(fn () => $validate->invoke(new FinanceRefundController(), $refund, 'simulation'))
        ->toThrow(ValidationException::class);
})->with(['gcash', 'maya']);

it('keeps original-card refunds off the manual payout path', function () {
    $refund = new FinanceRefund(['order_type' => 'ecommerce_order', 'refund_method' => 'card']);
    $validate = new ReflectionMethod(FinanceRefundController::class, 'validatePayoutProvider');

    expect(fn () => $validate->invoke(new FinanceRefundController(), $refund, 'manual'))
        ->toThrow(ValidationException::class);
    expect(fn () => $validate->invoke(new FinanceRefundController(), $refund, 'simulation'))
        ->toThrow(ValidationException::class);
});
