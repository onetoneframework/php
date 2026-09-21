<?php

declare(strict_types=1);

namespace Clover\Tests\Component\Routing;

use Clover\Component\Http\Request;
use Clover\Component\Routing\RouteRequestHandler;
use Clover\Component\Routing\Router;
use PHPUnit\Framework\TestCase;

final class RouteRequestHandlerTest extends TestCase
{
	public function testHandlerRunsTheAlreadyMatchedRouteWithoutMatchingTheNewRequestAgain(): void
	{
		$router = new Router();
		$router->get('/items/{id}', static function (Request $request, int $id): string {
			return $request->getUri() . ':' . $id;
		});
		$matchedRequest = new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/items/42']);
		$match = $router->match($matchedRequest);

		$this->assertNotNull($match);

		$handler = new RouteRequestHandler($router, $match['route'], $match['parameters']);
		$response = $handler->handle(new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/different']));

		$this->assertSame('/different:42', $response->getBody());
	}
}
