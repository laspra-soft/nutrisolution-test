<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

use function ceil;
use function floor;
use function sprintf;

/**
 * Value Object representing a percentage.
 *
 * Example: 20% = new Percentage(20.0)
 */
final readonly class Percentage
{
    public function __construct(
        public float $value,
    ) {
        if ($value < 0) {
            throw new InvalidArgumentException('Percentage cannot be negative');
        }
    }

    public function asDecimal(): float
    {
        return $this->value / 100;
    }

    public function apply(Money $amount): Money
    {
        return $amount->percentage($this);
    }

    public function asMultiplier(): float
    {
        return 1 + $this->asDecimal();
    }

    /**
     * Extract tax amount from a tax-inclusive price.
     * Formula: tax = price - (price / (1 + rate))
     */
    public function extractTaxFrom(Money $priceIncludingTax): Money
    {
        $priceExcludingTax = (int) floor($priceIncludingTax->value / $this->asMultiplier());
        $taxAmount         = $priceIncludingTax->value - $priceExcludingTax;

        return new Money($taxAmount, $priceIncludingTax->currency);
    }

    /**
     * Add tax to a price (for taxes_included = false).
     * Uses ceil for rounding up taxes as per spec.
     */
    public function addTaxTo(Money $priceExcludingTax): Money
    {
        $taxAmount = (int) ceil($priceExcludingTax->value * $this->asDecimal());

        return new Money($taxAmount, $priceExcludingTax->currency);
    }

    public function __toString(): string
    {
        return sprintf('%s%%', $this->value);
    }
}
