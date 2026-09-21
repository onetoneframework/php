<?php

declare(strict_types=1);

namespace Clover\Tests\Command;

use ArrayIterator;
use PHPUnit\Framework\TestCase;
use RouteListCommand;

final class RouteListCommandTest extends TestCase
{
	public function testRenderRouteTableNormalizesMethodAndLeadingDoubleSlash(): void
	{
		$command = new RouteListCommand();

		$this->expectOutputRegex('/GET.*UserController.*index.*\/users.*application\/json/s');

		$command->renderRouteTable([
			[
				'method' => 'get',
				'class' => 'UserController',
				'caller' => 'index',
				'pattern' => '//users',
				'contentType' => 'application/json',
			],
		]);
	}

	public function testRenderRouteTableAcceptsTraversableAndUsesDefaultContentType(): void
	{
		$command = new RouteListCommand();

		$this->expectOutputRegex('/POST.*HealthController.*check.*\/health.*\*/s');

		$command->renderRouteTable(new ArrayIterator([
			[
				'method' => 'post',
				'class' => 'HealthController',
				'caller' => 'check',
				'pattern' => '/health',
			],
		]));
	}
}
