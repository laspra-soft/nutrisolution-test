<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\Discount;
use App\Domain\ValueObject\DiscountType;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Percentage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DiscountTest extends TestCase
{
    #[Test]
    public function it_creates_percentage_discount(): void
    {
        $discount = new Discount(
            code: 'SAVE10',
            value: new Percentage(10.0),
        );

        self::assertSame('SAVE10', $discount->code);
        self::assertSame(DiscountType::Percentage, $discount->type());
        self::assertInstanceOf(Percentage::class, $discount->value);
        self::assertSame(10.0, $discount->value->value);
        self::assertNull($discount->maxCap);
    }

    #[Test]
    public function it_creates_fixed_discount(): void
    {
        $discount = new Discount(
            code: 'FLAT500',
            value: new Money(500, Currency::Euro),
        );

        self::assertSame('FLAT500', $discount->code);
        self::assertSame(DiscountType::Fixed, $discount->type());
        self::assertInstanceOf(Money::class, $discount->value);
        self::assertSame(500, $discount->value->value);
    }

    #[Test]
    public function it_creates_capped_percentage_discount(): void
    {
        $discount = new Discount(
            code: 'WELCOME20',
            value: new Percentage(20.0),
            maxCap: new Money(1000, Currency::Euro),
        );

        self::assertSame(1000, $discount->maxCap?->value);
    }

    #[Test]
    public function it_calculates_percentage_discount_amount(): void
    {
        $discount = new Discount(
            code: 'SAVE10',
            value: new Percentage(10.0),
        );

        $subtotal = new Money(10000, Currency::Euro);
        $amount   = $discount->calculateAmount($subtotal);

        self::assertSame(1000, $amount->value);
    }

    #[Test]
    public function it_calculates_fixed_discount_amount(): void
    {
        $discount = new Discount(
            code: 'FLAT500',
            value: new Money(500, Currency::Euro),
        );

        $subtotal = new Money(10000, Currency::Euro);
        $amount   = $discount->calculateAmount($subtotal);

        self::assertSame(500, $amount->value);
    }

    #[Test]
    public function it_caps_percentage_discount(): void
    {
        $discount = new Discount(
            code: 'WELCOME20',
            value: new Percentage(20.0),
            maxCap: new Money(1000, Currency::Euro),
        );

        // 20% of 100000 = 20000, but capped at 1000
        $subtotal = new Money(100000, Currency::Euro);
        $amount   = $discount->calculateAmount($subtotal);

        self::assertSame(1000, $amount->value);
    }

    #[Test]
    public function it_does_not_cap_percentage_when_below_max(): void
    {
        $discount = new Discount(
            code: 'WELCOME20',
            value: new Percentage(20.0),
            maxCap: new Money(1000, Currency::Euro),
        );

        // 20% of 4000 = 800, below cap of 1000
        $subtotal = new Money(4000, Currency::Euro);
        $amount   = $discount->calculateAmount($subtotal);

        self::assertSame(800, $amount->value);
    }

    #[Test]
    public function it_caps_fixed_discount_at_subtotal(): void
    {
        $discount = new Discount(
            code: 'FLAT500',
            value: new Money(500, Currency::Euro),
        );

        // 500 fixed on 200 subtotal = capped at 200
        $subtotal = new Money(200, Currency::Euro);
        $amount   = $discount->calculateAmount($subtotal);

        self::assertSame(200, $amount->value);
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $discount = new Discount(
            code: 'SAVE10',
            value: new Percentage(10.0),
        );

        $subtotal = new Money(10000, Currency::Euro);
        $array    = $discount->toArray($subtotal);

        self::assertSame([
            'code' => 'SAVE10',
            'type' => 'percentage',
            'value' => 10.0,
            'amount' => 1000,
        ], $array);
    }

    #[Test]
    public function it_rounds_percentage_discount_correctly(): void
    {
        $discount = new Discount(
            code: 'SAVE10',
            value: new Percentage(10.0),
        );

        // 10% of 10997 = 1099.7 -> rounds to 1100
        $subtotal = new Money(10997, Currency::Euro);
        $amount   = $discount->calculateAmount($subtotal);

        self::assertSame(1100, $amount->value);
    }

    #[Test]
    public function it_converts_fixed_discount_to_array(): void
    {
        $discount = new Discount(
            code: 'FLAT500',
            value: new Money(500, Currency::Euro),
        );

        $subtotal = new Money(10000, Currency::Euro);
        $array    = $discount->toArray($subtotal);

        self::assertSame([
            'code' => 'FLAT500',
            'type' => 'fixed',
            'value' => 500,
            'amount' => 500,
        ], $array);
    }
}
