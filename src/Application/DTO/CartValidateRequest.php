<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\Entity\CartItem;
use App\Domain\Exception\InvalidCartException;
use App\Domain\ValueObject\CountryCode;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Sku;
use InvalidArgumentException;

use function count;
use function is_array;
use function is_int;
use function is_string;

/**
 * Data Transfer Object for cart validation requests.
 */
final readonly class CartValidateRequest
{
    /** @param CartItem[] $items */
    public function __construct(
        public array $items,
        public CountryCode $countryCode,
        public bool $taxesIncluded,
        public string|null $discountCode = null,
    ) {
    }

    /**
     * Create from raw request array.
     *
     * @param array<string, mixed> $data
     *
     * @throws InvalidCartException
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['items']) || ! is_array($data['items'])) {
            throw new InvalidCartException('Items array is required');
        }

        if (count($data['items']) === 0) {
            throw new InvalidCartException('Cart cannot be empty');
        }

        if (! isset($data['country_code']) || ! is_string($data['country_code'])) {
            throw new InvalidCartException('Country code is required');
        }

        if (! isset($data['taxes_included'])) {
            throw new InvalidCartException('taxes_included field is required');
        }

        try {
            $countryCode = CountryCode::fromString($data['country_code']);
        } catch (InvalidArgumentException) {
            throw new InvalidCartException("Invalid country code: {$data['country_code']}");
        }

        $items = [];
        /** @var mixed $itemData */
        foreach ($data['items'] as $itemData) {
            if (! is_array($itemData)) {
                throw new InvalidCartException('Item must be an array');
            }

            /** @var array<string, mixed> $itemArray */
            $itemArray = $itemData;
            $validated = self::validateItem($itemArray);

            $items[] = new CartItem(
                sku: new Sku($validated['sku']),
                name: $validated['name'],
                quantity: $validated['quantity'],
                unitPrice: new Money($validated['unit_price'], Currency::Euro),
            );
        }

        $discountCode = null;
        if (isset($data['discount_code']) && is_string($data['discount_code']) && $data['discount_code'] !== '') {
            $discountCode = $data['discount_code'];
        }

        return new self(
            items: $items,
            countryCode: $countryCode,
            taxesIncluded: (bool) $data['taxes_included'],
            discountCode: $discountCode,
        );
    }

    /**
     * @param array<string, mixed> $itemData
     *
     * @return array{sku: string, name: string, quantity: int, unit_price: int}
     *
     * @throws InvalidCartException
     */
    private static function validateItem(array $itemData): array
    {
        if (! isset($itemData['sku']) || ! is_string($itemData['sku'])) {
            throw new InvalidCartException('Item SKU is required');
        }

        if (! isset($itemData['name']) || ! is_string($itemData['name'])) {
            throw new InvalidCartException('Item name is required');
        }

        if (! isset($itemData['quantity']) || ! is_int($itemData['quantity'])) {
            throw new InvalidCartException('Item quantity must be an integer');
        }

        if ($itemData['quantity'] <= 0) {
            throw new InvalidCartException('Item quantity must be positive');
        }

        if (! isset($itemData['unit_price']) || ! is_int($itemData['unit_price'])) {
            throw new InvalidCartException('Item unit_price must be an integer (cents)');
        }

        if ($itemData['unit_price'] < 0) {
            throw new InvalidCartException('Item unit_price cannot be negative');
        }

        return [
            'sku' => $itemData['sku'],
            'name' => $itemData['name'],
            'quantity' => $itemData['quantity'],
            'unit_price' => $itemData['unit_price'],
        ];
    }
}
