<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use Exception;

/**
 * Base exception for all domain-level exceptions.
 */
abstract class DomainException extends Exception
{
    abstract public function getErrorCode(): string;
}
