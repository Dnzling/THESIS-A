<?php

namespace App\Services\Payment;

use App\Models\Finance\FinanceRefund;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaymongoTestWalletTransferService
{
    public function isAvailable(): bool
    {
        return str_starts_with((string) config('paymongo.secret'), 'sk_test_')
            && trim((string) config('paymongo.test_wallet_account_number')) !== ''
            && trim((string) config('paymongo.test_wallet_account_name')) !== '';
    }

    public function create(FinanceRefund $refund): array
    {
        if (!$this->isAvailable()) {
            throw new RuntimeException('Configure a PayMongo key and Wallet source account before simulating transfers.');
        }

        $bic = $this->destinationBic((string) $refund->refund_method);
        $response = $this->client()->withHeaders(['Idempotency-Key' => 'furnisync-refund-' . $refund->id])
            ->post($this->v2Url('batch_transfers'), ['transfers' => [[
                'provider' => 'instapay',
                'amount' => (int) round((float) $refund->amount * 100),
                'currency' => 'PHP',
                'purpose' => 'Simulated customer refund',
                'description' => 'FurniSync simulated refund ' . $refund->id,
                'reference_number' => 'FURNISYNC-REFUND-' . $refund->id,
                'source_account' => [
                    'number' => (string) config('paymongo.test_wallet_account_number'),
                    'name' => (string) config('paymongo.test_wallet_account_name'),
                    'bic' => 'PAEYPHM2XXX',
                ],
                'destination_account' => [
                    'number' => '999999990001',
                    'name' => 'FurniSync Simulation Recipient',
                    'bic' => $bic,
                ],
            ]]]);

        if ($response->failed()) {
            throw new RuntimeException((string) ($response->json('errors.0.detail') ?: 'PayMongo rejected the simulated transfer.'));
        }

        $transferId = (string) $response->json('data.transfers.0.id', '');
        if ($transferId === '') {
            throw new RuntimeException('PayMongo did not return a transfer ID. Check its dashboard before retrying.');
        }

        return [
            'transfer_id' => $transferId,
            'batch_id' => (string) $response->json('data.id', ''),
            'status' => (string) $response->json('data.transfers.0.status', 'pending'),
        ];
    }

    public function retrieve(string $transferId): array
    {
        if (!str_starts_with((string) config('paymongo.secret'), 'sk_test_')) {
            throw new RuntimeException('A PayMongo key for the simulation is required to check this transfer.');
        }
        $response = $this->client()->get($this->v2Url('transfers/' . rawurlencode($transferId)));
        if ($response->failed()) {
            throw new RuntimeException((string) ($response->json('errors.0.detail') ?: 'Unable to check the PayMongo transfer.'));
        }

        $id = (string) $response->json('data.id', '');
        if ($id !== $transferId) {
            throw new RuntimeException('PayMongo returned an unexpected transfer.');
        }

        return [
            'status' => (string) ($response->json('data.status') ?: $response->json('data.attributes.status') ?: ''),
            'reference' => (string) ($response->json('data.provider_reference_number')
                ?: $response->json('data.attributes.provider_reference_number') ?: $transferId),
            'error' => (string) ($response->json('data.failure_reason')
                ?: $response->json('data.attributes.provider_error') ?: ''),
        ];
    }

    private function destinationBic(string $method): string
    {
        $response = $this->client()->get(rtrim((string) config('paymongo.endpoint'), '/') . '/wallets/receiving_institutions', [
            'provider' => 'instapay',
        ]);
        if ($response->failed()) {
            throw new RuntimeException('Unable to load PayMongo receiving institutions.');
        }

        $names = $method === 'gcash' ? ['gcash', 'g-xchange'] : ['maya philippines', 'paymaya', 'maya wallet'];
        foreach ((array) $response->json('data', []) as $institution) {
            $name = strtolower((string) (data_get($institution, 'attributes.name')
                ?: data_get($institution, 'attributes.bank_name')
                ?: data_get($institution, 'name') ?: data_get($institution, 'bank_name')));
            $bic = (string) (data_get($institution, 'attributes.provider_code')
                ?: data_get($institution, 'attributes.bic')
                ?: data_get($institution, 'provider_code') ?: data_get($institution, 'bic'));
            if ($bic !== '' && collect($names)->contains(fn (string $candidate) => str_contains($name, $candidate))) {
                return $bic;
            }
        }

        throw new RuntimeException('PayMongo did not list a matching ' . strtoupper($method) . ' receiving institution.');
    }

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withBasicAuth((string) config('paymongo.secret'), '')
            ->acceptJson()->timeout(15);
    }

    private function v2Url(string $path): string
    {
        return preg_replace('#/v1/?$#', '', rtrim((string) config('paymongo.endpoint'), '/')) . '/v2/' . $path;
    }
}
