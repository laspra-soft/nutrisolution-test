<?php

declare(strict_types=1);

namespace Tests\Unit\Application\DTO;

use App\Application\DTO\CartValidateRequest;
use App\Domain\Exception\InvalidCartException;
use App\Domain\ValueObject\CountryCode;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CartRequestTest extends TestCase
{
    #[Test]
    public function it_creates_request_from_valid_array(): void
    {
        $data = [
            'items' => [
                [
                    'sku' => 'PROD-001',
                    'name' => 'Premium Widget',
                    'quantity' => 2,
                    'unit_price' => 2999,
                ],
            ],
            'discount_code' => 'SAVE10',
            'country_code' => 'FR',
            'taxes_included' => true,
        ];

        $request = CartValidateRequest::fromArray($data);

        self::assertCount(1, $request->items);
        self::assertSame('PROD-001', $request->items[0]->sku->value);
        self::assertSame('Premium Widget', $request->items[0]->name);
        self::assertSame(2, $request->items[0]->quantity);
        self::assertSame(2999, $request->items[0]->unitPrice->value);
        self::assertSame('SAVE10', $request->discountCode);
        self::assertSame(CountryCode::France, $request->countryCode);
        self::assertTrue($request->taxesIncluded);
    }

    #[Test]
    public function it_creates_request_without_discount(): void
    {
        $data = [
            'items' => [
                [
                    'sku' => 'PROD-001',
                    'name' => 'Widget',
                    'quantity' => 1,
                    'unit_price' => 1000,
                ],
            ],
            'country_code' => 'DE',
            'taxes_included' => false,
        ];

        $request = CartValidateRequest::fromArray($data);

        self::assertNull($request->discountCode);
        self::assertSame(CountryCode::Germany, $request->countryCode);
        self::assertFalse($request->taxesIncluded);
    }

    #[Test]
    public function it_handles_empty_discount_code(): void
    {
        $data = [
            'items' => [
                [
                    'sku' => 'PROD-001',
                    'name' => 'Widget',
                    'quantity' => 1,
                    'unit_price' => 1000,
                ],
            ],
            'discount_code' => '',
            'country_code' => 'FR',
            'taxes_included' => true,
        ];

        $request = CartValidateRequest::fromArray($data);

        self::assertNull($request->discountCode);
    }

    #[Test]
    public function it_throws_on_missing_items(): void
    {
        $this->expectException(InvalidCartException::class);
        $this->expectExceptionMessage('Items array is required');

        CartValidateRequest::fromArray([
            'country_code' => 'FR',
            'taxes_included' => true,
        ]);
    }

    #[Test]
    public function it_throws_on_empty_items_array(): void
    {
        $this->expectException(InvalidCartException::class);
        $this->expectExceptionMessage('Cart cannot be empty');

        CartValidateRequest::fromArray([
            'items' => [],
            'country_code' => 'FR',
            'taxes_included' => true,
        ]);
    }

    #[Test]
    public function it_throws_on_missing_country_code(): void
    {
        $this->expectException(InvalidCartException::class);
        $this->expectExceptionMessage('Country code is required');

        CartValidateRequest::fromArray([
            'items' => [
                [
                    'sku' => 'PROD-001',
                    'name' => 'Widget',
                    'quantity' => 1,
                    'unit_price' => 1000,
                ],
            ],
            'taxes_included' => true,
        ]);
    }

    #[Test]
    public function it_throws_on_invalid_country_code(): void
    {
        $this->expectException(InvalidCartException::class);
        $this->expectExceptionMessage('Invalid country code: XX');

        CartValidateRequest::fromArray([
            'items' => [
                [
                    'sku' => 'PROD-001',
                    'name' => 'Widget',
                    'quantity' => 1,
                    'unit_price' => 1000,
                ],
            ],
            'country_code' => 'XX',
            'taxes_included' => true,
        ]);
    }

    #[Test]
    public function it_throws_on_missing_taxes_included(): void
    {
        $this->expectException(InvalidCartException::class);
        $this->expectExceptionMessage('taxes_included field is required');

        CartValidateRequest::fromArray([
            'items' => [
                [
                    'sku' => 'PROD-001',
                    'name' => 'Widget',
                    'quantity' => 1,
                    'unit_price' => 1000,
                ],
            ],
            'country_code' => 'FR',
        ]);
    }

    #[Test]
    public function it_throws_on_missing_item_sku(): void
    {
        $this->expectException(InvalidCartException::class);
        $this->expectExceptionMessage('Item SKU is required');

        CartValidateRequest::fromArray([
            'items' => [
                [
                    'name' => 'Widget',
                    'quantity' => 1,
                    'unit_price' => 1000,
                ],
            ],
            'country_code' => 'FR',
            'taxes_included' => true,
        ]);
    }

    #[Test]
    public function it_throws_on_missing_item_name(): void
    {
        $this->expectException(InvalidCartException::class);
        $this->expectExceptionMessage('Item name is required');

        CartValidateRequest::fromArray([
            'items' => [
                [
                    'sku' => 'PROD-001',
                    'quantity' => 1,
                    'unit_price' => 1000,
                ],
            ],
            'country_code' => 'FR',
            'taxes_included' => true,
        ]);
    }

    #[Test]
    public function it_throws_on_missing_item_quantity(): void
    {
        $this->expectException(InvalidCartException::class);
        $this->expectExceptionMessage('Item quantity must be an integer');

        CartValidateRequest::fromArray([
            'items' => [
                [
                    'sku' => 'PROD-001',
                    'name' => 'Widget',
                    'unit_price' => 1000,
                ],
            ],
            'country_code' => 'FR',
            'taxes_included' => true,
        ]);
    }

    #[Test]
    public function it_throws_on_zero_quantity(): void
    {
        $this->expectException(InvalidCartException::class);
        $this->expectExceptionMessage('Item quantity must be positive');

        CartValidateRequest::fromArray([
            'items' => [
                [
                    'sku' => 'PROD-001',
                    'name' => 'Widget',
                    'quantity' => 0,
                    'unit_price' => 1000,
                ],
            ],
            'country_code' => 'FR',
            'taxes_included' => true,
        ]);
    }

    #[Test]
    public function it_throws_on_negative_quantity(): void
    {
        $this->expectException(InvalidCartException::class);
        $this->expectExceptionMessage('Item quantity must be positive');

        CartValidateRequest::fromArray([
            'items' => [
                [
                    'sku' => 'PROD-001',
                    'name' => 'Widget',
                    'quantity' => -1,
                    'unit_price' => 1000,
                ],
            ],
            'country_code' => 'FR',
            'taxes_included' => true,
        ]);
    }

    #[Test]
    public function it_throws_on_missing_unit_price(): void
    {
        $this->expectException(InvalidCartException::class);
        $this->expectExceptionMessage('Item unit_price must be an integer (cents)');

        CartValidateRequest::fromArray([
            'items' => [
                [
                    'sku' => 'PROD-001',
                    'name' => 'Widget',
                    'quantity' => 1,
                ],
            ],
            'country_code' => 'FR',
            'taxes_included' => true,
        ]);
    }

    #[Test]
    public function it_throws_on_negative_unit_price(): void
    {
        $this->expectException(InvalidCartException::class);
        $this->expectExceptionMessage('Item unit_price cannot be negative');

        CartValidateRequest::fromArray([
            'items' => [
                [
                    'sku' => 'PROD-001',
                    'name' => 'Widget',
                    'quantity' => 1,
                    'unit_price' => -100,
                ],
            ],
            'country_code' => 'FR',
            'taxes_included' => true,
        ]);
    }

    #[Test]
    public function it_creates_multiple_items(): void
    {
        $data = [
            'items' => [
                [
                    'sku' => 'PROD-001',
                    'name' => 'Widget A',
                    'quantity' => 2,
                    'unit_price' => 2999,
                ],
                [
                    'sku' => 'PROD-002',
                    'name' => 'Widget B',
                    'quantity' => 1,
                    'unit_price' => 4999,
                ],
            ],
            'country_code' => 'FR',
            'taxes_included' => true,
        ];

        $request = CartValidateRequest::fromArray($data);

        self::assertCount(2, $request->items);
        self::assertSame('PROD-001', $request->items[0]->sku->value);
        self::assertSame('PROD-002', $request->items[1]->sku->value);
    }
}
