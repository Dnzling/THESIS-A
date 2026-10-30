<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\Ecommerce\EcommerceController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class EcommercePaymentResumeGuardTest extends TestCase
{
    #[DataProvider('snapshots')]
    public function test_only_the_same_reserved_cart_items_resume_an_order(array $snapshot, array $cartItemIds, bool $expected): void
    {
        $reflection = new ReflectionClass(EcommerceController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('paymentSnapshotMatchesCartItems');

        $this->assertSame($expected, $method->invoke($controller, $snapshot, $cartItemIds));
    }

    public static function snapshots(): array
    {
        return [
            'same items regardless of order' => [
                ['stock_reserved' => true, 'items' => [['cart_item_id' => 9], ['cart_item_id' => 3]]],
                [3, 9],
                true,
            ],
            'different selection' => [
                ['stock_reserved' => true, 'items' => [['cart_item_id' => 3], ['cart_item_id' => 9]]],
                [3, 10],
                false,
            ],
            'released reservation' => [
                ['stock_reserved' => false, 'items' => [['cart_item_id' => 3]]],
                [3],
                false,
            ],
            'missing snapshot items' => [
                ['stock_reserved' => true],
                [3],
                false,
            ],
        ];
    }
}
