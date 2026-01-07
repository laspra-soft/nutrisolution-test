<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Value Object representing a percentage.
 * 
 * Example: 20% = new Percentage(20.0)
 * 
 * TODO: Implement these methods:
 * - getValue(): float (returns 20.0 for 20%)
 * - asDecimal(): float (returns 0.20 for 20%)
 * - apply(Money $amount): Money
 * - equals(Percentage $other): bool
 */
final readonly class Percentage
{
    public function __construct(
        private float $value
    ) {
        // TODO: Validate
    }
    
    // TODO: Implement methods
}
