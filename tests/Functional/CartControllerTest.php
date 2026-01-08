<?php

declare(strict_types=1);

namespace Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use Slim\Psr7\Factory\ServerRequestFactory;

use function assert;
use function is_string;

/**
 * Functional tests for the Cart API endpoint.
 */
final class CartControllerTest extends AppTestCase
{
    /**
     * Test 1: Simple cart without discount (FR, taxes included)
     * Input: 2× 2999 + 1× 4999, country=FR, taxes_included=true, no discount
     * Expected: subtotal=10997, tax=1833, total=10997
     */
    #[Test]
    public function it_validates_simple_cart_without_discount_france_taxes_included(): void
    {
        $requestData = [
            'items' => [
                [
                    'sku' => 'PROD-001',
                    'name' => 'Premium Widget',
                    'quantity' => 2,
                    'unit_price' => 2999,
                ],
                [
                    'sku' => 'PROD-002',
                    'name' => 'Basic Gadget',
                    'quantity' => 1,
                    'unit_price' => 4999,
                ],
            ],
            'country_code' => 'FR',
            'taxes_included' => true,
        ];

        $response = $this->postJson('/api/cart/validate', $requestData);

        self::assertSame(200, $response->getStatusCode());
        $body = $this->getJsonBody($response);

        self::assertTrue($body['success']);
        self::assertSame('EUR', $body['currency']);
        self::assertArrayHasKey('cart', $body);
        /** @var array<string, mixed> $cart */
        $cart = $body['cart'];
        self::assertSame(10997, $cart['subtotal']);
        self::assertArrayHasKey('tax', $cart);
        /** @var array<string, mixed> $tax */
        $tax = $cart['tax'];
        self::assertSame(1833, $tax['amount']);
        self::assertSame(10997, $cart['total']);
        self::assertTrue($tax['included']);
    }

    /**
     * Test 2: Percentage discount
     * Input: 1× 10000, country=FR, taxes_included=true, discount=SAVE10
     * Expected: subtotal=10000, discount=1000, total=9000
     */
    #[Test]
    public function it_applies_percentage_discount(): void
    {
        $requestData = [
            'items' => [
                [
                    'sku' => 'PROD-001',
                    'name' => 'Product',
                    'quantity' => 1,
                    'unit_price' => 10000,
                ],
            ],
            'country_code' => 'FR',
            'taxes_included' => true,
            'discount_code' => 'SAVE10',
        ];

        $response = $this->postJson('/api/cart/validate', $requestData);

        self::assertSame(200, $response->getStatusCode());
        $body = $this->getJsonBody($response);

        self::assertTrue($body['success']);
        self::assertArrayHasKey('cart', $body);
        /** @var array<string, mixed> $cart */
        $cart = $body['cart'];
        self::assertSame(10000, $cart['subtotal']);
        self::assertArrayHasKey('discount', $cart);
        /** @var array<string, mixed> $discount */
        $discount = $cart['discount'];
        self::assertSame('SAVE10', $discount['code']);
        self::assertSame('percentage', $discount['type']);
        self::assertEquals(10.0, $discount['value']);
        self::assertSame(1000, $discount['amount']);
        self::assertSame(9000, $cart['total']);
    }

    /**
     * Test 3: Fixed amount discount
     * Input: 1× 10000, country=FR, taxes_included=true, discount=FLAT500
     * Expected: subtotal=10000, discount=500, total=9500
     */
    #[Test]
    public function it_applies_fixed_amount_discount(): void
    {
        $requestData = [
            'items' => [
                [
                    'sku' => 'PROD-001',
                    'name' => 'Product',
                    'quantity' => 1,
                    'unit_price' => 10000,
                ],
            ],
            'country_code' => 'FR',
            'taxes_included' => true,
            'discount_code' => 'FLAT500',
        ];

        $response = $this->postJson('/api/cart/validate', $requestData);

        self::assertSame(200, $response->getStatusCode());
        $body = $this->getJsonBody($response);

        self::assertTrue($body['success']);
        self::assertArrayHasKey('cart', $body);
        /** @var array<string, mixed> $cart */
        $cart = $body['cart'];
        self::assertSame(10000, $cart['subtotal']);
        self::assertArrayHasKey('discount', $cart);
        /** @var array<string, mixed> $discount */
        $discount = $cart['discount'];
        self::assertSame('FLAT500', $discount['code']);
        self::assertSame('fixed', $discount['type']);
        self::assertSame(500, $discount['amount']);
        self::assertSame(9500, $cart['total']);
    }

    /**
     * Test 4: Capped discount
     * Input: 1× 100000, country=FR, taxes_included=true, discount=WELCOME20
     * Expected: discount capped at 1000 (not 20000)
     */
    #[Test]
    public function it_caps_discount_at_maximum(): void
    {
        $requestData = [
            'items' => [
                [
                    'sku' => 'PROD-001',
                    'name' => 'Expensive Product',
                    'quantity' => 1,
                    'unit_price' => 100000,
                ],
            ],
            'country_code' => 'FR',
            'taxes_included' => true,
            'discount_code' => 'WELCOME20',
        ];

        $response = $this->postJson('/api/cart/validate', $requestData);

        self::assertSame(200, $response->getStatusCode());
        $body = $this->getJsonBody($response);

        self::assertTrue($body['success']);
        self::assertArrayHasKey('cart', $body);
        /** @var array<string, mixed> $cart */
        $cart = $body['cart'];
        self::assertSame(100000, $cart['subtotal']);
        self::assertArrayHasKey('discount', $cart);
        /** @var array<string, mixed> $discount */
        $discount = $cart['discount'];
        self::assertSame('WELCOME20', $discount['code']);
        // 20% of 100000 = 20000, but capped at 1000
        self::assertSame(1000, $discount['amount']);
        self::assertSame(99000, $cart['total']);
    }

    /**
     * Test 5: Taxes added (DE, taxes_included=false)
     * Input: 1× 10000, country=DE, taxes_included=false
     * Expected: subtotal=10000, tax=1900, total=11900
     */
    #[Test]
    public function it_calculates_taxes_added_germany(): void
    {
        $requestData = [
            'items' => [
                [
                    'sku' => 'PROD-001',
                    'name' => 'Product',
                    'quantity' => 1,
                    'unit_price' => 10000,
                ],
            ],
            'country_code' => 'DE',
            'taxes_included' => false,
        ];

        $response = $this->postJson('/api/cart/validate', $requestData);

        self::assertSame(200, $response->getStatusCode());
        $body = $this->getJsonBody($response);

        self::assertTrue($body['success']);
        self::assertArrayHasKey('cart', $body);
        /** @var array<string, mixed> $cart */
        $cart = $body['cart'];
        self::assertSame(10000, $cart['subtotal']);
        self::assertArrayHasKey('tax', $cart);
        /** @var array<string, mixed> $tax */
        $tax = $cart['tax'];
        self::assertEquals(19.0, $tax['rate']);
        self::assertSame(1900, $tax['amount']);
        self::assertSame(11900, $cart['total']);
        self::assertFalse($tax['included']);
    }

    /**
     * Test 6: Invalid discount code
     * Input: discount=INVALID123
     * Expected: InvalidDiscountCodeException with 400 status
     */
    #[Test]
    public function it_returns_error_for_invalid_discount_code(): void
    {
        $requestData = [
            'items' => [
                [
                    'sku' => 'PROD-001',
                    'name' => 'Widget',
                    'quantity' => 1,
                    'unit_price' => 10000,
                ],
            ],
            'country_code' => 'FR',
            'taxes_included' => true,
            'discount_code' => 'INVALID123',
        ];

        $response = $this->postJson('/api/cart/validate', $requestData);

        self::assertSame(400, $response->getStatusCode());
        $body = $this->getJsonBody($response);

        self::assertFalse($body['success']);
        self::assertArrayHasKey('error', $body);
        /** @var array<string, mixed> $error */
        $error = $body['error'];
        self::assertSame('INVALID_DISCOUNT_CODE', $error['code']);
        $message = $error['message'];
        assert(is_string($message));
        self::assertStringContainsString("The discount code 'INVALID123' is not valid", $message);
    }

    /**
     * Test 7: Empty cart
     * Input: items=[]
     * Expected: InvalidCartException with 400 status
     */
    #[Test]
    public function it_returns_error_for_empty_cart(): void
    {
        $requestData = [
            'items' => [],
            'country_code' => 'FR',
            'taxes_included' => true,
        ];

        $response = $this->postJson('/api/cart/validate', $requestData);

        self::assertSame(400, $response->getStatusCode());
        $body = $this->getJsonBody($response);

        self::assertFalse($body['success']);
        self::assertArrayHasKey('error', $body);
        /** @var array<string, mixed> $error */
        $error = $body['error'];
        self::assertSame('INVALID_CART', $error['code']);
        $message = $error['message'];
        assert(is_string($message));
        self::assertStringContainsString('Cart cannot be empty', $message);
    }

    /**
     * Test 8: Invalid quantity
     * Input: quantity=0 or quantity=-1
     * Expected: InvalidCartException with 400 status
     */
    #[Test]
    public function it_returns_error_for_invalid_quantity_zero(): void
    {
        $requestData = [
            'items' => [
                [
                    'sku' => 'PROD-001',
                    'name' => 'Widget',
                    'quantity' => 0,
                    'unit_price' => 10000,
                ],
            ],
            'country_code' => 'FR',
            'taxes_included' => true,
        ];

        $response = $this->postJson('/api/cart/validate', $requestData);

        self::assertSame(400, $response->getStatusCode());
        $body = $this->getJsonBody($response);

        self::assertFalse($body['success']);
        self::assertArrayHasKey('error', $body);
        /** @var array<string, mixed> $error */
        $error = $body['error'];
        self::assertSame('INVALID_CART', $error['code']);
        $message = $error['message'];
        assert(is_string($message));
        self::assertStringContainsString('Item quantity must be positive', $message);
    }

    #[Test]
    public function it_returns_error_for_invalid_quantity_negative(): void
    {
        $requestData = [
            'items' => [
                [
                    'sku' => 'PROD-001',
                    'name' => 'Widget',
                    'quantity' => -1,
                    'unit_price' => 10000,
                ],
            ],
            'country_code' => 'FR',
            'taxes_included' => true,
        ];

        $response = $this->postJson('/api/cart/validate', $requestData);

        self::assertSame(400, $response->getStatusCode());
        $body = $this->getJsonBody($response);

        self::assertFalse($body['success']);
        self::assertArrayHasKey('error', $body);
        /** @var array<string, mixed> $error */
        $error = $body['error'];
        self::assertSame('INVALID_CART', $error['code']);
        $message = $error['message'];
        assert(is_string($message));
        self::assertStringContainsString('Item quantity must be positive', $message);
    }

    /**
     * Test: Missing request body
     */
    #[Test]
    public function it_returns_error_for_missing_request_body(): void
    {
        // Create request without parsed body
        $requestFactory = new ServerRequestFactory();
        $request        = $requestFactory->createServerRequest('POST', '/api/cart/validate')
            ->withHeader('Content-Type', 'application/json');

        $response = $this->app->handle($request);

        self::assertSame(400, $response->getStatusCode());
        $body = $this->getJsonBody($response);

        self::assertFalse($body['success']);
        self::assertArrayHasKey('error', $body);
        /** @var array<string, mixed> $error */
        $error = $body['error'];
        self::assertSame('INVALID_CART', $error['code']);
        $message = $error['message'];
        assert(is_string($message));
        self::assertStringContainsString('Items array is required', $message);
    }

    /**
     * Test: Missing required fields
     */
    #[Test]
    public function it_returns_error_for_missing_country_code(): void
    {
        $requestData = [
            'items' => [
                [
                    'sku' => 'PROD-001',
                    'name' => 'Widget',
                    'quantity' => 1,
                    'unit_price' => 10000,
                ],
            ],
            'taxes_included' => true,
        ];

        $response = $this->postJson('/api/cart/validate', $requestData);

        self::assertSame(400, $response->getStatusCode());
        $body = $this->getJsonBody($response);

        self::assertFalse($body['success']);
        self::assertArrayHasKey('error', $body);
        /** @var array<string, mixed> $error */
        $error = $body['error'];
        self::assertSame('INVALID_CART', $error['code']);
        $message = $error['message'];
        assert(is_string($message));
        self::assertStringContainsString('Country code is required', $message);
    }

    /**
     * Test: US has 0% tax
     */
    #[Test]
    public function it_handles_zero_tax_rate_united_states(): void
    {
        $requestData = [
            'items' => [
                [
                    'sku' => 'PROD-001',
                    'name' => 'Product',
                    'quantity' => 1,
                    'unit_price' => 10000,
                ],
            ],
            'country_code' => 'US',
            'taxes_included' => false,
        ];

        $response = $this->postJson('/api/cart/validate', $requestData);

        self::assertSame(200, $response->getStatusCode());
        $body = $this->getJsonBody($response);

        self::assertTrue($body['success']);
        self::assertArrayHasKey('cart', $body);
        /** @var array<string, mixed> $cart */
        $cart = $body['cart'];
        self::assertSame(10000, $cart['subtotal']);
        self::assertArrayHasKey('tax', $cart);
        /** @var array<string, mixed> $tax */
        $tax = $cart['tax'];
        self::assertEquals(0.0, $tax['rate']);
        self::assertSame(0, $tax['amount']);
        self::assertSame(10000, $cart['total']);
    }

    /**
     * Test: Canada 5% GST
     */
    #[Test]
    public function it_calculates_canada_gst(): void
    {
        $requestData = [
            'items' => [
                [
                    'sku' => 'PROD-001',
                    'name' => 'Product',
                    'quantity' => 1,
                    'unit_price' => 10000,
                ],
            ],
            'country_code' => 'CA',
            'taxes_included' => false,
        ];

        $response = $this->postJson('/api/cart/validate', $requestData);

        self::assertSame(200, $response->getStatusCode());
        $body = $this->getJsonBody($response);

        self::assertTrue($body['success']);
        self::assertArrayHasKey('cart', $body);
        /** @var array<string, mixed> $cart */
        $cart = $body['cart'];
        self::assertSame(10000, $cart['subtotal']);
        self::assertArrayHasKey('tax', $cart);
        /** @var array<string, mixed> $tax */
        $tax = $cart['tax'];
        self::assertEquals(5.0, $tax['rate']);
        self::assertSame(500, $tax['amount']);
        self::assertSame(10500, $cart['total']);
    }

    /**
     * Test: Response format matches specification
     */
    #[Test]
    public function it_returns_correct_response_format(): void
    {
        $requestData = [
            'items' => [
                [
                    'sku' => 'PROD-001',
                    'name' => 'Premium Widget',
                    'quantity' => 2,
                    'unit_price' => 2999,
                ],
            ],
            'country_code' => 'FR',
            'taxes_included' => true,
            'discount_code' => 'SAVE10',
        ];

        $response = $this->postJson('/api/cart/validate', $requestData);

        self::assertSame(200, $response->getStatusCode());
        $body = $this->getJsonBody($response);

        // Check top-level structure
        self::assertArrayHasKey('success', $body);
        self::assertArrayHasKey('cart', $body);
        self::assertArrayHasKey('currency', $body);

        // Check cart structure
        /** @var array<string, mixed> $cart */
        $cart = $body['cart'];
        self::assertArrayHasKey('items', $cart);
        self::assertArrayHasKey('subtotal', $cart);
        self::assertArrayHasKey('discount', $cart);
        self::assertArrayHasKey('subtotal_after_discount', $cart);
        self::assertArrayHasKey('tax', $cart);
        self::assertArrayHasKey('total', $cart);

        // Check item structure
        /** @var array<int, array<string, mixed>> $items */
        $items = $cart['items'];
        self::assertArrayHasKey(0, $items);
        /** @var array<string, mixed> $item */
        $item = $items[0];
        self::assertArrayHasKey('sku', $item);
        self::assertArrayHasKey('name', $item);
        self::assertArrayHasKey('quantity', $item);
        self::assertArrayHasKey('unit_price', $item);
        self::assertArrayHasKey('line_total', $item);

        // Check discount structure
        /** @var array<string, mixed> $discount */
        $discount = $cart['discount'];
        self::assertArrayHasKey('code', $discount);
        self::assertArrayHasKey('type', $discount);
        self::assertArrayHasKey('value', $discount);
        self::assertArrayHasKey('amount', $discount);

        // Check tax structure
        /** @var array<string, mixed> $tax */
        $tax = $cart['tax'];
        self::assertArrayHasKey('rate', $tax);
        self::assertArrayHasKey('amount', $tax);
        self::assertArrayHasKey('included', $tax);
    }
}
