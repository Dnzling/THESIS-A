<?php

namespace Tests\Unit;

use App\Services\Logistics\LalamoveClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LalamoveClientTest extends TestCase
{
    public function test_sandbox_requests_use_the_lalamove_v3_hmac_signature(): void
    {
        config([
            'services.lalamove.base_url' => 'https://rest.sandbox.lalamove.com',
            'services.lalamove.api_key' => 'pk_test_example',
            'services.lalamove.api_secret' => 'sk_test_secret',
            'services.lalamove.market' => 'PH',
        ]);

        Http::fake([
            'https://rest.sandbox.lalamove.com/v3/quotations' => Http::response(['data' => ['quotationId' => 'sandbox-quote']], 201),
        ]);

        $payload = ['serviceType' => 'MOTORCYCLE', 'stops' => []];
        $result = app(LalamoveClient::class)->quotation($payload);

        $this->assertSame('sandbox-quote', $result['quotationId']);

        Http::assertSent(function ($request) use ($payload): bool {
            $auth = (string) ($request->header('Authorization')[0] ?? '');
            $parts = explode(':', substr($auth, strlen('hmac ')));
            if (count($parts) !== 3) return false;

            [$key, $timestamp, $signature] = $parts;
            $body = json_encode(['data' => $payload], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $raw = $timestamp . "\r\nPOST\r\n/v3/quotations\r\n\r\n" . $body;

            return $request->method() === 'POST'
                && $request->url() === 'https://rest.sandbox.lalamove.com/v3/quotations'
                && $key === 'pk_test_example'
                && ($request->header('Market')[0] ?? null) === 'PH'
                && hash_equals(hash_hmac('sha256', $raw, 'sk_test_secret'), $signature)
                && $request->data() === ['data' => $payload];
        });
    }
}
