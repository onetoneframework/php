<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Routing;

use Clover\Classes\Routing\Route;
use Clover\Classes\Routing\RouteCollection;
use PHPUnit\Framework\TestCase;

final class RouteCollectionTest extends TestCase
{
	public function testRoutesCanBeRegisteredQueriedAndRemoved(): void
	{
		$collection = new RouteCollection();
		$getRoute = new Route('/users', static fn (): string => 'users');
		$postRoute = new Route('/users', static fn (): string => 'created');
		$collection->add('GET', $getRoute);
		$collection->add('POST', $postRoute);

		self::assertTrue($collection->hasMethod('GET'));
		self::assertTrue($collection->hasPattern('GET', '/users'));
		self::assertSame([$getRoute], $collection->forMethod('GET'));
		self::assertSame(2, $collection->count());
		self::assertSame(['GET' => ['/users'], 'POST' => ['/users']], $collection->patternsByMethod());

		$collection->removeByMethodAndPattern('GET', '/users');

		self::assertFalse($collection->hasPattern('GET', '/users'));
		self::assertTrue($collection->hasPattern('POST', '/users'));
		self::assertSame(1, $collection->count());
	}

	public function testAddingRouteInvalidatesCompiledCandidateIndex(): void
	{
		$collection = new RouteCollection();
		$users = new Route('/users/{id}', static fn (): string => 'user');
		$posts = new Route('/posts/{id}', static fn (): string => 'post');
		$collection->add('GET', $users);
		$collection->add('GET', $posts);

		self::assertSame([$users], $collection->candidatesFor('GET', ['users', '42']));

		$dynamic = new Route('/{slug}', static fn (): string => 'dynamic');
		$collection->add('GET', $dynamic);

		self::assertSame([$users, $dynamic], $collection->candidatesFor('GET', ['users', '42']));
		self::assertSame([$posts, $dynamic], $collection->candidatesFor('GET', ['posts', '42']));
		self::assertSame([$dynamic], $collection->candidatesFor('GET', ['other']));
		self::assertSame([], $collection->candidatesFor('POST', ['users']));
	}

	public function testNamesExposeMethodAndPatternMetadata(): void
	{
		$collection = new RouteCollection();
		$collection->setName('users.show', 'GET', '/users/{id}');

		self::assertTrue($collection->hasName('users.show'));
		self::assertSame(
			['method' => 'GET', 'pattern' => '/users/{id}'],
			$collection->named('users.show')
		);
		self::assertNull($collection->named('missing'));
	}

	public function testTagsAreDeduplicatedAndSearchable(): void
	{
		$collection = new RouteCollection();
		$collection->addTags('/users', ['api', 'members']);
		$collection->addTags('/users', ['members', 'public']);
		$collection->addTags('/posts', ['api']);

		self::assertSame(['api', 'members', 'public'], array_values($collection->tagsFor('/users')));
		self::assertSame(['/users', '/posts'], $collection->patternsTagged('api'));
		self::assertSame([], $collection->patternsTagged('missing'));
	}

	public function testConstraintsAndLimitsAreStoredPerPattern(): void
	{
		$collection = new RouteCollection();
		$collection->setGlobalPattern('id', '[0-9]+');
		$collection->setRateLimit('/users', 20, 60);
		$collection->setThrottle('/uploads', 10, 30, 3);

		self::assertSame(['id' => '[0-9]+'], $collection->globalPatterns());
		self::assertSame(['max' => 20, 'per' => 60], $collection->rateLimit('/users'));
		self::assertNull($collection->rateLimit('/missing'));
		self::assertSame(['max' => 10, 'per' => 30, 'burst' => 3], $collection->throttle('/uploads'));
		self::assertSame(1, $collection->rateLimitCount());
		self::assertSame(1, $collection->throttleCount());
	}
}
