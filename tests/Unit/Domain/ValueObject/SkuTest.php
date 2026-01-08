<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Sku;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SkuTest extends TestCase
{
    #[Test]
    public function it_creates_valid_sku(): void
    {
        $sku = new Sku('PROD-001');

        self::assertSame('PROD-001', $sku->value);
    }

    #[Test]
    public function it_accepts_alphanumeric_with_hyphens_and_underscores(): void
    {
        $sku = new Sku('PROD_001-ABC');

        self::assertSame('PROD_001-ABC', $sku->value);
    }

    #[Test]
    public function it_throws_on_empty_sku(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SKU cannot be empty');

        new Sku('');
    }

    #[Test]
    public function it_throws_on_whitespace_only_sku(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SKU cannot be empty');

        new Sku('   ');
    }

    #[Test]
    public function it_throws_on_invalid_characters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SKU contains invalid characters');

        new Sku('PROD@001');
    }

    #[Test]
    public function it_throws_on_spaces_in_sku(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SKU contains invalid characters');

        new Sku('PROD 001');
    }

    #[Test]
    public function it_converts_to_string(): void
    {
        $sku = new Sku('PROD-001');

        self::assertSame('PROD-001', (string) $sku);
    }
}
