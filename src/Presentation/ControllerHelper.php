<?php

declare(strict_types=1);

namespace App\Presentation;

use Psr\Http\Message\ResponseInterface as Response;

use function json_encode;

/**
 * Helper class for common controller operations.
 */
final class ControllerHelper
{
    /**
     * Create a JSON response with proper headers.
     *
     * @param array<string, mixed> $data
     */
    public static function jsonResponse(Response $response, int $statusCode, array $data): Response
    {
        $response->getBody()->write((string) json_encode($data));

        return $response
            ->withStatus($statusCode)
            ->withHeader('Content-Type', 'application/json');
    }
}
