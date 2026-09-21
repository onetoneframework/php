<?php

declare(strict_types=1);

namespace Clover\Tests\Component\Kernel;

use Clover\Component\Container\Container;
use Clover\Component\Http\Request;
use Clover\Component\Kernel\HttpKernel;
use Clover\Component\Routing\Router;
use PHPUnit\Framework\TestCase;

final class HttpKernelTest extends TestCase
{
	public function testHandleDispatchesRequestThroughResolvedRouter(): void
	{
		$container = new Container();
		$router = new Router();
		$router->get('/health', static fn() => 'ok');
		$container->singleton(Router::class, $router);

		$kernel = new HttpKernel($container);
		$response = $kernel->handle(new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/health']));

		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame('ok', $response->getBody());
	}
}
