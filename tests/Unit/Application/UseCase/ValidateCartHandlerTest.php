<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase;

use App\Application\DTO\CartValidateRequest;
use App\Application\Service\TaxService;
use App\Application\UseCase\ValidateCartHandler;
use App\Domain\Entity\CartItem;
use App\Domain\Service\CartCalculator;
use App\Domain\ValueObject\CountryCode;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Sku;
use App\Infrastructure\Repository\DiscountRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ValidateCartHandlerTest extends TestCase
{
    private ValidateCartHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new ValidateCartHandler(
            new CartCalculator(),
            new DiscountRepository(),
            new TaxService(),
        );
    }

    #[Test]
    public function it_handles_complete_cart_validation(): void
    {
        $request = new CartValidateRequest(
            items: [
                new CartItem(
                    new Sku('PROD-001'),
                    'Premium Widget',
                    2,
                    new Money(2999, Currency::Euro),
                ),
                new CartItem(
                    new Sku('PROD-002'),
                    'Basic Gadget',
                    1,
                    new Money(4999, Currency::Euro),
                ),
            ],
            countryCode: CountryCode::France,
            taxesIncluded: true,
            discountCode: 'SAVE10',
        );

        $result = $this->handler->handle($request);

        self::assertTrue($result->success);
        self::assertSame(Currency::Euro, $result->currency);
        self::assertNotNull($result->calculation);
        self::assertSame(10997, $result->calculation->subtotal->value);
        self::assertSame(1100, $result->calculation->discountAmount->value);
    }

    #[Test]
    public function it_handles_cart_without_discount(): void
    {
        $request = new CartValidateRequest(
            items: [
                new CartItem(
                    new Sku('PROD-001'),
                    'Widget',
                    1,
                    new Money(10000, Currency::Euro),
                ),
            ],
            countryCode: CountryCode::Germany,
            taxesIncluded: false,
            discountCode: null,
        );

        $result = $this->handler->handle($request);

        self::assertTrue($result->success);
        self::assertNotNull($result->calculation);
        self::assertSame(10000, $result->calculation->subtotal->value);
        self::assertSame(1900, $result->calculation->taxAmount->value);
        self::assertSame(11900, $result->calculation->total->value);
    }

    #[Test]
    public function it_returns_error_for_invalid_discount(): void
    {
        $request = new CartValidateRequest(
            items: [
                new CartItem(
                    new Sku('PROD-001'),
                    'Widget',
                    1,
                    new Money(10000, Currency::Euro),
                ),
            ],
            countryCode: CountryCode::France,
            taxesIncluded: true,
            discountCode: 'INVALID',
        );

        $result = $this->handler->handle($request);

        self::assertFalse($result->success);
        self::assertSame('INVALID_DISCOUNT_CODE', $result->errorCode);
        self::assertSame("The discount code 'INVALID' is not valid", $result->errorMessage);
    }

    #[Test]
    public function it_outputs_correct_array_format(): void
    {
        $request = new CartValidateRequest(
            items: [
                new CartItem(
                    new Sku('PROD-001'),
                    'Premium Widget',
                    2,
                    new Money(2999, Currency::Euro),
                ),
            ],
            countryCode: CountryCode::France,
            taxesIncluded: true,
            discountCode: 'SAVE10',
        );

        $result = $this->handler->handle($request);
        $array  = $result->toArray();

        self::assertTrue($array['success']);
        self::assertSame('EUR', $array['currency']);
        self::assertArrayHasKey('cart', $array);
        self::assertIsArray($array['cart']);
        self::assertArrayHasKey('items', $array['cart']);
        self::assertArrayHasKey('subtotal', $array['cart']);
        self::assertArrayHasKey('discount', $array['cart']);
        self::assertArrayHasKey('tax', $array['cart']);
        self::assertArrayHasKey('total', $array['cart']);
    }

    #[Test]
    public function it_outputs_error_format(): void
    {
        $request = new CartValidateRequest(
            items: [
                new CartItem(
                    new Sku('PROD-001'),
                    'Widget',
                    1,
                    new Money(10000, Currency::Euro),
                ),
            ],
            countryCode: CountryCode::France,
            taxesIncluded: true,
            discountCode: 'BADCODE',
        );

        $result = $this->handler->handle($request);
        $array  = $result->toArray();

        self::assertFalse($array['success']);
        self::assertArrayHasKey('error', $array);
        self::assertIsArray($array['error']);
        self::assertSame('INVALID_DISCOUNT_CODE', $array['error']['code']);
    }
}
