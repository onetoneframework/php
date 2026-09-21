<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Annotation;

use Clover\Classes\Routing\RouteAnnotationReader;
use Clover\Classes\Routing\Router;
use PHPUnit\Framework\TestCase;

final class ControllerAnnotationRouteTest extends TestCase
{
	protected function tearDown(): void
	{
		$_SERVER = [];
	}

	public function testControllerAnnotationPrefixesMethodRoutes(): void
	{
		$reader = new RouteAnnotationReader();
		$routes = $reader->read(ControllerRouteFixture::class);

		$methodRoute = null;
		foreach ($routes as $route) {
			if ($route->holder !== null && $route->holder[1] === 'hello') {
				$methodRoute = $route;
				break;
			}
		}

		$this->assertNotNull($methodRoute);
		$this->assertSame('/spring-fixture/hello', $methodRoute->pattern);
		$this->assertSame('GET', $methodRoute->method);
	}

	public function testControllerInstantiation(): void
	{
		$attr = new \Clover\Annotation\Controller('/api');
		$this->assertSame('/api', $attr->value);
	}

	public function testClassLevelRequestMappingPrefixesMethodRouteWithoutDoublingStereotypePath(): void
	{
		$reader = new RouteAnnotationReader();
		$routes = $reader->read(ClassRequestMappingFixture::class);

		$itemRoute = null;
		foreach ($routes as $route) {
			if ($route->holder !== null && $route->holder[1] === 'item') {
				$itemRoute = $route;
				break;
			}
		}

		$this->assertNotNull($itemRoute);
		$this->assertSame('/spring-class-rm/item', $itemRoute->pattern);
	}

	public function testRouterInvokesHandlerRegisteredViaControllerAnnotations(): void
	{
		$_SERVER['HTTP_HOST'] = 'localhost';
		$_SERVER['REQUEST_URI'] = '/router-itest/hit';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['CONTENT_TYPE'] = 'text/plain';

		$fixturePath = __DIR__ . DIRECTORY_SEPARATOR . 'ControllerDispatchFixture.php';

		$router = new Router();
		$router->fromFile($fixturePath);
		$this->assertSame('controller-dispatch-ok', $router->handle());
	}
}
