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

use Closure;
use Clover\Classes\BaseClass;
use Clover\Classes\Data\ArrayObject;
use Clover\Classes\DependencyInjection\Container;
use Clover\Classes\Event\EventDispatcherAdapter;
use Clover\Classes\HTTP\Request as HTTPRequest;
use Clover\Classes\Reflection\Handler as ReflectionHandler;
use Clover\Classes\Routing\Route as RouteObject;
use Clover\Enumeration\HTTPRequestMethod;
use Clover\Framework\Component\Response;
use function in_array;

#endregion

/**
 * Turns a request into a result: maintenance gate, before hooks, method match,
 * route dispatch, after hooks, and the not-found and method-not-allowed
 * fallbacks.
 *
 * The collection is passed in per call rather than held, because Router::reset()
 * installs a fresh collection and Router::__clone() copies one; holding a
 * reference here would let a dispatcher keep answering from the collection its
 * router had already replaced.
 *
 * @package Clover\Classes\Routing
 */
class RouteDispatcher extends BaseClass
{
	#region properties

	/**
	 * Hooks run before matching. Returning false aborts routing; returning a
	 * Response answers the request.
	 *
	 * @var Closure[]
	 */
	private array $beforeHooks = [];

	/**
	 * Hooks run after a route produced a result, able to replace it.
	 *
	 * @var Closure[]
	 */
	private array $afterHooks = [];

	/** @var string|Closure|null $notFoundHandler Handler for a request no route matched. */
	private string|null|Closure $notFoundHandler = null;

	/** @var string|Closure|null $methodNotAllowedHandler Handler for a path that exists under other methods. */
	private string|null|Closure $methodNotAllowedHandler = null;

	/** @var bool $maintenanceMode Whether every request is being refused. */
	private bool $maintenanceMode = false;

	/** @var string[] $maintenanceBypassIPs Clients allowed through while in maintenance. */
	private array $maintenanceBypassIPs = [];

	/** @var Closure|null $maintenanceHandler What to answer with while in maintenance. */
	private ?Closure $maintenanceHandler = null;

	/** @var HTTPRequestMethod|string|null $method The method of the request last dispatched. */
	private null|string|HTTPRequestMethod $method = null;

	#endregion

	/**
	 * @param EventDispatcherAdapter $kernelEventDispatcher Kernel span dispatcher handed to route executors.
	 */
	public function __construct(private readonly EventDispatcherAdapter $kernelEventDispatcher)
	{
	}

	#region hooks and handlers

	/**
	 * Register a hook to run before matching.
	 *
	 * @param Closure $hook Receives the method and the path segments.
	 *
	 * @return void
	 */
	public function addBeforeHook(Closure $hook): void
	{
		$this->beforeHooks[] = $hook;
	}

	/**
	 * Register a hook to run after a route produced a result.
	 *
	 * @param Closure $hook Receives the method, the path segments and the result.
	 *
	 * @return void
	 */
	public function addAfterHook(Closure $hook): void
	{
		$this->afterHooks[] = $hook;
	}

	/**
	 * How many before hooks are registered.
	 *
	 * @return int
	 */
	public function beforeHookCount(): int
	{
		return count($this->beforeHooks);
	}

	/**
	 * How many after hooks are registered.
	 *
	 * @return int
	 */
	public function afterHookCount(): int
	{
		return count($this->afterHooks);
	}

	/**
	 * Set the handler for a request no route matched.
	 *
	 * @param string|Closure|null $handler The handler, or null to remove it.
	 *
	 * @return void
	 */
	public function setNotFoundHandler(string|Closure|null $handler): void
	{
		$this->notFoundHandler = $handler;
	}

	/**
	 * Set the handler for a path that exists under other methods.
	 *
	 * @param string|Closure|null $handler The handler, or null to remove it.
	 *
	 * @return void
	 */
	public function setMethodNotAllowedHandler(string|Closure|null $handler): void
	{
		$this->methodNotAllowedHandler = $handler;
	}

	/**
	 * Whether a not-found handler is registered.
	 *
	 * @return bool
	 */
	public function hasNotFoundHandler(): bool
	{
		return $this->notFoundHandler !== null;
	}

	/**
	 * The method of the request last dispatched.
	 *
	 * @return HTTPRequestMethod|string|null
	 */
	public function method(): null|string|HTTPRequestMethod
	{
		return $this->method;
	}

	#endregion

	#region maintenance

	/**
	 * Refuse every request except from the listed clients.
	 *
	 * @param string[]     $bypassIPs Clients allowed through.
	 * @param Closure|null $handler   What to answer with, or null for a default 503.
	 *
	 * @return void
	 */
	public function enableMaintenance(array $bypassIPs, ?Closure $handler): void
	{
		$this->maintenanceMode = true;
		$this->maintenanceBypassIPs = $bypassIPs;
		$this->maintenanceHandler = $handler;
	}

	/**
	 * Serve requests again.
	 *
	 * @return void
	 */
	public function disableMaintenance(): void
	{
		$this->maintenanceMode = false;
		$this->maintenanceBypassIPs = [];
		$this->maintenanceHandler = null;
	}

	/**
	 * Whether requests are being refused.
	 *
	 * @return bool
	 */
	public function inMaintenance(): bool
	{
		return $this->maintenanceMode;
	}

	#endregion

	#region dispatch

	/**
	 * Dispatch the current request.
	 *
	 * @param RouteCollection $routes      The routes to match against.
	 * @param Container|null  $container   Container handed to matched routes, if the router has one.
	 * @param array<int, mixed> $middlewares Router-level middlewares to push onto the matched route.
	 *
	 * @return mixed The route result, a Response, or false when nothing matched.
	 */
	public function dispatch(RouteCollection $routes, ?Container $container, array $middlewares): mixed
	{
		if ($this->maintenanceMode && !$this->isBypassed()) {
			if ($this->maintenanceHandler !== null) {
				return ($this->maintenanceHandler)();
			}

			http_response_code(503);

			return ['error' => 'Service Unavailable', 'code' => 503];
		}

		$this->method = HTTPRequest::getMethod();
		$urlPathSegments = HTTPRequest::getUrlPathSegments();
		$host = HTTPRequest::getHttpHost();
		$contentType = HTTPRequest::getContentType();

		$hookResult = $this->runBeforeHooks($urlPathSegments);

		if ($hookResult !== null) {
			return $hookResult;
		}

		/** When the method is not contained in the added routes. */
		if (!$routes->hasMethod($this->method)) {
			$allowedMethods = $this->allowedMethodsFor($routes, $urlPathSegments);

			if (!empty($allowedMethods)) {
				return $this->refuseMethod($allowedMethods, $container);
			}

			return false;
		}

		$route = $this->findMatch($routes, $urlPathSegments, $host, $contentType);

		if ($route === null) {
			return $this->handleNotFound($container);
		}

		if ($container !== null) {
			$route->setContainer($container);
		}

		// Unconditional, matching the isset() guard this replaced: that guard sat on
		// a typed array property with an [] default, so it never once evaluated false.
		$route->setMiddlewares($middlewares);

		return $this->runAfterHooks($urlPathSegments, $route->handle());
	}


	/**
	 * Whether this client is on the maintenance bypass list.
	 *
	 * The address compared is REMOTE_ADDR, the peer the server is actually
	 * talking to. The list used to be matched against
	 * HTTPRequest::getClientIP(), which reads only the HTTP_CLIENT_IP request
	 * header: that header is absent on an ordinary request, so no bypass entry
	 * could ever match, and it is set by the client, so anyone who guessed an
	 * address on the list could walk straight through a closed site.
	 *
	 * @return bool
	 */
	private function isBypassed(): bool
	{
		if ($this->maintenanceBypassIPs === []) {
			return false;
		}

		return in_array((string) HTTPRequest::getRemoteIPAddress(), $this->maintenanceBypassIPs, true);
	}

	/**
	 * Run the before hooks.
	 *
	 * Returning false aborts routing and falls through to the next middleware;
	 * returning a Response answers the request here, which is how the redirect
	 * hooks stop without ending the process.
	 *
	 * @param ArrayObject|array<int, mixed> $urlPathSegments The request path segments.
	 *
	 * @return mixed False, a Response, or null to carry on matching.
	 */
	private function runBeforeHooks(ArrayObject|array $urlPathSegments): mixed
	{
		foreach ($this->beforeHooks as $hook) {
			$hookResult = $hook($this->method, $urlPathSegments);

			if ($hookResult === false) {
				return false;
			}

			if ($hookResult instanceof Response) {
				return $hookResult;
			}
		}

		return null;
	}

	/**
	 * The first candidate route that matches the request, or null when none does.
	 *
	 * @param RouteCollection               $routes          The routes to match against.
	 * @param ArrayObject|array<int, mixed> $urlPathSegments The request path segments.
	 * @param string                        $host            The request host.
	 * @param string                        $contentType     The request content type.
	 *
	 * @return RouteObject|null
	 */
	private function findMatch(
		RouteCollection $routes,
		ArrayObject|array $urlPathSegments,
		string $host,
		string $contentType
	): ?RouteObject {
		foreach ($routes->candidatesFor($this->method, $urlPathSegments) as $route) {
			/** @var RouteObject $route */
			$route = self::setBaseProxy($route);

			if ($route->match($urlPathSegments, $host, $contentType)) {
				return $route;
			}
		}

		return null;
	}

	/**
	 * Let the after hooks replace the result.
	 *
	 * @param ArrayObject|array<int, mixed> $urlPathSegments The request path segments.
	 * @param mixed                         $result          The route result.
	 *
	 * @return mixed
	 */
	private function runAfterHooks(ArrayObject|array $urlPathSegments, mixed $result): mixed
	{
		foreach ($this->afterHooks as $hook) {
			$hookResult = $hook($this->method, $urlPathSegments, $result);

			if ($hookResult !== null) {
				$result = $hookResult;
			}
		}

		return $result;
	}

	/**
	 * The methods that do have a route for this URL.
	 *
	 * @param RouteCollection               $routes      The routes to search.
	 * @param ArrayObject|array<int, mixed> $urlSegments The request path segments.
	 *
	 * @return string[]
	 */
	public function allowedMethodsFor(RouteCollection $routes, ArrayObject|array $urlSegments): array
	{
		$allowed = [];
		$host = HTTPRequest::getHttpHost();
		$contentType = HTTPRequest::getContentType();

		foreach ($routes->all() as $method => $_routes) {
			foreach ($routes->candidatesFor((string) $method, $urlSegments) as $route) {
				$routeClone = clone $route;

				if ($routeClone->match($urlSegments, $host, $contentType)) {
					$allowed[] = $method;
					break;
				}
			}
		}

		return $allowed;
	}

	/**
	 * Answer a request whose path exists under other methods.
	 *
	 * @param string[]       $allowedMethods The methods that would have matched.
	 * @param Container|null $container      Container handed to a class-string handler.
	 *
	 * @return mixed
	 */
	private function refuseMethod(array $allowedMethods, ?Container $container): mixed
	{
		http_response_code(405);
		header('Allow: ' . implode(', ', $allowedMethods));

		if ($this->methodNotAllowedHandler !== null) {
			$handler = $this->methodNotAllowedHandler;

			if ($handler instanceof Closure) {
				return $handler($allowedMethods);
			}

			[$class, $method] = ReflectionHandler::getCallMethodFromString($handler);
			$executor = new RouteExecutor($class, $method, $handler, [$allowedMethods], $container, $this->kernelEventDispatcher);

			return $executor->__invoke([]);
		}

		return ['error' => 'Method Not Allowed', 'code' => 405, 'allowed' => $allowedMethods];
	}

	/**
	 * Answer a request no route matched.
	 *
	 * @param Container|null $container Container handed to the handler.
	 *
	 * @return mixed The handler result, or false when there is no handler.
	 */
	private function handleNotFound(?Container $container): mixed
	{
		if ($this->notFoundHandler == null) {
			return false;
		}

		$callback = $this->notFoundHandler;

		// setNotFoundHandler() accepts `string|Closure|null`, but a closure has no
		// class or method to look up: resolving one through the reflection helper
		// threw "Target class is empty", so the closure form the signature
		// advertises never worked. RouteExecutor takes nulls for exactly this.
		if ($callback instanceof Closure) {
			$executor = new RouteExecutor(null, null, $callback, [], $container, $this->kernelEventDispatcher);

			return $executor->__invoke([]);
		}

		[$class, $method] = ReflectionHandler::getCallMethodFromString($callback);
		$executor = new RouteExecutor($class, $method, $callback, [], $container, $this->kernelEventDispatcher);

		return $executor->__invoke([]);
	}

	#endregion
}
