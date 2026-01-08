<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Exception\DomainException;
use App\Infrastructure\Middleware\CorsMiddleware;
use DI\Bridge\Slim\Bridge as SlimAppFactory;
use DI\Container;
use DI\ContainerBuilder;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Throwable;

use function explode;
use function json_encode;

/**
 * Application factory for bootstrapping the Slim application.
 */
final class AppFactory
{
    /**
     * Create and configure the Slim application.
     *
     * @return App<Container>
     */
    public function create(): App
    {
        $container = $this->buildContainer();
        $app       = SlimAppFactory::create($container);

        $this->registerMiddleware($app);
        $this->registerErrorHandler($app);

        return $app;
    }

    /**
     * Build the dependency injection container.
     *
     * @return Container
     */
    private function buildContainer(): Container
    {
        $containerBuilder = new ContainerBuilder();
        /** @var callable(ContainerBuilder<Container>): void $dependencies */
        $dependencies = require __DIR__ . '/../../config/dependencies.php';
        $dependencies($containerBuilder);

        return $containerBuilder->build();
    }

    /**
     * Register middleware on the application.
     *
     * @param App<Container> $app
     */
    private function registerMiddleware(App $app): void
    {
        $app->addBodyParsingMiddleware();
        $app->addRoutingMiddleware();
        $app->add(CorsMiddleware::class);
    }

    /**
     * Register error handler on the application.
     *
     * @param App<Container> $app
     */
    private function registerErrorHandler(App $app): void
    {
        $errorMiddleware = $app->addErrorMiddleware(true, true, true);

        $errorMiddleware->setDefaultErrorHandler(
            static function (Request $request, Throwable $e, bool $displayErrorDetails) use ($app): Response {
                $status = 500;

                if ($e instanceof DomainException) {
                    $status = 400;
                }

                $payload = [
                    'success' => false,
                    'error' => [
                        'code' => $e instanceof DomainException
                            ? $e->getErrorCode()
                            : 'INTERNAL_ERROR',
                        'message' => $e->getMessage(),
                    ],
                ];

                if ($displayErrorDetails) {
                    $payload['error']['trace'] = explode("\n", $e->getTraceAsString());
                }

                $response = $app->getResponseFactory()->createResponse($status);
                $response->getBody()->write((string) json_encode($payload));

                return $response->withHeader('Content-Type', 'application/json');
            }
        );
    }
}
