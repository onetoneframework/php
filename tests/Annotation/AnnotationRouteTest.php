<?php

declare(strict_types=1);

namespace Clover\Tests\Annotation;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Annotation\Route;
use PHPUnit\Framework\TestCase;

class AnnotationRouteTest extends TestCase
{
    public function testRouteConstructorDefaults(): void
    {
        $route = new Route();
        $this->assertSame('*', $route->method);
        $this->assertSame('*', $route->pattern);
        $this->assertSame('*', $route->host);
		$this->assertSame(0, $route->priority);
		$this->assertSame('', $route->pathQueryKey);
		$this->assertSame([], $route->query);
    }

    public function testRouteConstructorWithArguments(): void
    {
        $route = new Route('GET', '/users', 'localhost');
        $this->assertSame('GET', $route->method);
        $this->assertSame('/users', $route->pattern);
        $this->assertSame('localhost', $route->host);
    }

	public function testRouteConstructorPreservesMatchingConstraints(): void
	{
		$route = new Route('POST', '/search', 'api.example.test', 25, 'virtualPath', ['format' => 'json']);

		$this->assertSame(25, $route->priority);
		$this->assertSame('virtualPath', $route->pathQueryKey);
		$this->assertSame(['format' => 'json'], $route->query);
	}

    public function testRoutePropertiesAreWritable(): void
    {
        $route = new Route('POST', '/api');
        $route->middleware = 'AuthMiddleware';
        $route->holder = 'api';
        $route->notFoundHandler = 'NotFoundHandler';
        $route->contentType = 'application/json';

        $this->assertSame('AuthMiddleware', $route->middleware);
        $this->assertSame('api', $route->holder);
        $this->assertSame('NotFoundHandler', $route->notFoundHandler);
        $this->assertSame('application/json', $route->contentType);
    }

	public function testRouteMiddlewarePropertyCanHoldMultipleMiddlewareNames(): void
	{
		$route = new Route();
		$route->middleware = ['AuthMiddleware', 'RateLimitMiddleware'];

		$this->assertSame(['AuthMiddleware', 'RateLimitMiddleware'], $route->middleware);
	}
}
