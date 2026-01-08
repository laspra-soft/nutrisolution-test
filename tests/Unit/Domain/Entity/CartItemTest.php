<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\CartItem;
use App\Domain\Exception\InvalidCartException;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Sku;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CartItemTest extends TestCase
{
    #[Test]
    public function it_creates_valid_cart_item(): void
    {
        $item = new CartItem(
            new Sku('PROD-001'),
            'Premium Widget',
            2,
            new Money(2999, Currency::Euro),
        );

        self::assertSame('PROD-001', $item->sku->value);
        self::assertSame('Premium Widget', $item->name);
        self::assertSame(2, $item->quantity);
        self::assertSame(2999, $item->unitPrice->value);
    }

    #[Test]
    public function it_calculates_line_total(): void
    {
        $item = new CartItem(
            new Sku('PROD-001'),
            'Widget',
            3,
            new Money(2999, Currency::Euro),
        );

        self::assertSame(8997, $item->lineTotal()->value);
    }

    #[Test]
    public function it_calculates_line_total_for_single_quantity(): void
    {
        $item = new CartItem(
            new Sku('PROD-001'),
            'Widget',
            1,
            new Money(5000, Currency::Euro),
        );

        self::assertSame(5000, $item->lineTotal()->value);
    }

    #[Test]
    public function it_throws_exception_for_zero_quantity(): void
    {
        $this->expectException(InvalidCartException::class);
        $this->expectExceptionMessage('Quantity must be positive, got: 0');

        new CartItem(
            new Sku('PROD-001'),
            'Widget',
            0,
            new Money(2999, Currency::Euro),
        );
    }

    #[Test]
    public function it_throws_exception_for_negative_quantity(): void
    {
        $this->expectException(InvalidCartException::class);
        $this->expectExceptionMessage('Quantity must be positive, got: -1');

        new CartItem(
            new Sku('PROD-001'),
            'Widget',
            -1,
            new Money(2999, Currency::Euro),
        );
    }

    #[Test]
    public function it_throws_exception_for_empty_name(): void
    {
        $this->expectException(InvalidCartException::class);
        $this->expectExceptionMessage('Item name cannot be empty');

        new CartItem(
            new Sku('PROD-001'),
            '',
            1,
            new Money(2999, Currency::Euro),
        );
    }

    #[Test]
    public function it_allows_zero_unit_price(): void
    {
        $item = new CartItem(
            new Sku('FREE-001'),
            'Free Sample',
            1,
            new Money(0, Currency::Euro),
        );

        self::assertSame(0, $item->unitPrice->value);
        self::assertSame(0, $item->lineTotal()->value);
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $item = new CartItem(
            new Sku('PROD-001'),
            'Premium Widget',
            2,
            new Money(2999, Currency::Euro),
        );

        $array = $item->toArray();

        self::assertSame([
            'sku' => 'PROD-001',
            'name' => 'Premium Widget',
            'quantity' => 2,
            'unit_price' => 2999,
            'line_total' => 5998,
        ], $array);
    }
}
