<?php

declare(strict_types=1);

namespace Tests\Functional;

use App\Infrastructure\AppFactory;
use DI\Container;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;

use function json_decode;

/**
 * Base test case for functional tests.
 */
abstract class AppTestCase extends TestCase
{
    /** @var App<Container> */
    protected App $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = (new AppFactory())->create();

        /** @var callable(App<Container>): void $registerRoutes */
        $registerRoutes = require __DIR__ . '/../../config/routes.php';
        $registerRoutes($this->app);
    }

    /**
     * Make a request to the application.
     *
     * @param array<string, mixed> $data
     */
    protected function postJson(string $path, array $data): ResponseInterface
    {
        $requestFactory = new ServerRequestFactory();
        $request        = $requestFactory->createServerRequest('POST', $path)
            ->withHeader('Content-Type', 'application/json')
            ->withParsedBody($data);

        return $this->app->handle($request);
    }

    /**
     * Helper to decode JSON response and ensure it's an array for PHPStan.
     *
     * @return array<string, mixed>
     */
    protected function getJsonBody(ResponseInterface $response): array
    {
        /** @var array<string, mixed>|null $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertIsArray($body);

        return $body;
    }

    /**
     * Assert JSON response structure.
     *
     * @param array<string, mixed> $expected
     */
    protected function assertJsonResponse(ResponseInterface $response, int $expectedStatus, array $expected): void
    {
        self::assertSame($expectedStatus, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));

        /** @var array<string, mixed> $actual */
        $actual = $this->getJsonBody($response);

        self::assertEquals($expected, $actual);
    }
}
