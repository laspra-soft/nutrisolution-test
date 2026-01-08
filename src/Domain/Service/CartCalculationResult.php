<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\CartItem;
use App\Domain\ValueObject\Discount;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Percentage;

use function array_map;

final readonly class CartCalculationResult
{
    /** @param CartItem[] $items */
    public function __construct(
        public array $items,
        public Money $subtotal,
        public Discount|null $discount,
        public Money $discountAmount,
        public Money $subtotalAfterDiscount,
        public Percentage $taxRate,
        public Money $taxAmount,
        public bool $taxesIncluded,
        public Money $total,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $result = [
            'items' => array_map(
                static fn (CartItem $item): array => $item->toArray(),
                $this->items,
            ),
            'subtotal' => $this->subtotal->value,
        ];

        if ($this->discount !== null) {
            $result['discount'] = $this->discount->toArray($this->subtotal);
        }

        $result['subtotal_after_discount'] = $this->subtotalAfterDiscount->value;
        $result['tax']                     = [
            'rate' => $this->taxRate->value,
            'amount' => $this->taxAmount->value,
            'included' => $this->taxesIncluded,
        ];
        $result['total']                   = $this->total->value;

        return $result;
    }
}
