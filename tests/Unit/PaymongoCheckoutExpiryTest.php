<?php

namespace Tests\Unit;

use App\Services\Payment\PaymongoService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class PaymongoCheckoutExpiryTest extends TestCase
{
    public function test_expire_checkout_session_posts_to_the_existing_session(): void
    {
        $history = [];
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode(['data' => ['attributes' => ['status' => 'expired']]])),
        ]));
        $handler->push(Middleware::history($history));

        $reflection = new \ReflectionClass(PaymongoService::class);
        $service = $reflection->newInstanceWithoutConstructor();
        $reflection->getProperty('client')->setValue($service, new Client([
            'base_uri' => 'https://api.paymongo.com/v1/',
            'handler' => $handler,
        ]));

        $result = $service->expireCheckoutSession('cs_test123');

        $this->assertSame('expired', $result['data']['attributes']['status']);
        $this->assertSame('POST', $history[0]['request']->getMethod());
        $this->assertSame('/v1/checkout_sessions/cs_test123/expire', $history[0]['request']->getUri()->getPath());
    }
}
