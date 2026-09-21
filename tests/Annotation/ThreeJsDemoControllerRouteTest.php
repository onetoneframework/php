<?php

declare(strict_types=1);

namespace Clover\Tests\Annotation;

use App\Controller\ThreeJsDemoController;
use Clover\Classes\Routing\RouteAnnotationReader;
use PHPUnit\Framework\TestCase;

final class ThreeJsDemoControllerRouteTest extends TestCase
{
	public function testThreeJsDemoControllerRegistersExpectedGetRoute(): void
	{
		$reader = new RouteAnnotationReader();
		$routes = $reader->read(ThreeJsDemoController::class);

		$demoRoute = null;
		foreach ($routes as $route) {
			if ($route->holder !== null && $route->holder[1] === 'demo') {
				$demoRoute = $route;
				break;
			}
		}

		$this->assertNotNull($demoRoute);
		$this->assertSame('GET', $demoRoute->method);
		$this->assertSame('/threejs-demo', $demoRoute->pattern);
	}
}
