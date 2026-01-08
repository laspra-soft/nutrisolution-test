<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

use function round;
use function sprintf;

/**
 * Value Object representing a monetary amount in cents.
 *
 * Example: €29.99 = 2999 cents
 */
final readonly class Money
{
    public function __construct(
        public int $value,
        public Currency $currency = Currency::Euro,
    ) {
        if ($value < 0) {
            throw new InvalidArgumentException('Amount cannot be negative');
        }
    }

    public function add(Money $other): self
    {
        self::assertCurrency($this, $other);

        return new self($this->value + $other->value, $this->currency);
    }

    public function subtract(Money $other): self
    {
        self::assertCurrency($this, $other);

        return new self($this->value - $other->value, $this->currency);
    }

    public function multiply(int $multiplier): self
    {
        return new self($this->value * $multiplier, $this->currency);
    }

    public function percentage(Percentage $percentage): self
    {
        $amount = round($this->value * $percentage->value / 100.0);

        return new self((int) $amount, $this->currency);
    }

    public function minZero(): self
    {
        if ($this->value > 0) {
            return $this;
        }

        return new self(0, $this->currency);
    }

    public function isGreater(self $other): bool
    {
        self::assertCurrency($this, $other);

        return $this->value > $other->value;
    }

    /** @throws InvalidArgumentException */
    private static function assertCurrency(self $a, self $b): void
    {
        if ($a->currency !== $b->currency) {
            throw new InvalidArgumentException(
                sprintf('Currency mismatch: %s != %s', $a->currency->value, $b->currency->value),
            );
        }
    }

    public static function zero(Currency $currency = Currency::Euro): self
    {
        return new self(0, $currency);
    }
}
