<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Routing;

use Clover\Classes\Routing\RouteDescriptorBuilder;
use PHPUnit\Framework\TestCase;

class RouteDescriptorBuilderTest extends TestCase
{
	public function testBuildCreatesRouteWithConfiguredValues(): void
	{
		$route = RouteDescriptorBuilder::create()
			->withMethod('POST')
			->withPattern('/v1/users')
			->withHost('api.example.com')
			->withPriority(30)
			->build();

		$this->assertSame('POST', $route->method);
		$this->assertSame('/v1/users', $route->pattern);
		$this->assertSame('api.example.com', $route->host);
		$this->assertSame(30, $route->priority);
	}
}
