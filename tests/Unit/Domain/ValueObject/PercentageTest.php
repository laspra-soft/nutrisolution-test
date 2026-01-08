<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Percentage;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PercentageTest extends TestCase
{
    #[Test]
    public function it_creates_percentage_with_valid_value(): void
    {
        $percentage = new Percentage(20.0);

        self::assertSame(20.0, $percentage->value);
    }

    #[Test]
    public function it_creates_percentage_with_zero(): void
    {
        $percentage = new Percentage(0.0);

        self::assertSame(0.0, $percentage->value);
    }

    #[Test]
    public function it_creates_percentage_with_decimal_value(): void
    {
        $percentage = new Percentage(19.5);

        self::assertSame(19.5, $percentage->value);
    }

    #[Test]
    public function it_creates_percentage_greater_than_100(): void
    {
        $percentage = new Percentage(150.0);

        self::assertSame(150.0, $percentage->value);
    }

    #[Test]
    public function it_throws_on_negative_percentage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Percentage cannot be negative');

        new Percentage(-10.0);
    }

    #[Test]
    public function it_converts_to_decimal(): void
    {
        $percentage = new Percentage(20.0);

        self::assertSame(0.2, $percentage->asDecimal());
    }

    #[Test]
    public function it_converts_zero_to_decimal(): void
    {
        $percentage = new Percentage(0.0);

        self::assertSame(0.0, $percentage->asDecimal());
    }

    #[Test]
    public function it_converts_100_to_decimal(): void
    {
        $percentage = new Percentage(100.0);

        self::assertSame(1.0, $percentage->asDecimal());
    }

    #[Test]
    public function it_converts_decimal_percentage_to_decimal(): void
    {
        $percentage = new Percentage(19.5);

        self::assertSame(0.195, $percentage->asDecimal());
    }

    #[Test]
    public function it_applies_percentage_to_money(): void
    {
        $percentage = new Percentage(10.0);
        $money      = new Money(10000, Currency::Euro);

        $result = $percentage->apply($money);

        self::assertSame(1000, $result->value);
        self::assertSame(Currency::Euro, $result->currency);
    }

    #[Test]
    public function it_applies_zero_percentage_to_money(): void
    {
        $percentage = new Percentage(0.0);
        $money      = new Money(10000, Currency::Euro);

        $result = $percentage->apply($money);

        self::assertSame(0, $result->value);
    }

    #[Test]
    public function it_applies_100_percentage_to_money(): void
    {
        $percentage = new Percentage(100.0);
        $money      = new Money(10000, Currency::Euro);

        $result = $percentage->apply($money);

        self::assertSame(10000, $result->value);
    }

    #[Test]
    public function it_calculates_multiplier_for_20_percent(): void
    {
        $percentage = new Percentage(20.0);

        self::assertSame(1.2, $percentage->asMultiplier());
    }

    #[Test]
    public function it_calculates_multiplier_for_19_percent(): void
    {
        $percentage = new Percentage(19.0);

        self::assertSame(1.19, $percentage->asMultiplier());
    }

    #[Test]
    public function it_calculates_multiplier_for_zero_percent(): void
    {
        $percentage = new Percentage(0.0);

        self::assertSame(1.0, $percentage->asMultiplier());
    }

    #[Test]
    public function it_calculates_multiplier_for_5_percent(): void
    {
        $percentage = new Percentage(5.0);

        self::assertSame(1.05, $percentage->asMultiplier());
    }

    #[Test]
    public function it_extracts_tax_from_inclusive_price_20_percent(): void
    {
        // Price incl. VAT = 10000, rate = 20%
        // Price excl. VAT = 10000 / 1.20 = 8333.33 -> floor to 8333
        // VAT = 10000 - 8333 = 1667
        $percentage = new Percentage(20.0);
        $price      = new Money(10000, Currency::Euro);

        $tax = $percentage->extractTaxFrom($price);

        self::assertSame(1667, $tax->value);
        self::assertSame(Currency::Euro, $tax->currency);
    }

    #[Test]
    public function it_extracts_tax_from_inclusive_price_19_percent(): void
    {
        // Price incl. VAT = 10000, rate = 19%
        // Price excl. VAT = 10000 / 1.19 = 8403.36 -> floor to 8403
        // VAT = 10000 - 8403 = 1597
        $percentage = new Percentage(19.0);
        $price      = new Money(10000, Currency::Euro);

        $tax = $percentage->extractTaxFrom($price);

        self::assertSame(1597, $tax->value);
    }

    #[Test]
    public function it_extracts_zero_tax_from_zero_rate(): void
    {
        $percentage = new Percentage(0.0);
        $price      = new Money(10000, Currency::Euro);

        $tax = $percentage->extractTaxFrom($price);

        self::assertSame(0, $tax->value);
    }

    #[Test]
    public function it_extracts_tax_from_spec_example(): void
    {
        // From spec: subtotal=10997, country=FR (20%), taxes_included=true
        // Expected tax=1833
        $percentage = new Percentage(20.0);
        $price      = new Money(10997, Currency::Euro);

        $tax = $percentage->extractTaxFrom($price);

        self::assertSame(1833, $tax->value);
    }

    #[Test]
    public function it_extracts_tax_with_5_percent_gst(): void
    {
        // Canada GST 5%
        // Price incl. = 10000 / 1.05 = 9523.81 -> floor to 9523
        // Tax = 10000 - 9523 = 477
        $percentage = new Percentage(5.0);
        $price      = new Money(10000, Currency::Euro);

        $tax = $percentage->extractTaxFrom($price);

        self::assertSame(477, $tax->value);
    }

    #[Test]
    public function it_adds_tax_to_price_20_percent(): void
    {
        // Price excl. VAT = 10000, rate = 20%
        // VAT = 10000 * 0.20 = 2000
        $percentage = new Percentage(20.0);
        $price      = new Money(10000, Currency::Euro);

        $tax = $percentage->addTaxTo($price);

        self::assertSame(2000, $tax->value);
        self::assertSame(Currency::Euro, $tax->currency);
    }

    #[Test]
    public function it_adds_tax_to_price_19_percent(): void
    {
        // Price excl. VAT = 10000, rate = 19%
        // VAT = 10000 * 0.19 = 1900
        $percentage = new Percentage(19.0);
        $price      = new Money(10000, Currency::Euro);

        $tax = $percentage->addTaxTo($price);

        self::assertSame(1900, $tax->value);
    }

    #[Test]
    public function it_adds_zero_tax_for_zero_rate(): void
    {
        // US: 0% federal tax
        $percentage = new Percentage(0.0);
        $price      = new Money(10000, Currency::Euro);

        $tax = $percentage->addTaxTo($price);

        self::assertSame(0, $tax->value);
    }

    #[Test]
    public function it_rounds_up_tax_when_adding(): void
    {
        // 10001 * 19% = 1900.19 -> ceil to 1901
        $percentage = new Percentage(19.0);
        $price      = new Money(10001, Currency::Euro);

        $tax = $percentage->addTaxTo($price);

        self::assertSame(1901, $tax->value);
    }

    #[Test]
    public function it_adds_tax_with_5_percent_gst(): void
    {
        // Canada GST 5%
        // 10000 * 0.05 = 500
        $percentage = new Percentage(5.0);
        $price      = new Money(10000, Currency::Euro);

        $tax = $percentage->addTaxTo($price);

        self::assertSame(500, $tax->value);
    }

    #[Test]
    public function it_converts_to_string(): void
    {
        $percentage = new Percentage(20.0);

        self::assertSame('20%', (string) $percentage);
    }

    #[Test]
    public function it_converts_decimal_to_string(): void
    {
        $percentage = new Percentage(19.5);

        self::assertSame('19.5%', (string) $percentage);
    }

    #[Test]
    public function it_converts_zero_to_string(): void
    {
        $percentage = new Percentage(0.0);

        self::assertSame('0%', (string) $percentage);
    }

    #[Test]
    public function it_preserves_currency_when_extracting_tax(): void
    {
        $percentage = new Percentage(20.0);
        $price      = new Money(10000, Currency::UnitedStatesDollar);

        $tax = $percentage->extractTaxFrom($price);

        self::assertSame(Currency::UnitedStatesDollar, $tax->currency);
    }

    #[Test]
    public function it_preserves_currency_when_adding_tax(): void
    {
        $percentage = new Percentage(20.0);
        $price      = new Money(10000, Currency::UnitedStatesDollar);

        $tax = $percentage->addTaxTo($price);

        self::assertSame(Currency::UnitedStatesDollar, $tax->currency);
    }
}
