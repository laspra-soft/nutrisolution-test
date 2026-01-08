<?php

declare(strict_types=1);

use App\Application\Port\DiscountRepositoryInterface;
use App\Application\Service\TaxService;
use App\Application\UseCase\ValidateCartHandler;
use App\Domain\Service\CartCalculator;
use App\Infrastructure\Repository\DiscountRepository;
use App\Presentation\Controller\CartValidateController;
use DI\ContainerBuilder;

use function DI\autowire;

return static function (ContainerBuilder $containerBuilder): void {
    $containerBuilder->addDefinitions([
        // Domain Services
        CartCalculator::class => autowire(),

        // Application Services
        TaxService::class => autowire(),

        // Use Cases
        ValidateCartHandler::class => autowire(),

        // Infrastructure
        DiscountRepositoryInterface::class => autowire(DiscountRepository::class),

        // Presentation
        CartValidateController::class => autowire(),
    ]);
};
