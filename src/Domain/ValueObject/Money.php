<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

use function round;
use function sprintf;

/**
 * Value Object representing a monetary amount in minor units (cents).
 *
 * Example: €29.99 = 2999 cents
 */
final readonly class Money
{
    public function __construct(
        public int $value,
        public Currency $currency = Currency::Euro,
    ) {
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

    /**
     * Calculate a percentage of this amount.
     * Uses standard rounding (round half up).
     */
    public function percentage(float $percentage): self
    {
        $amount = round($this->value * $percentage / 100.0);

        return new self((int) $amount, $this->currency);
    }

    public function negative(): self
    {
        return new self($this->value * -1, $this->currency);
    }

    /**
     * Clamp value to zero (return zero if negative).
     */
    public function minZero(): self
    {
        if ($this->value > 0) {
            return $this;
        }

        return new self(0, $this->currency);
    }

    public function isZero(): bool
    {
        return $this->value === 0;
    }

    public function isNegative(): bool
    {
        return $this->value < 0;
    }

    public function isPositive(): bool
    {
        return $this->value > 0;
    }

    public function isEqual(self $other): bool
    {
        self::assertCurrency($this, $other);

        return $this->value === $other->value;
    }

    public function isGreater(self $other): bool
    {
        self::assertCurrency($this, $other);

        return $this->value > $other->value;
    }

    public function isLess(self $other): bool
    {
        self::assertCurrency($this, $other);

        return $this->value < $other->value;
    }

    /**
     * Convert to float (major units).
     * Example: 2999 cents -> 29.99
     */
    public function toFloat(): float
    {
        $power   = $this->currency->minorUnit();
        $divisor = 10 ** $power;

        return $this->value / $divisor;
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

    public static function max(self $first, self ...$collection): self
    {
        $max = $first;

        foreach ($collection as $money) {
            if ($money->isGreater($max)) {
                $max = $money;
            }
        }

        return $max;
    }

    public static function min(self $first, self ...$collection): self
    {
        $min = $first;

        foreach ($collection as $money) {
            if ($money->isLess($min)) {
                $min = $money;
            }
        }

        return $min;
    }

    /**
     * Create Money from major units (e.g., 29.99 EUR -> 2999 cents).
     */
    public static function fromMajorUnit(float|int $amount, Currency $currency = Currency::Euro): self
    {
        return new self((int) round($amount * (10 ** $currency->minorUnit())), $currency);
    }
}
