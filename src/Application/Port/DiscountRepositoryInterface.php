<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Domain\ValueObject\Discount;

interface DiscountRepositoryInterface
{
    public function getByCode(string $code): Discount;
}
