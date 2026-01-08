<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Value Object representing a monetary amount in CENTS.
 *
 * Example: €29.99 = 2999 cents
 *
 * TODO: Implement these methods:
 * - getCents(): int
 * - getCurrency(): string
 * - add(Money $other): Money
 * - subtract(Money $other): Money
 * - multiply(int $quantity): Money
 * - percentage(Percentage $percent): Money
 * - isZero(): bool
 * - isGreaterThan(Money $other): bool
 * - equals(Money $other): bool
 */
final readonly class Money
{
    public function __construct(
        private int $cents,
        private string $currency = 'EUR',
    ) {
        // TODO: Validate (no negative amounts)
    }

    // TODO: Implement methods
}
