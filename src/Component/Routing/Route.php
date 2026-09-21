<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Component\Routing;

use function array_merge;

/**
 * Route
 *
 * One registered route: the method and pattern it answers, the action that answers it, and the
 * middleware that wrap that action.
 *
 * Returned by every registration call on {@see Router} so the route can be refined where it is
 * declared - `$router->get('/admin', $action)->middleware('auth')->name('admin.home')`.
 *
 * Middleware are held as names, not instances, and resolved by the kernel at dispatch time. A
 * route declared at boot must not force its middleware to be constructed then; some of them need
 * services that are still being registered.
 */
class Route
{
	/**
	 * @var string The HTTP method this route answers, upper case.
	 */
	protected string $method;

	/**
	 * @var string The normalized URI pattern.
	 */
	protected string $uri;

	/**
	 * @var callable|array The action to run.
	 */
	protected $action;

	/**
	 * @var array<int, string> Middleware names, in the order they wrap the action.
	 */
	protected array $middleware = [];

	/**
	 * @var string|null The route's name, if it was given one.
	 */
	protected ?string $name = null;

	/**
	 * @param string         $method The HTTP method, upper case.
	 * @param string         $uri    The normalized URI pattern.
	 * @param callable|array $action The action to run.
	 */
	public function __construct(string $method, string $uri, callable|array $action)
	{
		$this->method = $method;
		$this->uri = $uri;
		$this->action = $action;
	}

	/**
	 * Add middleware to this route.
	 *
	 * Names are resolved by the kernel: an alias from its `$routeMiddleware`, a group from its
	 * `$middlewareGroups`, or a class name.
	 *
	 * @param string|array<int, string> $middleware One name or several.
	 *
	 * @return self
	 */
	public function middleware(string|array $middleware): self
	{
		$this->middleware = array_merge($this->middleware, (array) $middleware);

		return $this;
	}

	/**
	 * Name this route.
	 *
	 * @param string $name The route name.
	 *
	 * @return self
	 */
	public function name(string $name): self
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * The HTTP method this route answers.
	 *
	 * @return string
	 */
	public function getMethod(): string
	{
		return $this->method;
	}

	/**
	 * The normalized URI pattern.
	 *
	 * @return string
	 */
	public function getUri(): string
	{
		return $this->uri;
	}

	/**
	 * The action to run.
	 *
	 * @return callable|array
	 */
	public function getAction(): callable|array
	{
		return $this->action;
	}

	/**
	 * The middleware names wrapping this route.
	 *
	 * @return array<int, string>
	 */
	public function getMiddleware(): array
	{
		return $this->middleware;
	}

	/**
	 * The route's name, or null when it has none.
	 *
	 * @return string|null
	 */
	public function getName(): ?string
	{
		return $this->name;
	}
}
