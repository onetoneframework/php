<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Middleware;

use Clover\Framework\Component\Request;
use Clover\Framework\Component\Response;
use Clover\Framework\Contract\RequestHandlerInterface;
use Clover\Framework\Middleware\MaintenanceMiddleware;
use PHPUnit\Framework\TestCase;

final class MaintenanceMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_ENV['APP_MAINTENANCE']);
    }

    public function testBypassesMaintenanceForHealthPath(): void
    {
        $_ENV['APP_MAINTENANCE'] = 'true';
        $middleware = new MaintenanceMiddleware();
        $request = new Request([], [], [], [], ['REQUEST_URI' => '/health'], '');

        $handler = new class () implements RequestHandlerInterface {
            public function handle(Request $request): Response
            {
                return new Response('up', [], 'text', 200);
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testReturns503ForNormalPathsWhenMaintenanceOn(): void
    {
        $_ENV['APP_MAINTENANCE'] = 'true';
        $middleware = new MaintenanceMiddleware();
        $request = new Request([], [], [], [], ['REQUEST_URI' => '/products'], '');

        $handler = new class () implements RequestHandlerInterface {
            public function handle(Request $request): Response
            {
                return new Response('should not run', [], 'text', 200);
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertSame(503, $response->getStatusCode());
    }
}
