<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

use function preg_match;
use function trim;

/**
 * Value Object representing a Stock Keeping Unit (SKU).
 */
final readonly class Sku
{
    public function __construct(
        public string $value,
    ) {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InvalidArgumentException('SKU cannot be empty');
        }

        if (preg_match('/^[A-Za-z0-9\-_]+$/', $trimmed) !== 1) {
            throw new InvalidArgumentException('SKU contains invalid characters');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
