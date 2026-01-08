<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Service;

use App\Domain\Entity\CartItem;
use App\Domain\Exception\InvalidCartException;
use App\Domain\Service\CartCalculator;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\Discount;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Percentage;
use App\Domain\ValueObject\Sku;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CartCalculatorTest extends TestCase
{
    private CartCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new CartCalculator();
    }

    /**
     * Test 1: Simple cart without discount (FR, taxes included)
     * Input: 2× 2999 + 1× 4999, country=FR, taxes_included=true, no discount
     * Expected: subtotal=10997, tax=1833, total=10997
     */
    #[Test]
    public function it_calculates_simple_cart_without_discount_france_taxes_included(): void
    {
        $items = [
            new CartItem(
                new Sku('PROD-001'),
                'Premium Widget',
                2,
                new Money(2999, Currency::Euro),
            ),
            new CartItem(
                new Sku('PROD-002'),
                'Basic Gadget',
                1,
                new Money(4999, Currency::Euro),
            ),
        ];

        $result = $this->calculator->calculate(
            items: $items,
            taxRate: new Percentage(20.0), // France
            taxesIncluded: true,
            discount: null,
        );

        self::assertSame(10997, $result->subtotal->value);
        self::assertSame(1833, $result->taxAmount->value);
        self::assertSame(10997, $result->total->value);
        self::assertTrue($result->taxesIncluded);
    }

    /**
     * Test 2: Percentage discount
     * Input: 1× 10000, country=FR, taxes_included=true, discount=SAVE10
     * Expected: subtotal=10000, discount=1000, total=9000
     */
    #[Test]
    public function it_applies_percentage_discount(): void
    {
        $items = [
            new CartItem(
                new Sku('PROD-001'),
                'Product',
                1,
                new Money(10000, Currency::Euro),
            ),
        ];

        $discount = new Discount(
            code: 'SAVE10',
            value: new Percentage(10.0),
        );

        $result = $this->calculator->calculate(
            items: $items,
            taxRate: new Percentage(20.0),
            taxesIncluded: true,
            discount: $discount,
        );

        self::assertSame(10000, $result->subtotal->value);
        self::assertSame(1000, $result->discountAmount->value);
        self::assertSame(9000, $result->total->value);
    }

    /**
     * Test 3: Fixed amount discount
     * Input: 1× 10000, country=FR, taxes_included=true, discount=FLAT500
     * Expected: subtotal=10000, discount=500, total=9500
     */
    #[Test]
    public function it_applies_fixed_amount_discount(): void
    {
        $items = [
            new CartItem(
                new Sku('PROD-001'),
                'Product',
                1,
                new Money(10000, Currency::Euro),
            ),
        ];

        $discount = new Discount(
            code: 'FLAT500',
            value: new Money(500, Currency::Euro),
        );

        $result = $this->calculator->calculate(
            items: $items,
            taxRate: new Percentage(20.0),
            taxesIncluded: true,
            discount: $discount,
        );

        self::assertSame(10000, $result->subtotal->value);
        self::assertSame(500, $result->discountAmount->value);
        self::assertSame(9500, $result->total->value);
    }

    /**
     * Test 4: Capped discount
     * Input: 1× 100000, country=FR, taxes_included=true, discount=WELCOME20
     * Expected: discount capped at 1000 (not 20000)
     */
    #[Test]
    public function it_caps_discount_at_maximum(): void
    {
        $items = [
            new CartItem(
                new Sku('PROD-001'),
                'Expensive Product',
                1,
                new Money(100000, Currency::Euro),
            ),
        ];

        $discount = new Discount(
            code: 'WELCOME20',
            value: new Percentage(20.0),
            maxCap: new Money(1000, Currency::Euro),
        );

        $result = $this->calculator->calculate(
            items: $items,
            taxRate: new Percentage(20.0),
            taxesIncluded: true,
            discount: $discount,
        );

        self::assertSame(100000, $result->subtotal->value);
        // 20% of 100000 = 20000, but capped at 1000
        self::assertSame(1000, $result->discountAmount->value);
        self::assertSame(99000, $result->total->value);
    }

    /**
     * Test 5: Taxes added (DE, taxes_included=false)
     * Input: 1× 10000, country=DE, taxes_included=false
     * Expected: subtotal=10000, tax=1900, total=11900
     */
    #[Test]
    public function it_calculates_taxes_added_germany(): void
    {
        $items = [
            new CartItem(
                new Sku('PROD-001'),
                'Product',
                1,
                new Money(10000, Currency::Euro),
            ),
        ];

        $result = $this->calculator->calculate(
            items: $items,
            taxRate: new Percentage(19.0), // Germany
            taxesIncluded: false,
            discount: null,
        );

        self::assertSame(10000, $result->subtotal->value);
        self::assertSame(1900, $result->taxAmount->value);
        self::assertSame(11900, $result->total->value);
        self::assertFalse($result->taxesIncluded);
    }

    /**
     * Test 7: Empty cart
     * Input: items=[]
     * Expected: InvalidCartException
     */
    #[Test]
    public function it_throws_exception_for_empty_cart(): void
    {
        $this->expectException(InvalidCartException::class);
        $this->expectExceptionMessage('Cart cannot be empty');

        $this->calculator->calculate(
            items: [],
            taxRate: new Percentage(20.0),
            taxesIncluded: true,
            discount: null,
        );
    }

    /**
     * Test: US has 0% tax
     */
    #[Test]
    public function it_handles_zero_tax_rate_united_states(): void
    {
        $items = [
            new CartItem(
                new Sku('PROD-001'),
                'Product',
                1,
                new Money(10000, Currency::Euro),
            ),
        ];

        $result = $this->calculator->calculate(
            items: $items,
            taxRate: new Percentage(0.0), // US
            taxesIncluded: false,
            discount: null,
        );

        self::assertSame(10000, $result->subtotal->value);
        self::assertSame(0, $result->taxAmount->value);
        self::assertSame(10000, $result->total->value);
    }

    /**
     * Test: Canada 5% GST
     */
    #[Test]
    public function it_calculates_canada_gst(): void
    {
        $items = [
            new CartItem(
                new Sku('PROD-001'),
                'Product',
                1,
                new Money(10000, Currency::Euro),
            ),
        ];

        $result = $this->calculator->calculate(
            items: $items,
            taxRate: new Percentage(5.0), // Canada
            taxesIncluded: false,
            discount: null,
        );

        self::assertSame(10000, $result->subtotal->value);
        self::assertSame(500, $result->taxAmount->value);
        self::assertSame(10500, $result->total->value);
    }

    /**
     * Test: Discount cannot make total negative
     */
    #[Test]
    public function it_prevents_discount_from_exceeding_subtotal(): void
    {
        $items = [
            new CartItem(
                new Sku('PROD-001'),
                'Cheap Product',
                1,
                new Money(200, Currency::Euro),
            ),
        ];

        // Fixed discount of 500 cents on 200 cent item
        $discount = new Discount(
            code: 'FLAT500',
            value: new Money(500, Currency::Euro),
        );

        $result = $this->calculator->calculate(
            items: $items,
            taxRate: new Percentage(20.0),
            taxesIncluded: true,
            discount: $discount,
        );

        // Discount should be capped at subtotal (200)
        self::assertSame(200, $result->discountAmount->value);
        self::assertSame(0, $result->total->value);
    }

    /**
     * Test: Multiple items calculation
     */
    #[Test]
    public function it_calculates_multiple_items_correctly(): void
    {
        $items = [
            new CartItem(
                new Sku('PROD-001'),
                'Widget A',
                3,
                new Money(1000, Currency::Euro),
            ),
            new CartItem(
                new Sku('PROD-002'),
                'Widget B',
                2,
                new Money(2500, Currency::Euro),
            ),
            new CartItem(
                new Sku('PROD-003'),
                'Widget C',
                1,
                new Money(500, Currency::Euro),
            ),
        ];

        $result = $this->calculator->calculate(
            items: $items,
            taxRate: new Percentage(20.0),
            taxesIncluded: true,
            discount: null,
        );

        // 3×1000 + 2×2500 + 1×500 = 3000 + 5000 + 500 = 8500
        self::assertSame(8500, $result->subtotal->value);
        self::assertSame(8500, $result->total->value);
    }

    /**
     * Test: Line total calculation
     */
    #[Test]
    public function it_calculates_line_totals_correctly(): void
    {
        $items = [
            new CartItem(
                new Sku('PROD-001'),
                'Widget',
                3,
                new Money(2999, Currency::Euro),
            ),
        ];

        $result = $this->calculator->calculate(
            items: $items,
            taxRate: new Percentage(20.0),
            taxesIncluded: true,
            discount: null,
        );

        // 3 × 2999 = 8997
        self::assertSame(8997, $result->subtotal->value);
        self::assertSame(8997, $result->items[0]->lineTotal()->value);
    }

    /**
     * Test: toArray output format
     */
    #[Test]
    public function it_formats_output_correctly(): void
    {
        $items = [
            new CartItem(
                new Sku('PROD-001'),
                'Premium Widget',
                2,
                new Money(2999, Currency::Euro),
            ),
        ];

        $discount = new Discount(
            code: 'SAVE10',
            value: new Percentage(10.0),
        );

        $result = $this->calculator->calculate(
            items: $items,
            taxRate: new Percentage(20.0),
            taxesIncluded: true,
            discount: $discount,
        );

        $output = $result->toArray();

        self::assertArrayHasKey('items', $output);
        self::assertArrayHasKey('subtotal', $output);
        self::assertArrayHasKey('discount', $output);
        self::assertArrayHasKey('subtotal_after_discount', $output);
        self::assertArrayHasKey('tax', $output);
        self::assertArrayHasKey('total', $output);

        self::assertIsArray($output['items']);
        self::assertIsArray($output['items'][0]);
        /** @var array{sku: string, name: string, quantity: int, unit_price: int, line_total: int} $item */
        $item = $output['items'][0];
        self::assertSame('PROD-001', $item['sku']);
        self::assertSame('Premium Widget', $item['name']);
        self::assertSame(2, $item['quantity']);
        self::assertSame(2999, $item['unit_price']);
        self::assertSame(5998, $item['line_total']);

        self::assertIsArray($output['discount']);
        /** @var array{code: string, type: string, value: float, amount: int} $discountOutput */
        $discountOutput = $output['discount'];
        self::assertSame('SAVE10', $discountOutput['code']);
        self::assertSame('percentage', $discountOutput['type']);
        self::assertSame(10.0, $discountOutput['value']);
    }

    /**
     * Test: Discount with taxes added mode
     */
    #[Test]
    public function it_applies_discount_before_taxes_added(): void
    {
        $items = [
            new CartItem(
                new Sku('PROD-001'),
                'Product',
                1,
                new Money(10000, Currency::Euro),
            ),
        ];

        $discount = new Discount(
            code: 'SAVE10',
            value: new Percentage(10.0),
        );

        $result = $this->calculator->calculate(
            items: $items,
            taxRate: new Percentage(20.0),
            taxesIncluded: false,
            discount: $discount,
        );

        // Subtotal = 10000
        // Discount = 1000
        // Subtotal after discount = 9000
        // Tax = 9000 × 20% = 1800
        // Total = 9000 + 1800 = 10800
        self::assertSame(10000, $result->subtotal->value);
        self::assertSame(1000, $result->discountAmount->value);
        self::assertSame(9000, $result->subtotalAfterDiscount->value);
        self::assertSame(1800, $result->taxAmount->value);
        self::assertSame(10800, $result->total->value);
    }
}
