<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\DTO\CartValidateRequest;
use App\Application\DTO\CartValidateResponse;
use App\Application\UseCase\ValidateCartHandler;
use App\Domain\Exception\InvalidCartException;
use App\Presentation\ControllerHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Single-action controller for cart validation endpoint.
 */
final readonly class CartValidateController
{
    public function __construct(
        private ValidateCartHandler $handler,
    ) {
    }

    /**
     * POST /api/cart/validate
     *
     * Validates a cart and returns calculated totals.
     */
    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string, mixed>|null $body */
        $body = $request->getParsedBody();

        try {
            $cartRequest = CartValidateRequest::fromArray($body ?? []);
            $result      = $this->handler->handle($cartRequest);
        } catch (InvalidCartException $e) {
            $result = CartValidateResponse::error($e->getErrorCode(), $e->getMessage());
        }

        $statusCode = $result->success ? 200 : 400;

        return ControllerHelper::jsonResponse($response, $statusCode, $result->toArray());
    }
}
