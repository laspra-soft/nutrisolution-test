<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Example test file - implement after completing Money class.
 */
class MoneyTest extends TestCase
{
    #[Test]
    public function it_creates_money_with_valid_cents(): void
    {
        $money = new Money(2999, 'EUR');
        
        $this->assertSame(2999, $money->getCents());
        $this->assertSame('EUR', $money->getCurrency());
    }
    
    #[Test]
    public function it_throws_exception_for_negative_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Money(-100, 'EUR');
    }
    
    #[Test]
    public function it_adds_two_money_values(): void
    {
        $a = new Money(1000, 'EUR');
        $b = new Money(500, 'EUR');
        
        $result = $a->add($b);
        
        $this->assertSame(1500, $result->getCents());
    }
    
    #[Test]
    public function it_multiplies_by_quantity(): void
    {
        $unitPrice = new Money(2999, 'EUR');
        $result = $unitPrice->multiply(3);
        
        $this->assertSame(8997, $result->getCents());
    }
    
    #[Test]
    public function it_throws_when_adding_different_currencies(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $eur = new Money(1000, 'EUR');
        $usd = new Money(1000, 'USD');
        
        $eur->add($usd);
    }
}
