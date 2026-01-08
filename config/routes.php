<?php

declare(strict_types=1);

use App\Presentation\Controller\CartValidateController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

/**
 * Register application routes.
 *
 * @param App $app
 */
return static function (App $app): void {
    $app->get('/', static function (Request $request, Response $response): Response {
        $response->getBody()->write((string) json_encode([
            'app' => 'Cart Validation Test',
            'status' => 'running',
            'php_version' => PHP_VERSION,
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->group('/api', static function (RouteCollectorProxy $group): void {
        $group->post('/cart/validate', CartValidateController::class);
    });
};
