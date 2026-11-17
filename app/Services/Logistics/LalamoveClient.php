<?php

namespace App\Services\Logistics;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class LalamoveClient
{
    public function isConfigured(): bool
    {
        return filled(config('services.lalamove.api_key')) && filled(config('services.lalamove.api_secret'));
    }

    public function cities(): array
    {
        return $this->request('GET', '/v3/cities')['data'] ?? [];
    }

    public function quotation(array $payload): array
    {
        return $this->request('POST', '/v3/quotations', ['data' => $payload])['data'] ?? [];
    }

    public function createOrder(array $payload): array
    {
        return $this->request('POST', '/v3/orders', ['data' => $payload])['data'] ?? [];
    }

    public function order(string $orderId): array
    {
        return $this->request('GET', '/v3/orders/' . rawurlencode($orderId))['data'] ?? [];
    }

    private function request(string $method, string $path, ?array $payload = null): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Lalamove sandbox credentials are not configured. Set LALAMOVE_API_KEY and LALAMOVE_API_SECRET in the server environment.');
        }

        $method = strtoupper($method);
        $timestamp = (string) ((int) floor(microtime(true) * 1000));
        $body = $payload === null ? '' : json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $raw = $timestamp . "\r\n" . $method . "\r\n" . $path . "\r\n\r\n" . $body;
        $signature = hash_hmac('sha256', $raw, (string) config('services.lalamove.api_secret'));
        $key = (string) config('services.lalamove.api_key');

        $request = Http::baseUrl(rtrim((string) config('services.lalamove.base_url'), '/'))
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.lalamove.timeout', 20))
            ->withHeaders([
                'Authorization' => "hmac {$key}:{$timestamp}:{$signature}",
                'Market' => (string) config('services.lalamove.market', 'PH'),
                'Request-ID' => (string) Str::uuid(),
            ]);

        $response = $payload === null
            ? $request->get($path)
            : $request->withBody($body, 'application/json')->send($method, $path);

        return $this->decode($response);
    }

    private function decode(Response $response): array
    {
        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $errors = collect($response->json('errors', []))
            ->map(fn ($error) => $error['detail'] ?? $error['message'] ?? null)
            ->filter()
            ->take(2)
            ->implode(' ');
        $message = $errors ?: (string) ($response->json('message') ?: 'Lalamove could not process the request.');

        throw new RuntimeException("Lalamove API ({$response->status()}): {$message}");
    }
}
