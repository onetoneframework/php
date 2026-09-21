<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Routing;

#region use

use Clover\Classes\Data\ArrayObject;
use Clover\Classes\Routing\Route as RouteObject;
use function count;
use function in_array;

#endregion

/**
 * Owns everything the router has registered: the routes themselves, the names,
 * tags, parameter constraints, rate limits and throttles attached to their
 * patterns, and the first-segment lookup table matching is driven from.
 *
 * Router keeps the registration API — `get()`, `post()`, `group()` and the rest
 * — because that is the surface callers bind against. What it no longer keeps
 * is the storage: every read and write of that state goes through this object,
 * so the invariant that adding or removing a route invalidates the compiled
 * table is enforced in one place instead of at eleven call sites.
 *
 * @package Clover\Classes\Routing
 */
class RouteCollection
{
	#region properties

	/**
	 * Registered routes, keyed by uppercase HTTP method.
	 *
	 * @var array<string, RouteObject[]>
	 */
	private array $routes = [];

	/**
	 * Named routes, keyed by name.
	 *
	 * @var array<string, array{method: string, pattern: string}>
	 */
	private array $namedRoutes = [];

	/**
	 * Tags assigned to route patterns.
	 *
	 * @var array<string, string[]>
	 */
	private array $routeTags = [];

	/**
	 * Global parameter constraints applied to every route.
	 *
	 * @var array<string, string>
	 */
	private array $globalPatterns = [];

	/**
	 * Rate limits keyed by route pattern.
	 *
	 * @var array<string, array{max: int, per: int}>
	 */
	private array $rateLimits = [];

	/**
	 * Throttle configurations keyed by route pattern.
	 *
	 * @var array<string, array{max: int, per: int, burst: int}>
	 */
	private array $throttleConfigs = [];

	/**
	 * Routes bucketed by method and first path segment, or null when the table
	 * needs rebuilding. Kept private with no accessor: it is a derived cache,
	 * and the only legitimate way to observe it is {@see candidatesFor()}.
	 *
	 * @var array<string, array<string, RouteObject[]>>|null
	 */
	private ?array $compiledRoutes = null;

	#endregion

	#region routes

	/**
	 * Register a route under an HTTP method.
	 *
	 * @param string      $method The HTTP method the route answers.
	 * @param RouteObject $route  The route to store.
	 *
	 * @return void
	 */
	public function add(string $method, RouteObject $route): void
	{
		if (!isset($this->routes[$method])) {
			$this->routes[$method] = [];
		}

		$this->routes[$method][] = $route;
		$this->compiledRoutes = null;
	}

	/**
	 * Whether any route is registered for the method.
	 *
	 * @param string|null $method The HTTP method to look for.
	 *
	 * @return bool
	 */
	public function hasMethod(?string $method): bool
	{
		return isset($this->routes[$method]);
	}

	/**
	 * The routes registered for one HTTP method.
	 *
	 * @param string $method The HTTP method to read.
	 *
	 * @return RouteObject[]
	 */
	public function forMethod(string $method): array
	{
		return $this->routes[$method] ?? [];
	}

	/**
	 * Every registered route, keyed by HTTP method.
	 *
	 * @return array<string, RouteObject[]>
	 */
	public function all(): array
	{
		return $this->routes;
	}

	/**
	 * Whether a pattern is registered under the given method.
	 *
	 * @param string $method  The HTTP method to search.
	 * @param string $pattern The pattern to look for.
	 *
	 * @return bool
	 */
	public function hasPattern(string $method, string $pattern): bool
	{
		foreach ($this->forMethod($method) as $route) {
			if ((string) $route->getPattern() === $pattern) {
				return true;
			}
		}

		return false;
	}

	/**
	 * How many routes are registered across all methods.
	 *
	 * @return int
	 */
	public function count(): int
	{
		$total = 0;

		foreach ($this->routes as $routes) {
			$total += count($routes);
		}

		return $total;
	}

	/**
	 * Drop every route matching a pattern under one method.
	 *
	 * @param string $method  The HTTP method to filter.
	 * @param string $pattern The pattern to remove.
	 *
	 * @return void
	 */
	public function removeByMethodAndPattern(string $method, string $pattern): void
	{
		if (!isset($this->routes[$method])) {
			return;
		}

		$this->routes[$method] = array_values(array_filter(
			$this->routes[$method],
			static fn (RouteObject $route): bool => (string) $route->getPattern() !== $pattern
		));

		$this->compiledRoutes = null;
	}

	/**
	 * Drop every route matching a pattern, across all methods.
	 *
	 * @param string $pattern The pattern to remove.
	 *
	 * @return void
	 */
	public function removeByPattern(string $pattern): void
	{
		foreach ($this->routes as $method => $routes) {
			$this->routes[$method] = array_values(array_filter(
				$routes,
				static fn (RouteObject $route): bool => (string) $route->getPattern() !== $pattern
			));
		}

		$this->compiledRoutes = null;
	}

	/**
	 * Forget every registered route. Names, tags and constraints are left
	 * alone, matching what Router::clear() has always done.
	 *
	 * @return void
	 */
	public function clear(): void
	{
		$this->routes = [];
		$this->compiledRoutes = null;
	}

	/**
	 * The registered patterns, grouped by HTTP method.
	 *
	 * @return array<string, string[]>
	 */
	public function patternsByMethod(): array
	{
		$list = [];

		foreach ($this->routes as $method => $routes) {
			$list[$method] = [];

			foreach ($routes as $route) {
				$list[$method][] = (string) $route->getPattern();
			}
		}

		return $list;
	}

	#endregion

	#region matching

	/**
	 * The routes worth matching against a request, narrowed by HTTP method and
	 * first path segment.
	 *
	 * @param string             $method          The request method.
	 * @param ArrayObject|array<int, mixed> $urlPathSegments The request path, split on "/".
	 *
	 * @return RouteObject[]
	 */
	public function candidatesFor(string $method, ArrayObject|array $urlPathSegments): array
	{
		$this->compile();

		if (!isset($this->compiledRoutes[$method])) {
			return [];
		}

		$first = $urlPathSegments instanceof ArrayObject
			? (string) ($urlPathSegments->getByIndex(0) ?? '')
			: (string) ($urlPathSegments[0] ?? '');

		$candidates = $this->compiledRoutes[$method][$first] ?? [];
		$wildcard = $this->compiledRoutes[$method]['*'] ?? [];
		$root = $this->compiledRoutes[$method][''] ?? [];

		return [...$candidates, ...$wildcard, ...$root];
	}

	/**
	 * Bucket every route by method and first path segment, unless that has
	 * already been done since the last change.
	 *
	 * @return void
	 */
	private function compile(): void
	{
		if ($this->compiledRoutes !== null) {
			return;
		}

		$this->compiledRoutes = [];

		foreach ($this->routes as $method => $routes) {
			$this->compiledRoutes[$method] = [];

			foreach ($routes as $route) {
				$pattern = trim((string) $route->getPattern(), '/');

				if ($pattern === '') {
					$key = '';
				} else {
					$firstSegment = explode('/', $pattern, 2)[0];
					$key = (str_starts_with($firstSegment, '{') && str_contains($firstSegment, '}')) ? '*' : $firstSegment;
				}

				$this->compiledRoutes[$method][$key][] = $route;
			}
		}
	}

	#endregion

	#region names

	/**
	 * Give a name to a method and pattern pair.
	 *
	 * @param string $name    The route name.
	 * @param string $method  The HTTP method.
	 * @param string $pattern The route pattern.
	 *
	 * @return void
	 */
	public function setName(string $name, string $method, string $pattern): void
	{
		$this->namedRoutes[$name] = ['method' => $method, 'pattern' => $pattern];
	}

	/**
	 * Whether a name is registered.
	 *
	 * @param string $name The route name.
	 *
	 * @return bool
	 */
	public function hasName(string $name): bool
	{
		return isset($this->namedRoutes[$name]);
	}

	/**
	 * The method and pattern a name points at.
	 *
	 * @param string $name The route name.
	 *
	 * @return array{method: string, pattern: string}|null
	 */
	public function named(string $name): ?array
	{
		return $this->namedRoutes[$name] ?? null;
	}

	/**
	 * Every named route.
	 *
	 * @return array<string, array{method: string, pattern: string}>
	 */
	public function names(): array
	{
		return $this->namedRoutes;
	}

	#endregion

	#region tags

	/**
	 * Attach tags to a route pattern, keeping any it already carries.
	 *
	 * @param string   $pattern The route pattern.
	 * @param string[] $tags    Tags to add.
	 *
	 * @return void
	 */
	public function addTags(string $pattern, array $tags): void
	{
		if (!isset($this->routeTags[$pattern])) {
			$this->routeTags[$pattern] = [];
		}

		$this->routeTags[$pattern] = array_unique(array_merge($this->routeTags[$pattern], $tags));
	}

	/**
	 * The tags on one route pattern.
	 *
	 * @param string $pattern The route pattern.
	 *
	 * @return string[]
	 */
	public function tagsFor(string $pattern): array
	{
		return $this->routeTags[$pattern] ?? [];
	}

	/**
	 * Every tagged pattern with its tags.
	 *
	 * @return array<string, string[]>
	 */
	public function tags(): array
	{
		return $this->routeTags;
	}

	/**
	 * The route patterns carrying a tag.
	 *
	 * @param string $tag The tag to search for.
	 *
	 * @return string[]
	 */
	public function patternsTagged(string $tag): array
	{
		$result = [];

		foreach ($this->routeTags as $pattern => $tags) {
			if (in_array($tag, $tags)) {
				$result[] = $pattern;
			}
		}

		return $result;
	}

	#endregion

	#region constraints and limits

	/**
	 * Constrain a route parameter everywhere it appears.
	 *
	 * @param string $parameter The parameter name.
	 * @param string $regex     The pattern it must match.
	 *
	 * @return void
	 */
	public function setGlobalPattern(string $parameter, string $regex): void
	{
		$this->globalPatterns[$parameter] = $regex;
	}

	/**
	 * Every global parameter constraint.
	 *
	 * @return array<string, string>
	 */
	public function globalPatterns(): array
	{
		return $this->globalPatterns;
	}

	/**
	 * Set the rate limit for a route pattern.
	 *
	 * @param string $pattern     The route pattern.
	 * @param int    $maxRequests Requests allowed per window.
	 * @param int    $perSeconds  Window length in seconds.
	 *
	 * @return void
	 */
	public function setRateLimit(string $pattern, int $maxRequests, int $perSeconds): void
	{
		$this->rateLimits[$pattern] = ['max' => $maxRequests, 'per' => $perSeconds];
	}

	/**
	 * The rate limit on a route pattern, if any.
	 *
	 * @param string $pattern The route pattern.
	 *
	 * @return array{max: int, per: int}|null
	 */
	public function rateLimit(string $pattern): ?array
	{
		return $this->rateLimits[$pattern] ?? null;
	}

	/**
	 * Set the throttle configuration for a route pattern.
	 *
	 * @param string $pattern     The route pattern.
	 * @param int    $maxRequests Requests allowed per window.
	 * @param int    $perSeconds  Window length in seconds.
	 * @param int    $burst       Burst allowance above the steady rate.
	 *
	 * @return void
	 */
	public function setThrottle(string $pattern, int $maxRequests, int $perSeconds, int $burst): void
	{
		$this->throttleConfigs[$pattern] = ['max' => $maxRequests, 'per' => $perSeconds, 'burst' => $burst];
	}

	/**
	 * The throttle configuration on a route pattern, if any.
	 *
	 * @param string $pattern The route pattern.
	 *
	 * @return array{max: int, per: int, burst: int}|null
	 */
	public function throttle(string $pattern): ?array
	{
		return $this->throttleConfigs[$pattern] ?? null;
	}

	/**
	 * How many patterns carry a rate limit.
	 *
	 * @return int
	 */
	public function rateLimitCount(): int
	{
		return count($this->rateLimits);
	}

	/**
	 * How many patterns carry a throttle configuration.
	 *
	 * @return int
	 */
	public function throttleCount(): int
	{
		return count($this->throttleConfigs);
	}

	#endregion
}
