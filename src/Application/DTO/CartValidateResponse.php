<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\Service\CartCalculationResult;
use App\Domain\ValueObject\Currency;

/**
 * Data Transfer Object for cart validation result.
 */
final readonly class CartValidateResponse
{
    public function __construct(
        public bool $success,
        public CartCalculationResult|null $calculation = null,
        public Currency $currency = Currency::Euro,
        public string|null $errorCode = null,
        public string|null $errorMessage = null,
    ) {
    }

    public static function success(CartCalculationResult $calculation, Currency $currency = Currency::Euro): self
    {
        return new self(
            success: true,
            calculation: $calculation,
            currency: $currency,
        );
    }

    public static function error(string $code, string $message): self
    {
        return new self(
            success: false,
            errorCode: $code,
            errorMessage: $message,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        if (! $this->success) {
            return [
                'success' => false,
                'error' => [
                    'code' => $this->errorCode,
                    'message' => $this->errorMessage,
                ],
            ];
        }

        return [
            'success' => true,
            'cart' => $this->calculation?->toArray(),
            'currency' => $this->currency->value,
        ];
    }
}
