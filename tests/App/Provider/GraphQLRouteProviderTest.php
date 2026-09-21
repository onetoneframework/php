<?php

declare(strict_types=1);

namespace Clover\Tests\App\Provider;

use App\Provider\GraphQLRouteProvider;
use Clover\Classes\Routing\Router;
use PHPUnit\Framework\TestCase;

final class GraphQLRouteProviderTest extends TestCase
{
	public function testRegistersGraphqlGetAndPostRoutes(): void
	{
		$router = new Router();
		GraphQLRouteProvider::register($router);

		$routes = $router->toArray();
		$this->assertArrayHasKey('GET', $routes);
		$this->assertArrayHasKey('POST', $routes);
		$this->assertEquals('/graphql', $routes['GET'][0]['pattern']);
		$this->assertEquals('/graphql', $routes['POST'][0]['pattern']);
	}
}
