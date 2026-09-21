<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Component\Routing;

use Closure;
use Clover\Component\Http\Request;
use Clover\Component\Http\Response;
use Clover\Contract\ContainerInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;
use function array_key_exists;
use function class_exists;
use function count;
use function implode;
use function is_array;
use function is_object;
use function is_string;
use function preg_match;
use function preg_quote;
use function sprintf;
use function str_starts_with;
use function strtolower;
use function strtoupper;

/**
 * Router Class
 *
 * Handles HTTP request routing.
 * Maps URIs and methods to specific actions or controllers.
 *
 * Matching and running are separate operations - {@see self::match()} then {@see self::runRoute()} -
 * because the kernel has to read a route's middleware before the action runs, and a middleware that
 * short-circuits must be able to stop the action running at all. {@see self::dispatch()} does both
 * in one call for callers that want no pipeline.
 */
class Router
{
	/**
	 * @var array<string, array<int, Route>> The registered routes, keyed by HTTP method.
	 */
	protected array $routes = [];

	/**
	 * @var ContainerInterface|null Used to build controller instances, when one was given.
	 */
	protected ?ContainerInterface $container;

	/**
	 * @param ContainerInterface|null $container Container used to resolve controller classes. Without
	 *                                          one, controllers are constructed directly and cannot
	 *                                          receive dependencies.
	 */
	public function __construct(?ContainerInterface $container = null)
	{
		$this->container = $container;
	}

	/**
	 * Set the container used to resolve controllers.
	 *
	 * Registration happens before the container has finished filling, so a router built early can
	 * be given its container later without being rebuilt.
	 *
	 * @param ContainerInterface $container The container to resolve through.
	 *
	 * @return void
	 */
	public function setContainer(ContainerInterface $container): void
	{
		$this->container = $container;
	}

	/**
	 * Register a GET route.
	 *
	 * @param string $uri The URI pattern.
	 * @param callable|array $action The action to execute.
	 *
	 * @return Route The registered route, so middleware and a name can be added to it.
	 */
	public function get(string $uri, callable|array $action): Route
	{
		return $this->addRoute('GET', $uri, $action);
	}

	/**
	 * Register a POST route.
	 *
	 * @param string $uri The URI pattern.
	 * @param callable|array $action The action to execute.
	 *
	 * @return Route The registered route, so middleware and a name can be added to it.
	 */
	public function post(string $uri, callable|array $action): Route
	{
		return $this->addRoute('POST', $uri, $action);
	}

	/**
	 * Register a PUT route.
	 *
	 * @param string $uri The URI pattern.
	 * @param callable|array $action The action to execute.
	 *
	 * @return Route The registered route, so middleware and a name can be added to it.
	 */
	public function put(string $uri, callable|array $action): Route
	{
		return $this->addRoute('PUT', $uri, $action);
	}

	/**
	 * Register a PATCH route.
	 *
	 * @param string $uri The URI pattern.
	 * @param callable|array $action The action to execute.
	 *
	 * @return Route The registered route, so middleware and a name can be added to it.
	 */
	public function patch(string $uri, callable|array $action): Route
	{
		return $this->addRoute('PATCH', $uri, $action);
	}

	/**
	 * Register a DELETE route.
	 *
	 * @param string $uri The URI pattern.
	 * @param callable|array $action The action to execute.
	 *
	 * @return Route The registered route, so middleware and a name can be added to it.
	 */
	public function delete(string $uri, callable|array $action): Route
	{
		return $this->addRoute('DELETE', $uri, $action);
	}

	/**
	 * Register a HEAD route.
	 *
	 * @param string $uri The URI pattern.
	 * @param callable|array $action The action to execute.
	 *
	 * @return Route The registered route, so middleware and a name can be added to it.
	 */
	public function head(string $uri, callable|array $action): Route
	{
		return $this->addRoute('HEAD', $uri, $action);
	}

	/**
	 * Register an OPTIONS route.
	 *
	 * @param string $uri The URI pattern.
	 * @param callable|array $action The action to execute.
	 *
	 * @return Route The registered route, so middleware and a name can be added to it.
	 */
	public function options(string $uri, callable|array $action): Route
	{
		return $this->addRoute('OPTIONS', $uri, $action);
	}

	/**
	 * Register a route for any standard HTTP method.
	 *
	 * @param string $uri The URI pattern.
	 * @param callable|array $action The action to execute.
	 *
	 * @return array<int, Route> One route per method, so middleware can be applied to each.
	 */
	public function any(string $uri, callable|array $action): array
	{
		$routes = [];

		foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'] as $method) {
			$routes[] = $this->addRoute($method, $uri, $action);
		}

		return $routes;
	}

	/**
	 * Add a route to the router.
	 *
	 * @param string $method The HTTP method (e.g., GET, POST).
	 * @param string $uri The URI pattern.
	 * @param callable|array $action The action to execute.
	 *
	 * @return Route The registered route.
	 */
	protected function addRoute(string $method, string $uri, callable|array $action): Route
	{
		$normalizedMethod = strtoupper($method);
		$route = new Route($normalizedMethod, $this->normalizeRoutePattern($uri), $action);
		$this->routes[$normalizedMethod][] = $route;

		return $route;
	}

	/**
	 * Find the route that answers a request, without running it.
	 *
	 * The kernel calls this so it can build the middleware pipeline from the matched route before
	 * anything runs - a middleware that refuses the request has to be able to stop the action
	 * being reached at all.
	 *
	 * @param Request $request The incoming request.
	 *
	 * @return array{route: Route, parameters: array<string, string|null>}|null The match, or null.
	 */
	public function match(Request $request): ?array
	{
		$method = strtoupper($request->getMethod());
		$uri = $this->normalizeUri($request->getUri());

		$methodsToTry = [$method];
		if ($method === 'HEAD') {
			$methodsToTry[] = 'GET';
		}

		foreach ($methodsToTry as $candidateMethod) {
			$routeMatch = $this->findRoute($candidateMethod, $uri);
			if ($routeMatch !== null) {
				return $routeMatch;
			}
		}

		return null;
	}

	/**
	 * Run a matched route's action.
	 *
	 * @param Route                     $route      The matched route.
	 * @param Request                   $request    The incoming request.
	 * @param array<string, string|null> $parameters Parameters extracted from the URI.
	 *
	 * @return Response
	 */
	public function runRoute(Route $route, Request $request, array $parameters): Response
	{
		return $this->invokeRouteAction($route->getAction(), $request, $parameters);
	}

	/**
	 * Build the response for a request that matched no route.
	 *
	 * Answers 405 with an `Allow` header when the path exists under other methods, and 404 only
	 * when the path itself is unknown - the two are different facts and the client can act on the
	 * difference.
	 *
	 * @param Request $request The unmatched request.
	 *
	 * @return Response
	 */
	public function respondToUnmatchedRequest(Request $request): Response
	{
		$allowedMethods = $this->getAllowedMethods($this->normalizeUri($request->getUri()));

		if ($allowedMethods !== []) {
			return Response::text(
				'Method Not Allowed',
				405,
				['Allow' => implode(', ', $allowedMethods)]
			);
		}

		return Response::text('Not Found', 404);
	}

	/**
	 * Look up a route by name.
	 *
	 * Reads the registered routes rather than a parallel index. A name is given after registration
	 * - `$router->get(...)->name('home')` - so an index would have to be filled by a second pass
	 * that someone has to remember to run, and a route named anywhere else would silently not be
	 * findable. Route tables are small; a wrong answer is expensive and a scan is not.
	 *
	 * @param string $name The route name.
	 *
	 * @return Route|null The route with that name, or null when nothing carries it.
	 */
	public function getRouteByName(string $name): ?Route
	{
		foreach ($this->routes as $routes) {
			foreach ($routes as $route) {
				if ($route->getName() === $name) {
					return $route;
				}
			}
		}

		return null;
	}

	/**
	 * Every registered route, flattened.
	 *
	 * @return array<int, Route>
	 */
	public function getRoutes(): array
	{
		$flattened = [];

		foreach ($this->routes as $routes) {
			foreach ($routes as $route) {
				$flattened[] = $route;
			}
		}

		return $flattened;
	}

	/**
	 * Dispatch an incoming request to the appropriate route action.
	 *
	 * @param Request $request The incoming HTTP request.
	 * @return Response The generated HTTP response.
	 */
	public function dispatch(Request $request): Response
	{
		$routeMatch = $this->match($request);

		if ($routeMatch !== null) {
			return $this->runRoute($routeMatch['route'], $request, $routeMatch['parameters']);
		}

		return $this->respondToUnmatchedRequest($request);
	}

	/**
	 * Find the first matching route for a method and URI.
	 *
	 * @param string $method The HTTP method.
	 * @param string $uri The normalized URI path.
	 *
	 * @return array{route: Route, parameters: array<string, string|null>}|null The matched route and parameters.
	 */
	protected function findRoute(string $method, string $uri): ?array
	{
		foreach ($this->routes[$method] ?? [] as $route) {
			$parameters = $this->matchUri($route->getUri(), $uri);
			if ($parameters !== null) {
				return [
					'route' => $route,
					'parameters' => $parameters,
				];
			}
		}

		return null;
	}

	/**
	 * Get the allowed methods for a URI when path matching succeeds.
	 *
	 * @param string $uri The normalized URI path.
	 *
	 * @return array<int, string> The allowed methods.
	 */
	protected function getAllowedMethods(string $uri): array
	{
		$allowedMethods = [];

		foreach ($this->routes as $method => $routes) {
			foreach ($routes as $route) {
				if ($this->matchUri($route->getUri(), $uri) !== null) {
					$allowedMethods[] = $method;
					break;
				}
			}
		}

		sort($allowedMethods);
		return $allowedMethods;
	}

	/**
	 * Match a request URI against a route pattern and return extracted parameters.
	 *
	 * @param string $routeUri The registered route pattern.
	 * @param string $requestUri The incoming request URI.
	 *
	 * @return array<string, string|null>|null The extracted parameters, or null when the route does not match.
	 */
	protected function matchUri(string $routeUri, string $requestUri): ?array
	{
		if ($routeUri === '/') {
			return $requestUri === '/' ? [] : null;
		}

		$routeSegments = $this->splitUriSegments($routeUri);
		$pattern = '#^';

		foreach ($routeSegments as $segment) {
			$parameter = $this->parseParameterSegment($segment);
			if ($parameter === null) {
				$pattern .= '/' . preg_quote($segment, '#');
				continue;
			}

			if ($parameter['optional']) {
				$pattern .= sprintf('(?:/(?P<%s>[^/]+))?', $parameter['name']);
				continue;
			}

			$pattern .= sprintf('/(?P<%s>[^/]+)', $parameter['name']);
		}

		$pattern .= '$#';

		$matches = [];
		if (preg_match($pattern, $requestUri, $matches) !== 1) {
			return null;
		}

		$parameters = [];
		foreach ($routeSegments as $segment) {
			$parameter = $this->parseParameterSegment($segment);
			if ($parameter === null) {
				continue;
			}

			$parameters[$parameter['name']] = array_key_exists($parameter['name'], $matches) && $matches[$parameter['name']] !== ''
				? $matches[$parameter['name']]
				: null;
		}

		return $parameters;
	}

	/**
	 * Invoke a route action and normalize its return value to a Response.
	 *
	 * @param callable|array $action The route action.
	 * @param Request $request The current request.
	 * @param array<string, string|null> $parameters Route parameters.
	 *
	 * @return Response The normalized response.
	 */
	protected function invokeRouteAction(callable|array $action, Request $request, array $parameters): Response
	{
		$callable = $this->resolveActionCallable($action);
		$arguments = $this->resolveActionArguments($callable, $request, $parameters);
		$result = $callable(...$arguments);

		if ($result instanceof Response) {
			return $result;
		}

		if (is_array($result) || is_object($result)) {
			return Response::json($result);
		}

		return Response::text((string) $result);
	}

	/**
	 * Resolve array controller actions into concrete callables.
	 *
	 * @param callable|array $action The registered route action.
	 *
	 * @return callable The executable callable.
	 */
	protected function resolveActionCallable(callable|array $action): callable
	{
		if (!is_array($action)) {
			return $action;
		}

		if (!isset($action[0], $action[1]) || !is_string($action[1])) {
			throw new RuntimeException('Route action arrays must contain [class|object, method].');
		}

		$target = $action[0];
		if (is_string($target)) {
			$target = $this->makeController($target);
		}

		return [$target, $action[1]];
	}

	/**
	 * Build a controller instance.
	 *
	 * Resolved through the container when there is one, so a controller can declare its
	 * dependencies in its constructor like anything else. Without a container it is constructed
	 * directly, which only works for a controller that needs no arguments - and that restriction
	 * is stated rather than discovered, because `new $class()` on a controller with a required
	 * constructor argument fails somewhere far from the route that caused it.
	 *
	 * @param string $controller Fully-qualified controller class name.
	 *
	 * @throws RuntimeException When the class cannot be built.
	 *
	 * @return object
	 */
	protected function makeController(string $controller): object
	{
		if ($this->container !== null) {
			$resolved = $this->container->make($controller);

			if (!is_object($resolved)) {
				throw new RuntimeException(sprintf(
					'Route controller `%s` did not resolve to an object.',
					$controller
				));
			}

			return $resolved;
		}

		if (!class_exists($controller)) {
			throw new RuntimeException(sprintf('Route controller `%s` does not exist.', $controller));
		}

		$constructor = (new ReflectionClass($controller))->getConstructor();

		if ($constructor !== null && $constructor->getNumberOfRequiredParameters() > 0) {
			throw new RuntimeException(sprintf(
				'Route controller `%s` needs %d constructor argument(s); give the router a container to resolve it.',
				$controller,
				$constructor->getNumberOfRequiredParameters()
			));
		}

		return new $controller();
	}

	/**
	 * Resolve callable arguments from the current request and path parameters.
	 *
	 * @param callable $callable The route callable.
	 * @param Request $request The current request.
	 * @param array<string, string|null> $parameters Route parameters.
	 *
	 * @return array<int, mixed> The resolved argument list.
	 */
	protected function resolveActionArguments(callable $callable, Request $request, array $parameters): array
	{
		$reflection = is_array($callable)
			? new ReflectionMethod($callable[0], $callable[1])
			: new ReflectionFunction(Closure::fromCallable($callable));

		$arguments = [];
		$parameterQueue = array_values($parameters);

		foreach ($reflection->getParameters() as $parameter) {
			$type = $parameter->getType();
			if ($type instanceof ReflectionNamedType && !$type->isBuiltin() && is_a($request, $type->getName())) {
				$arguments[] = $request;
				continue;
			}

			$parameterName = $parameter->getName();
			if (array_key_exists($parameterName, $parameters)) {
				$arguments[] = $this->castRouteValue($parameters[$parameterName], $type);
				continue;
			}

			if ($parameterQueue !== []) {
				$arguments[] = $this->castRouteValue(array_shift($parameterQueue), $type);
				continue;
			}

			if ($parameter->isDefaultValueAvailable()) {
				$arguments[] = $parameter->getDefaultValue();
				continue;
			}

			if ($parameter->allowsNull()) {
				$arguments[] = null;
				continue;
			}

			throw new RuntimeException(sprintf('Unable to resolve route argument "%s".', $parameterName));
		}

		return $arguments;
	}

	/**
	 * Cast a route value to a supported builtin scalar type when possible.
	 *
	 * @param string|null $value The raw route value.
	 * @param \ReflectionType|null $type The target parameter type.
	 *
	 * @return mixed The cast value.
	 */
	protected function castRouteValue(?string $value, ?\ReflectionType $type): mixed
	{
		if (!($type instanceof ReflectionNamedType) || !$type->isBuiltin() || $value === null) {
			return $value;
		}

		$typeName = strtolower($type->getName());
		if ($typeName === 'string') {
			return $value;
		}

		if ($typeName === 'int' && preg_match('/^-?\d+$/', $value) === 1) {
			return (int) $value;
		}

		if ($typeName === 'float' && is_numeric($value)) {
			return (float) $value;
		}

		if ($typeName === 'bool') {
			if ($value === '1' || strtolower($value) === 'true') {
				return true;
			}

			if ($value === '0' || strtolower($value) === 'false') {
				return false;
			}
		}

		return $value;
	}

	/**
	 * Normalize URIs so registration and dispatch use the same format.
	 *
	 * @param string $uri The raw URI value.
	 *
	 * @return string The normalized URI.
	 */
	protected function normalizeUri(string $uri): string
	{
		$path = parse_url($uri, PHP_URL_PATH);
		if (!is_string($path) || $path === '') {
			return '/';
		}

		if (!str_starts_with($path, '/')) {
			$path = '/' . $path;
		}

		if ($path !== '/') {
			$path = rtrim($path, '/');
		}

		return $path === '' ? '/' : $path;
	}

	/**
	 * Normalize a registered route pattern without stripping optional placeholder markers.
	 *
	 * @param string $uri The raw route pattern.
	 *
	 * @return string The normalized route pattern.
	 */
	protected function normalizeRoutePattern(string $uri): string
	{
		$path = $uri;
		if (!str_starts_with($path, '/')) {
			$path = '/' . $path;
		}

		if ($path !== '/') {
			$path = rtrim($path, '/');
		}

		return $path === '' ? '/' : $path;
	}

	/**
	 * Split a normalized URI into path segments.
	 *
	 * @param string $uri The normalized URI.
	 *
	 * @return array<int, string> The URI segments.
	 */
	protected function splitUriSegments(string $uri): array
	{
		$trimmed = trim($uri, '/');
		if ($trimmed === '') {
			return [];
		}

		return explode('/', $trimmed);
	}

	/**
	 * Parse a parameter segment definition from a route segment.
	 *
	 * @param string $segment The route segment.
	 *
	 * @return array{name: string, optional: bool}|null The parsed parameter metadata.
	 */
	protected function parseParameterSegment(string $segment): ?array
	{
		$matches = [];
		if (preg_match('/^\{([A-Za-z_][A-Za-z0-9_]*)(\?)?\}$/', $segment, $matches) !== 1) {
			return null;
		}

		return [
			'name' => $matches[1],
			'optional' => isset($matches[2]) && $matches[2] === '?',
		];
	}
}
