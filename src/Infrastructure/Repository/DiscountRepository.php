<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Application\Port\DiscountRepositoryInterface;
use App\Domain\Exception\InvalidDiscountCodeException;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\Discount;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Percentage;

use function strtoupper;

/**
 * Repository for discount codes.
 * Uses in-memory array storage for this test.
 */
final class DiscountRepository implements DiscountRepositoryInterface
{
    /** @var array<string, Discount> */
    private array $discounts;

    public function __construct()
    {
        $this->discounts = [
            'SAVE10' => new Discount(
                code: 'SAVE10',
                value: new Percentage(10.0),
            ),
            'FLAT500' => new Discount(
                code: 'FLAT500',
                value: new Money(500, Currency::Euro),
            ),
            'WELCOME20' => new Discount(
                code: 'WELCOME20',
                value: new Percentage(20.0),
                maxCap: new Money(1000, Currency::Euro),
            ),
        ];
    }

    public function getByCode(string $code): Discount
    {
        $normalizedCode = strtoupper($code);

        $discount = $this->discounts[$normalizedCode] ?? null;

        if ($discount === null) {
            throw new InvalidDiscountCodeException($normalizedCode);
        }

        return $discount;
    }
}
