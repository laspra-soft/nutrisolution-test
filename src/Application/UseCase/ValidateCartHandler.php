<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\CartValidateRequest;
use App\Application\DTO\CartValidateResponse;
use App\Application\Port\DiscountRepositoryInterface;
use App\Application\Service\TaxService;
use App\Domain\Exception\DomainException;
use App\Domain\Service\CartCalculator;
use App\Domain\ValueObject\Currency;

final readonly class ValidateCartHandler
{
    public function __construct(
        private CartCalculator $calculator,
        private DiscountRepositoryInterface $discountRepository,
        private TaxService $taxService,
    ) {
    }

    public function handle(CartValidateRequest $request): CartValidateResponse
    {
        try {
            $taxRate = $this->taxService->getTaxRate($request->countryCode);

            $discount = null;
            if ($request->discountCode !== null) {
                $discount = $this->discountRepository->getByCode($request->discountCode);
            }

            $calculation = $this->calculator->calculate(
                items: $request->items,
                taxRate: $taxRate,
                taxesIncluded: $request->taxesIncluded,
                discount: $discount,
            );

            return CartValidateResponse::success($calculation, Currency::Euro);
        } catch (DomainException $e) {
            return CartValidateResponse::error($e->getErrorCode(), $e->getMessage());
        }
    }
}
