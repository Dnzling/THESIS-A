<?php

namespace Tests\Unit;

use App\Models\Store\Store;
use App\Services\Store\ReturnResolutionPolicy;
use PHPUnit\Framework\TestCase;

class ReturnResolutionPolicyTest extends TestCase
{
    public function test_it_provides_compatibility_safe_defaults(): void
    {
        $policy = (new ReturnResolutionPolicy())->forStore(new Store());

        $this->assertTrue($policy['allow_refunds']);
        $this->assertSame('replacement', $policy['default_resolution']);
        $this->assertSame(['gcash', 'maya'], $policy['refund_methods']);
        $this->assertSame(7, $policy['return_window_days']);
    }

    public function test_it_forces_replacement_as_default_when_refunds_are_disabled(): void
    {
        $store = new Store();
        $store->settings = ['return_resolution' => [
            'allow_refunds' => false,
            'default_resolution' => 'refund',
            'refund_methods' => ['bank_transfer', 'unsupported'],
            'return_window_days' => 90,
            'allow_higher_value_replacements' => false,
        ]];

        $policy = (new ReturnResolutionPolicy())->forStore($store);

        $this->assertFalse($policy['allow_refunds']);
        $this->assertSame('replacement', $policy['default_resolution']);
        $this->assertSame(['bank_transfer'], $policy['refund_methods']);
        $this->assertSame(30, $policy['return_window_days']);
        $this->assertFalse($policy['allow_higher_value_replacements']);
    }
}
