<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Middleware;

use Clover\Classes\DependencyInjection\Container;
use Clover\Framework\Component\Request;
use Clover\Framework\Component\Response;
use Clover\Framework\Contract\RequestHandlerInterface;
use Clover\Framework\Middleware\RoutingMiddleware;
use PHPUnit\Framework\TestCase;

final class RoutingMiddlewareTest extends TestCase
{
    private string $cacheFile = '';

    protected function tearDown(): void
    {
        $_ENV['ROUTE_CACHE'] = 'false';
        $_ENV['ROUTE_CACHE_PATH'] = '';

        if ($this->cacheFile !== '' && file_exists($this->cacheFile)) {
            @unlink($this->cacheFile);
        }
    }

    public function testFallsBackToNextHandlerWhenRouterReturnsFalseWithCachedRoutes(): void
    {
        $this->cacheFile = tempnam(sys_get_temp_dir(), 'route-cache-') . '.php';
        file_put_contents($this->cacheFile, "<?php\nreturn [];\n");

        $_ENV['ROUTE_CACHE'] = 'true';
        $_ENV['ROUTE_CACHE_PATH'] = $this->cacheFile;

        if (!defined('BASE_PATH')) {
            define('BASE_PATH', __DIR__ . '/../../../../root');
        }

        $middleware = new RoutingMiddleware($this->createMock(Container::class));
        $request = new Request([], [], [], [], ['REQUEST_URI' => '/unknown'], '');
        $fallback = new class implements RequestHandlerInterface {
            public function handle(Request $request): Response
            {
                return new Response('fallback-response', [], 'text', 299);
            }
        };

        $response = $middleware->process($request, $fallback);

        $this->assertSame(299, $response->getStatusCode());
        $this->assertSame('fallback-response', $response->getBody());
    }
}
