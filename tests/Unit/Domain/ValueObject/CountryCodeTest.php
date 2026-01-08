<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\CountryCode;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CountryCodeTest extends TestCase
{
    #[Test]
    public function it_returns_france_tax_rate(): void
    {
        $rate = CountryCode::France->taxRate();

        self::assertSame(20.0, $rate->value);
    }

    #[Test]
    public function it_returns_germany_tax_rate(): void
    {
        $rate = CountryCode::Germany->taxRate();

        self::assertSame(19.0, $rate->value);
    }

    #[Test]
    public function it_returns_united_states_tax_rate(): void
    {
        $rate = CountryCode::UnitedStates->taxRate();

        self::assertSame(0.0, $rate->value);
    }

    #[Test]
    public function it_returns_canada_tax_rate(): void
    {
        $rate = CountryCode::Canada->taxRate();

        self::assertSame(5.0, $rate->value);
    }

    #[Test]
    public function it_creates_from_string_lowercase(): void
    {
        $country = CountryCode::fromString('fr');

        self::assertSame(CountryCode::France, $country);
    }

    #[Test]
    public function it_creates_from_string_uppercase(): void
    {
        $country = CountryCode::fromString('FR');

        self::assertSame(CountryCode::France, $country);
    }

    #[Test]
    public function it_creates_from_string_mixed_case(): void
    {
        $country = CountryCode::fromString('De');

        self::assertSame(CountryCode::Germany, $country);
    }

    #[Test]
    public function it_throws_on_unknown_country_code(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown country code: XX');

        CountryCode::fromString('XX');
    }
}
