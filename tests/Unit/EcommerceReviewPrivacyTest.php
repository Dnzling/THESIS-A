<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\Ecommerce\EcommerceController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class EcommerceReviewPrivacyTest extends TestCase
{
    #[DataProvider('reviewerNames')]
    public function test_review_customer_surname_is_masked(?string $firstName, ?string $lastName, string $expected): void
    {
        $reflection = new ReflectionClass(EcommerceController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('maskedReviewCustomerName');

        $this->assertSame($expected, $method->invoke($controller, $firstName, $lastName));
    }

    public static function reviewerNames(): array
    {
        return [
            'full name' => ['Juan', 'Dela Cruz', 'Juan D***'],
            'trimmed name' => ['  Maria  ', ' santos ', 'Maria S***'],
            'missing surname' => ['Ana', null, 'Ana'],
            'missing first name' => [null, 'Reyes', 'Customer'],
            'missing name' => [null, null, 'Customer'],
        ];
    }
}
