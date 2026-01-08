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

    public static function fromString(string $value): self
    {
        return self::from(strtoupper($value));
    }
}
