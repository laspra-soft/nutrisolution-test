<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

enum DiscountType: string
{
    case Percentage = 'percentage';
    case Fixed      = 'fixed';
}
