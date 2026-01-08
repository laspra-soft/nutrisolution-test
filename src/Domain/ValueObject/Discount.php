<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Value Object representing a discount that can be applied to a cart.
 */
final readonly class Discount
{
    /**
     * @param string           $code   The discount code (e.g., "SAVE10")
     * @param Percentage|Money $value  The discount value (Percentage for %, Money for fixed)
     * @param Money|null       $maxCap Optional maximum discount cap (only for percentage)
     */
    public function __construct(
        public string $code,
        public Percentage|Money $value,
        public Money|null $maxCap = null,
    ) {
    }

    /** Get the discount type based on the value type. */
    public function type(): DiscountType
    {
        return $this->value instanceof Percentage
            ? DiscountType::Percentage
            : DiscountType::Fixed;
    }

    /** Calculate the discount amount for a given subtotal. */
    public function calculateAmount(Money $subtotal): Money
    {
        if ($this->value instanceof Percentage) {
            $amount = $this->value->apply($subtotal);

            if ($this->maxCap !== null && $amount->isGreater($this->maxCap)) {
                return $this->maxCap;
            }

            return $amount;
        }

        // Fixed amount discount
        // Ensure discount doesn't exceed subtotal
        if ($this->value->isGreater($subtotal)) {
            return $subtotal;
        }

        return $this->value;
    }

    /**
     * Convert to array for output.
     *
     * @return array{code: string, type: string, value: float|int, amount: int}
     */
    public function toArray(Money $subtotal): array
    {
        return [
            'code' => $this->code,
            'type' => $this->type()->value,
            'value' => $this->value->value,
            'amount' => $this->calculateAmount($subtotal)->value,
        ];
    }
}
