<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

use function strtoupper;

/**
 * Value Object representing an ISO 3166-1 alpha-2 country code with associated tax rates.
 */
enum CountryCode: string
{
    case France       = 'FR';
    case Germany      = 'DE';
    case UnitedStates = 'US';
    case Canada       = 'CA';

    /**
     * Get the VAT/tax rate for this country.
     */
    public function taxRate(): Percentage
    {
        return new Percentage(match ($this) {
            self::France => 20.0,
            self::Germany => 19.0,
            self::UnitedStates => 0.0,
            self::Canada => 5.0,
        });
    }

    /**
     * Create from string, case-insensitive.
     *
     * @throws InvalidArgumentException
     */
    public static function fromString(string $code): self
    {
        $normalized = strtoupper($code);

        return self::tryFrom($normalized)
            ?? throw new InvalidArgumentException("Unknown country code: $code");
    }
}
