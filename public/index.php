<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use DI\Bridge\Slim\Bridge as SlimAppFactory;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteCollectorProxy;

require __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');

$containerBuilder = new ContainerBuilder();
$dependencies = require __DIR__ . '/../config/dependencies.php';
$dependencies($containerBuilder);
$container = $containerBuilder->build();

$app = SlimAppFactory::create($container);

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$errorMiddleware->setDefaultErrorHandler(
    function (Request $request, Throwable $e, bool $displayErrorDetails) use ($app): Response {
        $status = 500;
        
        if ($e instanceof \App\Domain\Exception\InvalidCartException) {
            $status = 400;
        } elseif ($e instanceof \App\Domain\Exception\InvalidDiscountCodeException) {
            $status = 400;
        }
        
        $payload = [
            'success' => false,
            'error' => [
                'code' => $e instanceof \App\Domain\Exception\DomainException 
                    ? $e->getErrorCode() 
                    : 'INTERNAL_ERROR',
                'message' => $e->getMessage(),
            ]
        ];

        if ($displayErrorDetails) {
            $payload['error']['trace'] = explode("\n", $e->getTraceAsString());
        }

        $response = $app->getResponseFactory()->createResponse($status);
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
);

$app->add(function (Request $request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
});

$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode([
        'app' => 'Cart Validation Test',
        'status' => 'running',
        'php_version' => PHP_VERSION
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->group('/api', function (RouteCollectorProxy $group) {
    // TODO: Replace with your implementation
    // $group->post('/cart/validate', \App\Presentation\Controller\CartController::class . ':validate');
    
    $group->post('/cart/validate', function (Request $request, Response $response) {
        $body = $request->getParsedBody();
        
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => [
                'code' => 'NOT_IMPLEMENTED',
                'message' => 'This endpoint needs to be implemented',
                'received' => $body
            ]
        ], JSON_PRETTY_PRINT));
        
        return $response->withStatus(501)->withHeader('Content-Type', 'application/json');
    });
});

$app->run();
