<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\Security\Middleware;

use Clover\Classes\Cache\ArrayCache;
use Clover\Classes\HTTP\Request;
use Clover\Classes\HTTP\Response;
use Clover\Classes\Security\Middleware\RateLimitingMiddleware;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class RateLimitingMiddlewareTest extends TestCase
{
	private const CACHE_KEY = 'rate_limit:test-client';

	private const INITIAL_TIME = 1_000;

	private const MAXIMUM_REQUESTS = 2;

	private const TIME_WINDOW_SECONDS = 60;

	public function testAllowsRequestsWithinTheLimitAndSetsRateLimitHeaders(): void
	{
		$currentTime = self::INITIAL_TIME;
		$cache = new ArrayCache(static function () use (&$currentTime): int {
			return $currentTime;
		});
		$middleware = new RateLimitingMiddleware(
			$cache,
			self::MAXIMUM_REQUESTS,
			self::TIME_WINDOW_SECONDS,
			static fn (Request $request): string => self::CACHE_KEY
		);
		$request = new Request();

		$firstResponse = $middleware->handle(
			$request,
			static fn (Request $request): Response => new Response('accepted')
		);
		$secondResponse = $middleware->handle(
			$request,
			static fn (Request $request): Response => new Response('accepted')
		);

		self::assertSame(200, $firstResponse->getStatus());
		self::assertSame('1', $firstResponse->getResponseHeaders()['X-RateLimit-Remaining']);
		self::assertSame(200, $secondResponse->getStatus());
		self::assertSame('0', $secondResponse->getResponseHeaders()['X-RateLimit-Remaining']);
	}

	public function testRejectsARequestAboveTheLimitWithoutCallingTheNextHandler(): void
	{
		$currentTime = self::INITIAL_TIME;
		$cache = new ArrayCache(static function () use (&$currentTime): int {
			return $currentTime;
		});
		$middleware = new RateLimitingMiddleware(
			$cache,
			self::MAXIMUM_REQUESTS,
			self::TIME_WINDOW_SECONDS,
			static fn (Request $request): string => self::CACHE_KEY
		);
		$request = new Request();
		$nextHandlerCalls = 0;
		$next = static function (Request $request) use (&$nextHandlerCalls): Response {
			$nextHandlerCalls++;

			return new Response('accepted');
		};

		$middleware->handle($request, $next);
		$middleware->handle($request, $next);
		$currentTime += 15;
		$blockedResponse = $middleware->handle($request, $next);

		self::assertSame(429, $blockedResponse->getStatus());
		self::assertSame(2, $nextHandlerCalls);
		self::assertSame(45, $blockedResponse->getBody()['retry_after']);
	}

	public function testAllowsRequestsAgainAfterTheTimeWindowExpires(): void
	{
		$currentTime = self::INITIAL_TIME;
		$cache = new ArrayCache(static function () use (&$currentTime): int {
			return $currentTime;
		});
		$middleware = new RateLimitingMiddleware(
			$cache,
			1,
			self::TIME_WINDOW_SECONDS,
			static fn (Request $request): string => self::CACHE_KEY
		);
		$request = new Request();
		$next = static fn (Request $request): Response => new Response('accepted');

		$middleware->handle($request, $next);
		$blockedResponse = $middleware->handle($request, $next);
		$currentTime += self::TIME_WINDOW_SECONDS;
		$resetResponse = $middleware->handle($request, $next);

		self::assertSame(429, $blockedResponse->getStatus());
		self::assertSame(200, $resetResponse->getStatus());
	}

	public function testRejectsANonPositiveMaximumRequestCount(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('The maximum request count must be at least one.');

		new RateLimitingMiddleware(new ArrayCache(), 0, self::TIME_WINDOW_SECONDS);
	}

	public function testRejectsANonPositiveTimeWindow(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('The rate limit time window must be at least one second.');

		new RateLimitingMiddleware(new ArrayCache(), self::MAXIMUM_REQUESTS, 0);
	}
}
