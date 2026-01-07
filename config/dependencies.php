<?php

declare(strict_types=1);

use DI\ContainerBuilder;

return function (ContainerBuilder $containerBuilder): void {
    
    $containerBuilder->addDefinitions([
        // ========================================
        // TODO: Add your dependencies here
        // ========================================
        
        // Example:
        // \App\Application\UseCase\ValidateCartHandler::class => \DI\autowire(),
        // \App\Presentation\Controller\CartController::class => \DI\autowire(),
    ]);
};
