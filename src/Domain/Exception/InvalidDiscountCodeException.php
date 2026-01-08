<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Thrown when a discount code is not found or invalid.
 */
class InvalidDiscountCodeException extends DomainException
{
    public function __construct(string $code)
    {
        parent::__construct("The discount code '$code' is not valid");
    }

    public function getErrorCode(): string
    {
        return 'INVALID_DISCOUNT_CODE';
    }
}
