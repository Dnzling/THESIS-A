<?php

use App\Models\Finance\FinanceRefund;
use App\Services\Payment\PaymongoTestWalletTransferService;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

it('sends a test-only wallet transfer to the documented sandbox account', function () {
    config()->set('paymongo.secret', 'sk_test_example');
    config()->set('paymongo.endpoint', 'https://api.paymongo.com/v1');
    config()->set('paymongo.test_wallet_account_number', '0000000001');
    config()->set('paymongo.test_wallet_account_name', 'Test Store');

    Http::fake([
        'api.paymongo.com/v1/wallets/receiving_institutions*' => Http::response([
            'data' => [['name' => 'GCash', 'provider_code' => 'GXCHPHM2XXX']],
        ]),
        'api.paymongo.com/v2/batch_transfers' => Http::response([
            'data' => ['id' => 'btr_test', 'transfers' => [['id' => 'tr_test', 'status' => 'pending']]],
        ], 201),
    ]);

    $refund = new FinanceRefund(['refund_method' => 'gcash', 'amount' => 250]);
    $refund->id = 42;
    $result = app(PaymongoTestWalletTransferService::class)->create($refund);

    expect($result)->toBe(['transfer_id' => 'tr_test', 'batch_id' => 'btr_test', 'status' => 'pending']);
    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/v2/batch_transfers')
        && $request->hasHeader('Idempotency-Key', 'furnisync-refund-42')
        && data_get($request->data(), 'transfers.0.destination_account.number') === '999999990001'
        && data_get($request->data(), 'transfers.0.amount') === 25000);
});

it('does not allow a live key to create a test transfer', function () {
    config()->set('paymongo.secret', 'sk_live_example');
    config()->set('paymongo.test_wallet_account_number', '0000000001');
    config()->set('paymongo.test_wallet_account_name', 'Test Store');
    Http::fake();

    expect(app(PaymongoTestWalletTransferService::class)->isAvailable())->toBeFalse();
    Http::assertNothingSent();
});

it('reads the provider status and rejects a mismatched transfer ID', function () {
    config()->set('paymongo.secret', 'sk_test_example');
    config()->set('paymongo.endpoint', 'https://api.paymongo.com/v1');
    Http::fake(['api.paymongo.com/v2/transfers/tr_test' => Http::response([
        'data' => ['id' => 'tr_test', 'status' => 'succeeded', 'provider_reference_number' => 'provider-123'],
    ])]);

    expect(app(PaymongoTestWalletTransferService::class)->retrieve('tr_test'))
        ->toBe(['status' => 'succeeded', 'reference' => 'provider-123', 'error' => '']);
});
