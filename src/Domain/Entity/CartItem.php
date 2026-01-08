<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\InvalidCartException;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Sku;

final readonly class CartItem
{
    /** @throws InvalidCartException */
    public function __construct(
        public Sku $sku,
        public string $name,
        public int $quantity,
        public Money $unitPrice,
    ) {
        if ($quantity <= 0) {
            throw new InvalidCartException("Quantity must be positive, got: $quantity");
        }

        if ($name === '') {
            throw new InvalidCartException('Item name cannot be empty');
        }
    }

    /**
     * Calculate the line total (unit_price × quantity).
     */
    public function lineTotal(): Money
    {
        return $this->unitPrice->multiply($this->quantity);
    }

    /** @return array{sku: string, name: string, quantity: int, unit_price: int, line_total: int} */
    public function toArray(): array
    {
        return [
            'sku' => $this->sku->value,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice->value,
            'line_total' => $this->lineTotal()->value,
        ];
    }
}
