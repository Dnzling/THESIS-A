<?php

use App\Http\Controllers\Api\Finance\FinanceRefundController;
use App\Models\Finance\FinanceRefund;
use App\Services\Finance\CashflowService;
use App\Services\Payment\PaymongoService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class);

it('rejects manual payout submissions from the finance refund endpoint', function (array $payload) {
    $user = (object) ['store_id' => 7];
    $request = Request::create('/api/finance/refunds/12/send', 'POST', $payload);
    $request->setUserResolver(fn () => $user);
    $refund = new FinanceRefund(['id' => 12, 'store_id' => 7]);

    expect(fn () => app(FinanceRefundController::class)->markSent(
        $request,
        $refund,
        Mockery::mock(CashflowService::class),
        Mockery::mock(PaymongoService::class),
    ))->toThrow(ValidationException::class);
})->with([
    'manual provider' => [['provider' => 'manual', 'payout_reference' => 'BANK-REF-123']],
    'missing provider' => [[]],
]);
