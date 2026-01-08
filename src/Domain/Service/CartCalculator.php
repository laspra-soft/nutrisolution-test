<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\CartItem;
use App\Domain\Exception\InvalidCartException;
use App\Domain\ValueObject\Discount;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Percentage;

use function count;

/**
 * Domain service for cart calculations.
 * Pure business logic with no external dependencies.
 */
final readonly class CartCalculator
{
    /**
     * Calculate cart totals.
     *
     * @param CartItem[]    $items         Array of cart items
     * @param Percentage    $taxRate       Tax rate for the country
     * @param bool          $taxesIncluded Whether prices already include tax
     * @param Discount|null $discount      Optional discount to apply
     *
     * @return CartCalculationResult
     *
     * @throws InvalidCartException
     */
    public function calculate(
        array $items,
        Percentage $taxRate,
        bool $taxesIncluded,
        Discount|null $discount = null,
    ): CartCalculationResult {
        if (count($items) === 0) {
            throw new InvalidCartException('Cart cannot be empty');
        }

        $subtotal = $this->calculateSubtotal($items);

        $discountAmount = Money::zero($subtotal->currency);
        if ($discount !== null) {
            $discountAmount = $discount->calculateAmount($subtotal);
        }

        $subtotalAfterDiscount = $subtotal->subtract($discountAmount);

        if ($taxesIncluded) {
            $taxAmount = $taxRate->extractTaxFrom($subtotalAfterDiscount);
            $total     = $subtotalAfterDiscount;
        } else {
            $taxAmount = $taxRate->addTaxTo($subtotalAfterDiscount);
            $total     = $subtotalAfterDiscount->add($taxAmount);
        }

        return new CartCalculationResult(
            items: $items,
            subtotal: $subtotal,
            discount: $discount,
            discountAmount: $discountAmount,
            subtotalAfterDiscount: $subtotalAfterDiscount,
            taxRate: $taxRate,
            taxAmount: $taxAmount,
            taxesIncluded: $taxesIncluded,
            total: $total,
        );
    }

    /**
     * Calculate the subtotal (sum of line totals).
     *
     * @param CartItem[] $items
     */
    private function calculateSubtotal(array $items): Money
    {
        $currency = $items[0]->unitPrice->currency;
        $subtotal = Money::zero($currency);

        foreach ($items as $item) {
            $subtotal = $subtotal->add($item->lineTotal());
        }

        return $subtotal;
    }
}
