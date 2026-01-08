<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use function strtoupper;

enum Currency: string
{
    case Euro               = 'EUR';
    case UnitedStatesDollar = 'USD';
    case CanadianDollar     = 'CAD';
    case BritishPound       = 'GBP';
    case IcelandicKrona     = 'ISK';
    case BahrainiDinar      = 'BHD';

    public static function fromString(string $value): self
    {
        return self::from(strtoupper($value));
    }

    /**
     * Number of decimal places for this currency.
     * Most currencies use 2 (cents), some use 0(Islandic Krona) or 3(Bahraini Dinar).
     */
    public function minorUnit(): int
    {
        return match ($this) {
            self::IcelandicKrona => 0,
            self::BahrainiDinar => 3,
            default => 2,
        };
    }
}
