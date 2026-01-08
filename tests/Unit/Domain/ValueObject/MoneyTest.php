<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Percentage;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    #[Test]
    public function it_creates_money_with_valid_cents(): void
    {
        $money = new Money(2999, Currency::Euro);

        self::assertSame(2999, $money->value);
        self::assertSame(Currency::Euro, $money->currency);
    }

    #[Test]
    public function it_creates_money_with_default_currency(): void
    {
        $money = new Money(1000);

        self::assertSame(1000, $money->value);
        self::assertSame(Currency::Euro, $money->currency);
    }

    #[Test]
    public function it_allows_zero_amount(): void
    {
        $money = new Money(0, Currency::Euro);

        self::assertSame(0, $money->value);
    }

    #[Test]
    public function it_throws_on_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount cannot be negative');

        new Money(-100, Currency::Euro);
    }

    #[Test]
    public function it_adds_two_money_values(): void
    {
        $a = new Money(1000, Currency::Euro);
        $b = new Money(500, Currency::Euro);

        $result = $a->add($b);

        self::assertSame(1500, $result->value);
        self::assertSame(Currency::Euro, $result->currency);
    }

    #[Test]
    public function it_adds_zero_to_money(): void
    {
        $a = new Money(1000, Currency::Euro);
        $b = new Money(0, Currency::Euro);

        $result = $a->add($b);

        self::assertSame(1000, $result->value);
    }

    #[Test]
    public function it_throws_when_adding_different_currencies(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency mismatch: EUR != USD');

        $eur = new Money(1000, Currency::Euro);
        $usd = new Money(1000, Currency::UnitedStatesDollar);

        $eur->add($usd);
    }

    #[Test]
    public function it_subtracts_two_money_values(): void
    {
        $a = new Money(1000, Currency::Euro);
        $b = new Money(300, Currency::Euro);

        $result = $a->subtract($b);

        self::assertSame(700, $result->value);
        self::assertSame(Currency::Euro, $result->currency);
    }

    #[Test]
    public function it_throws_when_subtracting_to_negative_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount cannot be negative');

        $a = new Money(100, Currency::Euro);
        $b = new Money(300, Currency::Euro);

        $a->subtract($b);
    }

    #[Test]
    public function it_throws_when_subtracting_different_currencies(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency mismatch: EUR != USD');

        $eur = new Money(1000, Currency::Euro);
        $usd = new Money(500, Currency::UnitedStatesDollar);

        $eur->subtract($usd);
    }

    #[Test]
    public function it_multiplies_by_quantity(): void
    {
        $unitPrice = new Money(2999, Currency::Euro);
        $result    = $unitPrice->multiply(3);

        self::assertSame(8997, $result->value);
        self::assertSame(Currency::Euro, $result->currency);
    }

    #[Test]
    public function it_multiplies_by_zero(): void
    {
        $money  = new Money(1000, Currency::Euro);
        $result = $money->multiply(0);

        self::assertSame(0, $result->value);
    }

    #[Test]
    public function it_multiplies_by_one(): void
    {
        $money  = new Money(1000, Currency::Euro);
        $result = $money->multiply(1);

        self::assertSame(1000, $result->value);
    }

    #[Test]
    public function it_calculates_percentage(): void
    {
        $money = new Money(10000, Currency::Euro);

        $result = $money->percentage(new Percentage(10));

        self::assertSame(1000, $result->value);
        self::assertSame(Currency::Euro, $result->currency);
    }

    #[Test]
    public function it_calculates_zero_percentage(): void
    {
        $money = new Money(10000, Currency::Euro);

        $result = $money->percentage(new Percentage(0));

        self::assertSame(0, $result->value);
    }

    #[Test]
    public function it_calculates_full_percentage(): void
    {
        $money = new Money(10000, Currency::Euro);

        $result = $money->percentage(new Percentage(100));

        self::assertSame(10000, $result->value);
    }

    #[Test]
    public function it_rounds_percentage_half_up(): void
    {
        // 10997 * 10% = 1099.7 -> rounds to 1100
        $money = new Money(10997, Currency::Euro);

        $result = $money->percentage(new Percentage(10));

        self::assertSame(1100, $result->value);
    }

    #[Test]
    public function it_rounds_percentage_down_when_below_half(): void
    {
        // 10993 * 10% = 1099.3 -> rounds to 1099
        $money = new Money(10993, Currency::Euro);

        $result = $money->percentage(new Percentage(10));

        self::assertSame(1099, $result->value);
    }

    #[Test]
    public function it_handles_percentage_with_decimal(): void
    {
        // 10000 * 19.5% = 1950
        $money = new Money(10000, Currency::Euro);

        $result = $money->percentage(new Percentage(19.5));

        self::assertSame(1950, $result->value);
    }

    #[Test]
    public function it_returns_self_for_positive_with_min_zero(): void
    {
        $positive = new Money(500, Currency::Euro);

        $result = $positive->minZero();

        self::assertSame(500, $result->value);
    }

    #[Test]
    public function it_returns_self_for_zero_with_min_zero(): void
    {
        $zero = new Money(0, Currency::Euro);

        $result = $zero->minZero();

        self::assertSame(0, $result->value);
    }

    #[Test]
    public function it_checks_greater_than(): void
    {
        $a = new Money(2000, Currency::Euro);
        $b = new Money(1000, Currency::Euro);
        $c = new Money(2000, Currency::Euro);

        self::assertTrue($a->isGreater($b));
        self::assertFalse($b->isGreater($a));
        self::assertFalse($a->isGreater($c)); // Equal is not greater
    }

    #[Test]
    public function it_throws_when_comparing_greater_with_different_currencies(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency mismatch: EUR != USD');

        $eur = new Money(2000, Currency::Euro);
        $usd = new Money(1000, Currency::UnitedStatesDollar);

        $eur->isGreater($usd);
    }

    #[Test]
    public function it_creates_zero(): void
    {
        $zero = Money::zero();

        self::assertSame(0, $zero->value);
        self::assertSame(Currency::Euro, $zero->currency);
    }

    #[Test]
    public function it_creates_zero_with_specific_currency(): void
    {
        $zero = Money::zero(Currency::UnitedStatesDollar);

        self::assertSame(0, $zero->value);
        self::assertSame(Currency::UnitedStatesDollar, $zero->currency);
    }

    #[Test]
    public function it_returns_new_instance_on_add(): void
    {
        $original = new Money(1000, Currency::Euro);
        $other    = new Money(500, Currency::Euro);

        $result = $original->add($other);

        self::assertNotSame($original, $result);
        self::assertSame(1000, $original->value);
    }

    #[Test]
    public function it_returns_new_instance_on_subtract(): void
    {
        $original = new Money(1000, Currency::Euro);
        $other    = new Money(300, Currency::Euro);

        $result = $original->subtract($other);

        self::assertNotSame($original, $result);
        self::assertSame(1000, $original->value);
    }

    #[Test]
    public function it_returns_new_instance_on_multiply(): void
    {
        $original = new Money(1000, Currency::Euro);

        $result = $original->multiply(3);

        self::assertNotSame($original, $result);
        self::assertSame(1000, $original->value);
    }

    #[Test]
    public function it_returns_new_instance_on_percentage(): void
    {
        $original = new Money(10000, Currency::Euro);

        $result = $original->percentage(new Percentage(10));

        self::assertNotSame($original, $result);
        self::assertSame(10000, $original->value);
    }
}
