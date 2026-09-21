<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\Routing;

use Clover\Classes\Routing\Route;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class RouteConfigurationTest extends TestCase
{
	public function testFluentConfigurationBuildsConsistentRouteMetadata(): void
	{
		$route = (new Route('/users/{id}/{section}?', static fn (): string => 'matched'))
			->setName('users.show')
			->whereNumber('id')
			->whereSlug('section')
			->defaults('section', 'profile')
			->prefix('/api')
			->domain('api.example.com')
			->middleware(['authentication', 'metrics', 'audit'])
			->withoutMiddleware('metrics');

		self::assertSame('users.show', $route->getName());
		self::assertSame('/api', $route->getPrefix());
		self::assertSame('api.example.com', $route->getDomain());
		self::assertSame('api.example.com', $route->getHost());
		self::assertSame(
			['id' => '[0-9]+', 'section' => '[a-z0-9-]+'],
			$route->getWhereConstraints()
		);
		self::assertSame(['section' => 'profile'], $route->getDefaults());
		self::assertSame(['metrics'], $route->getExcludedMiddlewares());
		self::assertSame(['authentication', 'audit'], array_values($route->getEffectiveMiddlewares()));
		self::assertSame('/api/users/42/profile', $route->generateUrl(['id' => 42]));
	}

	public function testGenerateUrlRemovesUnusedOptionalParameter(): void
	{
		$route = new Route('/reports/{year}/{format}?', static fn (): string => 'matched');

		self::assertSame('/reports/2026', $route->generateUrl(['year' => 2026]));
	}

	public function testGenerateUrlRejectsNonStringableParameter(): void
	{
		$route = new Route('/reports/{year}', static fn (): string => 'matched');

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Route parameter "year" must be scalar or stringable.');

		$route->generateUrl(['year' => ['2026']]);
	}

	public function testGenerateUrlTreatsParameterNamesAndValuesAsLiteralText(): void
	{
		$route = new Route('/items/{item.id}/{itemXid}?', static fn (): string => 'matched');

		self::assertSame('/items/$1\\segment', $route->generateUrl(['item.id' => '$1\\segment']));
	}

	public function testConstraintHelpersCanBeCombinedWithCustomConstraints(): void
	{
		$route = (new Route('/items/{identifier}/{code}/{slug}', static fn (): string => 'matched'))
			->whereUuid('identifier')
			->whereAlphaNumeric('code')
			->whereArray(['slug' => '[a-z][a-z0-9-]{2,}']);

		self::assertSame(
			[
				'identifier' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}',
				'code' => '[a-zA-Z0-9]+',
				'slug' => '[a-z][a-z0-9-]{2,}',
			],
			$route->getWhereConstraints()
		);
	}

	public function testCloneWithPatternDoesNotChangeOriginalRoute(): void
	{
		$original = (new Route('/users/{id}', static fn (): string => 'matched'))
			->setName('users.show')
			->whereNumber('id');

		$clone = $original->cloneWithPattern('/members/{id}');

		self::assertNotSame($original, $clone);
		self::assertSame('/users/{id}', (string) $original->getPattern());
		self::assertSame('/members/{id}', (string) $clone->getPattern());
		self::assertSame('users.show', $clone->getName());
		self::assertSame($original->getWhereConstraints(), $clone->getWhereConstraints());
	}

	public function testToArrayContainsSerializableRouteConfiguration(): void
	{
		$route = (new Route('/health', static fn (): string => 'ok'))
			->setName('health')
			->domain('status.example.com')
			->middleware('metrics');

		$configuration = $route->toArray();

		self::assertSame('/health', $configuration['pattern']);
		self::assertSame('health', $configuration['name']);
		self::assertSame('status.example.com', $configuration['host']);
		self::assertSame(['metrics'], $configuration['middlewares']);
		self::assertSame([], $configuration['constraints']);
		self::assertSame([], $configuration['defaults']);
	}
}
