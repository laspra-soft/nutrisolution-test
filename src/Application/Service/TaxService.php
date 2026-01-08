<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\ValueObject\CountryCode;
use App\Domain\ValueObject\Percentage;

final readonly class TaxService
{
    public function getTaxRate(CountryCode $countryCode): Percentage
    {
        return $countryCode->taxRate();
    }
}
