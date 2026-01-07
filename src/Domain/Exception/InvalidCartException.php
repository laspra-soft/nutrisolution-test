<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Thrown when cart validation fails.
 * 
 * Cases: empty cart, invalid quantity, invalid price
 */
class InvalidCartException extends DomainException
{
    public function getErrorCode(): string
    {
        return 'INVALID_CART';
    }
}
