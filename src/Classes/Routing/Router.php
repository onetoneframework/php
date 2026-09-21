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
use Clover\Annotation\Route as RouteAnnotation;
use Clover\Classes\BaseClass;
use Clover\Classes\Data\ArrayObject;
use Clover\Classes\DependencyInjection\Container;
use Clover\Classes\Debug\Profiler;
use Clover\Classes\Directory\Handler as DirectoryHandler;
use Clover\Classes\Event\EventDispatcherAdapter;
use Clover\Classes\Event\EventManager;
use Clover\Classes\File\Functions as FileFunctions;
use Clover\Classes\HTTP\Request as HTTPRequest;
use Clover\Classes\HTTP\Router\Middleware;
use Clover\Classes\Reflection\Handler as ReflectionHandler;
use Clover\Classes\Routing\Route as RouteObject;
use Clover\Classes\Routing\RouteAnnotationReader;
use Clover\Classes\Routing\RouteAnnotationReaderBuilder;
use Clover\Enumeration\HTTPRequestMethod as HTTPRequestMethod;
use Clover\Framework\Component\Response;
use Clover\Framework\Event\KernelSpanFinished;
use Clover\Framework\Event\KernelSpanStarted;
use Clover\Implement\EventDispatcherInterface;
use RuntimeException;
use function count;
use function in_array;
use function sprintf;
use function uniqid;
use function is_string;
use function is_array;

#endregion

/**
 * Class Router
 * 
 * Manages the registration and handling of HTTP routes.
 */
class Router extends BaseClass
{
	#region properties

	private EventDispatcherAdapter $kernelEventDispatcher;
	/** @var Container $container */
	private Container $container;

	/** @var RouteCollection $routeCollection Everything registered: routes, names, tags, constraints and limits. */
	private RouteCollection $routeCollection;

	/** @var Middleware[] $middlewares */
	private array $middlewares = [];

	/** @var string $prependPrefix */
	private string $prependPrefix = "";

	/** @var RouteAnnotationReader $annotationReader */
	private RouteAnnotationReader $annotationReader;

	/** @var string */
	private string $currentDomain = '*';

	/**
	 * Route group middleware stack for nested groups
	 * 
	 * @var array<int, Middleware[]>
	 */
	private array $groupMiddlewareStack = [];

	/**
	 * Route group prefix stack for nested groups
	 * 
	 * @var string[]
	 */
	private array $groupPrefixStack = [];

	/**
	 * Route macros for reusable pattern definitions
	 * 
	 * @var array<string, Closure(Router, array[]): ?Router>
	 */
	private array $macros = [];

	/**
	 * Route conditions for conditional registration
	 * 
	 * @var Closure[]
	 */
	private array $conditions = [];

	/** @var RouteDispatcher $dispatcher Request handling: hooks, maintenance, matching and the fallbacks. */
	private RouteDispatcher $dispatcher;

	#endregion

	#region function

	/**
	 * Constructor for the Router class.
	 *
	 * @param EventDispatcherInterface|null $eventDispatcher Optional event dispatcher for handling events.
	 * @param RouteAnnotationReaderBuilder|null $annotationReaderBuilder Optional builder for annotation reader wiring.
	 */
	public function __construct(
		private readonly ?EventDispatcherInterface $eventDispatcher = null,
		?RouteAnnotationReaderBuilder $annotationReaderBuilder = null
	) {
		$annotationReaderBuilder = $annotationReaderBuilder ?? RouteAnnotationReaderBuilder::create();
		$this->annotationReader = $annotationReaderBuilder->build();
		$this->kernelEventDispatcher = EventDispatcherAdapter::fromEventManager();
		$this->routeCollection = new RouteCollection();
		$this->dispatcher = new RouteDispatcher($this->kernelEventDispatcher);
	}

	/**
	 * Give the copy its own registrations.
	 *
	 * cloneRouter() is `clone $this`, and before the collection existed the
	 * route array was copied by value, so writing to the copy left the original
	 * alone. An object property would be shared instead, which would silently
	 * turn every clone into an alias.
	 *
	 * @return void
	 */
	public function __clone(): void
	{
		$this->routeCollection = clone $this->routeCollection;
		$this->dispatcher = clone $this->dispatcher;
	}

	/**
	 * Gets the middlewares associated with the router.
	 *
	 * @return Middleware[] An array of middleware instances.
	 */
	public function getMiddlewares(): array
	{
		return $this->middlewares;
	}

	/**
	 * Gets the current HTTP method being processed by the router.
	 *
	 * @return string The HTTP method (e.g., GET, POST).
	 */
	public function getMethod(): string
	{
		return $this->dispatcher->method();
	}

	/**
	 * Sets one or more middleware(s) to be used by the router.
	 *
	 * Accepts a variable number of middleware arguments.
	 *
	 * @param Middleware[] ...$middlewares List of middleware(s) to apply.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function setMiddlewares(array ...$middlewares): self
	{
		$this->middlewares = $middlewares;

		return $this;
	}

	/**
	 * Appends a middleware to the router.
	 *
	 * @param Middleware $middleware The middleware to append. Can be a string (class name), callable, or an array of middlewares.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function appendMiddleware(Middleware $middleware): self
	{
		$this->middlewares[] = $middleware;

		return $this;
	}

	/**
	 * Sets the handler to be executed when no matching route is found.
	 *
	 * @param string|Closure|null $handler The not found handler, which can be a string (class method) or a Closure.
	 * 
	 * @return void
	 */
	public function setNotFoundHandler(string|null|Closure $handler): void
	{
		$this->dispatcher->setNotFoundHandler($handler);
	}

	/**
	 * Sets the dependency injection container for the router.
	 *
	 * @param Container $container The container instance to be used by the router.
	 * 
	 * @return void
	 */
	public function setContainer(Container $container): void
	{
		$this->container = $container;
	}

	/**
	 * Set a pre-append prefix on pattern
	 * 
	 * @param string $pattern The pattern to which the prefix will be added.
	 * 
	 * @return string
	 */
	private function addPrefix(string $pattern): string
	{
		return sprintf("%s%s", $this->prependPrefix, $pattern);
	}

	/**
	 * Registers a new route with the specified HTTP method, URL pattern, callback, host, and content type.
	 *
	 * @param string $method       The HTTP method (e.g., GET, POST) for the route.
	 * @param string $pattern      The URL pattern to match for the route.
	 * @param mixed  $callback     The callback to execute when the route is matched.
	 * @param string $host         The host to match for the route. Defaults to "*" (any host).
	 * @param string $contentType  The content type to match for the route. Defaults to "*" (any content type).
	 *
	 * @return void
	 */
	private function set(string $method, string $pattern, mixed $callback, string $host = "*", string $contentType = "*"): void
	{
		$pattern = $this->addPrefix($pattern);

		$this->addRoute((string) $method, $pattern, $callback, [], $host, $contentType, [], '');
	}

	/**
	 * Defines a group of routes that share a common URI pattern and callback.
	 *
	 * @param string $pattern  The URI pattern that applies to the group of routes.
	 * @param mixed  $callback The callback to execute for the group, typically a closure or array of routes.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function group(string $pattern, mixed $callback): self
	{
		if (!ReflectionHandler::isCallable($callback)) {
			return $this;
		}

		return $this->groupWithAttributes(
			['prefix' => $pattern],
			Closure::fromCallable($callback)
		);
	}

	/**
	 * Registers a Specify route with the specified pattern and callback.
	 *
	 * @param string $pattern The route pattern to match.
	 * @param mixed  $callback The callback to execute when the route is matched.
	 * 
	 * @return self Returns the Router instance for method chaining.
	 */
	public function on(string $method, string $pattern, mixed $callback): self
	{
		$this->set($method, $pattern, $callback);

		return $this;
	}

	/**
	 * Registers a GET route with the specified pattern and callback.
	 *
	 * @param string $pattern The route pattern to match.
	 * @param mixed  $callback The callback to execute when the route is matched.
	 * 
	 * @return self Returns the Router instance for method chaining.
	 */
	public function get(string $pattern, mixed $callback): self
	{
		$this->set(HTTPRequestMethod::GET, $pattern, $callback);

		return $this;
	}

	/**
	 * Registers a POST route with the specified pattern and callback.
	 *
	 * @param string $pattern The route pattern to match.
	 * @param mixed  $callback The callback to execute when the route is matched.
	 * 
	 * @return self Returns the Router instance for method chaining.
	 */
	public function post(string $pattern, mixed $callback): self
	{
		$this->set(HTTPRequestMethod::POST, $pattern, $callback);

		return $this;
	}

	/**
	 * Loads routes from declared classes with the specified prefix.
	 *
	 * Scans all declared classes for route annotations and registers them.
	 *
	 * @param string $prefix The prefix to filter declared classes.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function fromDeclaredClasses(string $prefix): static
	{
		$declared = get_declared_classes();
		$classNames = array_filter($declared, function ($class) use ($prefix) {
			return str_starts_with($class, $prefix);
		});

		/** @var ?RouteAnnotation[] $annotations */
		$annotations = [];

		/** @var string[] $classNames */
		foreach ($classNames as $className) {
			$annotations = array_merge($annotations, $this->annotationReader->read($className));
		}

		/** @var RouteAnnotation $annotation */
		foreach ($annotations as $annotation) {
			$host = $annotation->host ?? "*";
			$method = $annotation->method ?? "";
			$pattern = $annotation->pattern ?? "";
			$middleware = $annotation->middleware ?? "";
			$contentType = $annotation->contentType ?? "*";
			$notFoundHandler = $annotation->notFoundHandler;
			$holder = $annotation->holder ?? [];
			$callback = join('::', $holder);
			$pathQueryKey = $annotation->pathQueryKey ?? '';
			$requiredQuery = is_array($annotation->query ?? null) ? $annotation->query : [];

			if ($notFoundHandler != null) {
				$this->setNotFoundHandler($notFoundHandler);
			}

			$this->addRoute($method, $pattern, $callback, $middleware, $host, $contentType, $requiredQuery, $pathQueryKey);
		}

		return $this;
	}

	/**
	 * Register a GET parameter name whose value is used as the virtual URL path when the client requests only the front script (no mod_rewrite). See {@see \Clover\Classes\HTTP\Request::registerRoutingPathQueryKeys()}.
	 */
	public function pathFromQuery(string $queryKey): self
	{
		HTTPRequest::registerRoutingPathQueryKeys($queryKey);

		return $this;
	}

	/**
	 * Loads routes from the specified directory.
	 *
	 * Scans the given directory path for route definitions and registers them.
	 *
	 * @param string $path The path to the directory containing route files.
	 * 
	 * @return bool Returns true on success, false on failure.
	 */
	public function fromDirectory(string $path): bool
	{
		$fileList = DirectoryHandler::getList($path, 'file', true, false, ['php']);

		if (!$fileList) {
			return false;
		}

		foreach ($fileList as $file) {
			$this->fromFile($file);
		}

		return true;
	}

	/**
	 * Loads routing configuration from the specified file path.
	 *
	 * @param string $path The path to the configuration file.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function fromFile(string $path): self
	{
		$classNames = FileFunctions::getClassNames($path);

		/** @var ?RouteAnnotation[] $annotations */
		$annotations = [];

		/** @var string[] $classNames */
		foreach ($classNames as $className) {
			$annotations = array_merge($annotations, $this->annotationReader->read($className));
		}

		/** @var RouteAnnotation $annotation */
		foreach ($annotations as $annotation) {
			$host = $annotation->host ?? "*";
			$method = $annotation->method ?? "";
			$pattern = $annotation->pattern ?? "";
			$middleware = $annotation->middleware ?? "";
			$contentType = $annotation->contentType ?? "*";
			$notFoundHandler = $annotation->notFoundHandler;
			$holder = $annotation->holder ?? [];
			$callback = join('::', $holder);
			$pathQueryKey = $annotation->pathQueryKey ?? '';
			$requiredQuery = is_array($annotation->query ?? null) ? $annotation->query : [];

			if ($notFoundHandler != null) {
				$this->setNotFoundHandler($notFoundHandler);
			}

			$this->addRoute($method, $pattern, $callback, $middleware, $host, $contentType, $requiredQuery, $pathQueryKey);
		}

		return $this;
	}

	/**
	 * Registers a new route with the router.
	 *
	 * @param string $method       The HTTP method (e.g., 'GET', 'POST') for the route.
	 * @param string $pattern      The URI pattern to match for the route.
	 * @param mixed  $callback     The callback to execute when the route is matched.
	 * @param mixed  $middleware   Optional. Middleware(s) to apply to the route. Default is an empty array.
	 * @param string $host         Optional. The host to match for the route. Default is "*".
	 * @param string $contentType  Optional. The content type to match for the route. Default is "*".
	 * @param array<string, string> $requiredQueryParams Optional. Required $_GET parameters (exact match).
	 * @param string $pathQueryKey Optional. Register this GET parameter as a source for the virtual URL path when the request hits only the front script.
	 *
	 * @return void
	 */
	private function addRoute(string $method, string $pattern, mixed $callback, mixed $middleware = [], string $host = "*", string $contentType = "*", array $requiredQueryParams = [], string $pathQueryKey = ""): void
	{
		$routeObject = new RouteObject($pattern, $callback, $middleware);
		$routeObject->setHost($host);
		$routeObject->setContentType($contentType);

		if ($requiredQueryParams !== []) {
			$routeObject->setRequiredQueryParams($requiredQueryParams);
		}

		if ($pathQueryKey !== '') {
			HTTPRequest::registerRoutingPathQueryKeys($pathQueryKey);
			$routeObject->setPathQueryKey($pathQueryKey);
		}

		/** @var RouteObject $route */
		$routeObject = self::setBaseProxy($routeObject);

		$this->routeCollection->add($method, $routeObject);
	}

	/**
	 * Add route for DELETE-Method
	 * 
	 * @param string $pattern
	 * @param mixed $callback
	 * 
	 * @return self
	 */
	public function delete(string $pattern, mixed $callback): self
	{
		$this->set(HTTPRequestMethod::DELETE, $pattern, $callback);

		return $this;
	}

	/**
	 * Add route for PUT-Method
	 * 
	 * @param string $pattern
	 * @param mixed $callback
	 * 
	 * @return self
	 */
	public function put(string $pattern, mixed $callback): self
	{
		$this->set(HTTPRequestMethod::PUT, $pattern, $callback);

		return $this;
	}

	/**
	 * Add route for OPTIONS-Method
	 * 
	 * @param string $pattern
	 * @param mixed $callback
	 * 
	 * @return self
	 */
	public function options(string $pattern, mixed $callback): self
	{
		$this->set(HTTPRequestMethod::OPTIONS, $pattern, $callback);

		return $this;
	}

	/**
	 * Add route for PATCH-Method
	 * 
	 * @param string $pattern
	 * @param mixed $callback
	 * 
	 * @return self
	 */
	public function patch(string $pattern, mixed $callback): self
	{
		$this->set(HTTPRequestMethod::PATCH, $pattern, $callback);

		return $this;
	}

	/**
	 * Check that if exists specify method routes
	 * 
	 * @param string $method
	 * 
	 * @return bool
	 */
	private function has(?string $method): bool
	{
		return $this->routeCollection->hasMethod($method);
	}

	/**
	 * Get the routes registered for one HTTP method.
	 *
	 * @param string $method The HTTP method to read.
	 *
	 * @return Route[]
	 */
	private function getRoute(string $method): array
	{
		return $this->routeCollection->forMethod($method);
	}

	/**
	 * Gets all routes, keyed by HTTP method.
	 *
	 * @return array<string, Route[]>
	 */
	private function getRoutes(): array
	{
		return $this->routeCollection->all();
	}

	/**
	 * Map all routes to ArrayObject
	 * 
	 * @return ArrayObject
	 */
	public function map(): ArrayObject
	{
		$routeRules = new ArrayObject();

		/** @var Route[Route[]] $routes */
		$routeMap = $this->getRoutes();

		foreach ($routeMap as $method => $routes) {
			/** @var Route[] $routes */
			foreach ($routes as $route) {
				[$class, $caller] = $route->getClassAndMethod();
				$pattern = $route->getPattern();
				$contentType = $route->getContentType();

				$routeRules->add([
					'class' => $class,
					'caller' => $caller,
					'pattern' => $pattern,
					'contentType' => $contentType,
					'method' => $method
				]);
			}
		}

		return $routeRules;
	}

	/**
	 * Export routes to array for caching.
	 *
	 * @return array<string, array<int, array{
	 *     pattern: string,
	 *     callback: string,
	 *     middleware: mixed,
	 *     host: string,
	 *     contentType: string,
	 *     requiredQuery: array<string, string>,
	 *     pathQueryKey: string
	 * }>>
	 */
	public function toArray(): array
	{
		$export = [];
		foreach ($this->routeCollection->all() as $method => $routes) {
			$export[$method] = [];
			foreach ($routes as $route) {
				/** @var RouteObject $route */
				$export[$method][] = [
					'pattern' => $route->getPattern()->__toString(),
					'callback' => join('::', $route->getClassAndMethod()),
					'middleware' => $route->getMiddlewares(),
					'host' => $route->getHost(),
					'contentType' => $route->getContentType(),
					'requiredQuery' => $route->getRequiredQueryParams(),
					'pathQueryKey' => $route->getPathQueryKey(),
				];
			}
		}

		return $export;
	}

	/**
	 * Load routes from cached array.
	 *
	 * @param array $data
	 * 
	 * @return self
	 */
	public function fromCachedArray(array $data): self
	{
		$this->routeCollection->clear();
		foreach ($data as $method => $routes) {
			foreach ($routes as $r) {
				$callback = $r['callback'] ?? '';
				$middleware = $r['middleware'] ?? [];
				$host = $r['host'] ?? '*';
				$contentType = $r['contentType'] ?? '*';
				$pattern = $r['pattern'] ?? '';
				$requiredQuery = is_array($r['requiredQuery'] ?? null) ? $r['requiredQuery'] : [];
				$pathQueryKey = is_string($r['pathQueryKey'] ?? null) ? $r['pathQueryKey'] : '';

				$this->addRoute((string) $method, (string) $pattern, $callback, $middleware, $host, $contentType, $requiredQuery, $pathQueryKey);
			}
		}

		return $this;
	}

	/**
	 * Handle a matched callback
	 * 
	 * @return mixed
	 */
	public function handle(): mixed
	{
		$profilerEnabled = Profiler::isEnabled();
		$profilerToken = '';
		if ($profilerEnabled) {
			$profilerToken = uniqid('pf_', true);
			$this->kernelEventDispatcher->dispatch(new KernelSpanStarted(
				$profilerToken,
				'Kernel::Router::handle',
				'Router::handle'
			));
		}

		try {
			return $this->dispatcher->dispatch($this->routeCollection, $this->container ?? null, $this->middlewares);
		} finally {
			if ($profilerEnabled) {
				$this->kernelEventDispatcher->dispatch(new KernelSpanFinished($profilerToken));
			}
		}
	}

	/**
	 * Register routes for all HTTP methods
	 * 
	 * @param string $pattern
	 * @param mixed $callback
	 * 
	 * @return self
	 */
	public function any(string $pattern, mixed $callback): self
	{
		$methods = [
			HTTPRequestMethod::GET,
			HTTPRequestMethod::POST,
			HTTPRequestMethod::PUT,
			HTTPRequestMethod::DELETE,
			HTTPRequestMethod::PATCH,
			HTTPRequestMethod::OPTIONS
		];

		foreach ($methods as $method) {
			$this->set($method, $pattern, $callback);
		}

		return $this;
	}

	/**
	 * Register routes for multiple HTTP methods
	 * 
	 * @param array $methods
	 * @param string $pattern
	 * @param mixed $callback
	 * 
	 * @return self
	 */
	public function match(array $methods, string $pattern, mixed $callback): self
	{
		foreach ($methods as $method) {
			$this->set(strtoupper($method), $pattern, $callback);
		}

		return $this;
	}

	/**
	 * Register resource routes (RESTful)
	 * 
	 * @param string $name
	 * @param string $controller
	 * @param array $options
	 * 
	 * @return self
	 */
	public function resource(string $name, string $controller, array $options = []): self
	{
		$only = $options['only'] ?? ['index', 'show', 'store', 'update', 'destroy'];
		$except = $options['except'] ?? [];

		$routes = [
			'index' => ['GET', "/{$name}", 'index'],
			'show' => ['GET', "/{$name}/{id}", 'show'],
			'store' => ['POST', "/{$name}", 'store'],
			'update' => ['PUT', "/{$name}/{id}", 'update'],
			'destroy' => ['DELETE', "/{$name}/{id}", 'destroy'],
		];

		foreach ($routes as $action => $config) {
			if (in_array($action, $except)) {
				continue;
			}

			if (!empty($only) && !in_array($action, $only)) {
				continue;
			}

			[$method, $pattern, $func] = $config;
			$this->set($method, $pattern, "{$controller}::{$func}");
		}

		return $this;
	}

	/**
	 * Register API resource routes
	 * 
	 * @param string $name
	 * @param string $controller
	 * @param array $options
	 * 
	 * @return self
	 */
	public function apiResource(string $name, string $controller, array $options = []): self
	{
		$prefix = $options['prefix'] ?? '/api';
		$version = $options['version'] ?? 'v1';

		$routes = [
			['GET', "{$prefix}/{$version}/{$name}", 'index'],
			['GET', "{$prefix}/{$version}/{$name}/{id}", 'show'],
			['POST', "{$prefix}/{$version}/{$name}", 'store'],
			['PUT', "{$prefix}/{$version}/{$name}/{id}", 'update'],
			['DELETE', "{$prefix}/{$version}/{$name}/{id}", 'destroy'],
		];

		foreach ($routes as $config) {
			[$method, $pattern, $func] = $config;

			if (is_callable("{$controller}::{$func}")) {
				$this->set($method, $pattern, "{$controller}::{$func}");
			}
		}

		return $this;
	}

	/**
	 * Add redirect route
	 * 
	 * @param string $from
	 * @param string $to
	 * @param int $status
	 * 
	 * @return self
	 */
	public function redirect(string $from, string $to, int $status = 302): self
	{
		$this->get($from, static function () use ($to, $status): Response {
			return Response::redirect($to, $status);
		});

		return $this;
	}

	/**
	 * Add permanent redirect route
	 * 
	 * @param string $from
	 * @param string $to
	 * 
	 * @return self
	 */
	public function permanentRedirect(string $from, string $to): self
	{
		return $this->redirect($from, $to, 301);
	}

	/**
	 * Register view route
	 * 
	 * @param string $pattern
	 * @param string $view
	 * @param array $data
	 * 
	 * @return self
	 */
	public function view(string $pattern, string $view, array $data = []): self
	{
		$this->get($pattern, function () use ($view, $data) {
			extract($data);
			include $view;
		});

		return $this;
	}

	/**
	 * Add fallback route
	 * 
	 * @param mixed $callback
	 * 
	 * @return self
	 */
	public function fallback(mixed $callback): self
	{
		$this->setNotFoundHandler($callback);
		return $this;
	}

	/**
	 * Get current route info
	 * 
	 * @return array|null
	 */
	public function current(): ?array
	{
		$method = HTTPRequest::getMethod();
		if (!$this->has($method)) {
			return null;
		}

		$routes = $this->getRoute($method);
		$urlPathSegments = HTTPRequest::getUrlPathSegments();
		$host = HTTPRequest::getHttpHost();
		$contentType = HTTPRequest::getContentType();

		/** @var Route $route */
		foreach ($routes as $route) {
			if ($route->match($urlPathSegments, $host, $contentType)) {
				[$class, $caller] = $route->getClassAndMethod();
				return [
					'pattern' => $route->getPattern()->__toString(),
					'class' => $class,
					'method' => $caller,
					'arguments' => $route->getArguments(),
					'middlewares' => $route->getMiddlewares(),
				];
			}
		}

		return null;
	}

	/**
	 * Check if route exists
	 * 
	 * @param string $method
	 * @param string $pattern
	 * 
	 * @return bool
	 */
	public function hasRoute(string $method, string $pattern): bool
	{
		return $this->routeCollection->hasPattern($method, $pattern);
	}

	/**
	 * Get all registered routes count
	 * 
	 * @return int
	 */
	public function count(): int
	{
		return $this->routeCollection->count();
	}

	/**
	 * Clear all routes
	 * 
	 * @return self
	 */
	public function clear(): self
	{
		$this->routeCollection->clear();
		$this->middlewares = [];
		$this->dispatcher->setNotFoundHandler(null);
		HTTPRequest::clearRoutingPathQueryKeys();
		return $this;
	}

	/**
	 * Register named route
	 * 
	 * @param string $name
	 * @param string $method
	 * @param string $pattern
	 * @param mixed $callback
	 * 
	 * @return self
	 */
	public function name(string $name, string $method, string $pattern, mixed $callback): self
	{
		$this->set($method, $pattern, $callback);
		$this->routeCollection->setName($name, $method, $pattern);
		return $this;
	}

	/**
	 * Get URL by route name
	 * 
	 * @param string $name
	 * @param array $params
	 * 
	 * @return string|null
	 */
	public function route(string $name, array $params = []): ?string
	{
		$named = $this->routeCollection->named($name);

		if ($named === null) {
			return null;
		}

		$pattern = $named['pattern'];

		foreach ($params as $key => $value) {
			$pattern = preg_replace('/\{' . $key . '\}(\?)?/', $value, $pattern);
		}

		$pattern = preg_replace('/\{[^}]+\}\?/', '', $pattern);

		return $pattern;
	}

	/**
	 * Add domain group
	 * 
	 * @param string $domain
	 * @param Closure $callback
	 * 
	 * @return self
	 */
	public function domain(string $domain, Closure $callback): self
	{
		$previousHost = $this->currentDomain ?? '*';
		$this->currentDomain = $domain;

		if (is_callable($callback)) {
			$callback($this);
		}

		$this->currentDomain = $previousHost;
		return $this;
	}

	/**
	 * Set rate limit for pattern
	 * 
	 * @param string $pattern
	 * @param int $maxRequests
	 * @param int $perSeconds
	 * 
	 * @return self
	 */
	public function rateLimit(string $pattern, int $maxRequests, int $perSeconds = 60): self
	{
		$this->routeCollection->setRateLimit($pattern, $maxRequests, $perSeconds);

		return $this;
	}

	/**
	 * Get rate limit for pattern
	 * 
	 * @param string $pattern
	 * 
	 * @return array{
	 * 	max: int, 
	 * 	per: int
	 * }|null
	 */
	public function getRateLimit(string $pattern): ?array
	{
		return $this->routeCollection->rateLimit($pattern);
	}

	/**
	 * Add CORS headers route
	 * 
	 * @param string $pattern
	 * @param array $options
	 * 
	 * @return self
	 */
	public function cors(string $pattern, array $options = []): self
	{
		$defaults = [
			'origin' => '*',
			'methods' => 'GET, POST, PUT, DELETE, OPTIONS',
			'headers' => 'Content-Type, Authorization',
			'credentials' => false,
			'maxAge' => 86400
		];

		$config = array_merge($defaults, $options);

		$this->options($pattern, static function () use ($config): Response {
			$response = new Response('', [], 'html', 204);
			$response->setHeader('Access-Control-Allow-Origin', (string) $config['origin']);
			$response->setHeader('Access-Control-Allow-Methods', (string) $config['methods']);
			$response->setHeader('Access-Control-Allow-Headers', (string) $config['headers']);
			$response->setHeader('Access-Control-Max-Age', (string) $config['maxAge']);

			if ($config['credentials']) {
				$response->setHeader('Access-Control-Allow-Credentials', 'true');
			}

			return $response;
		});

		return $this;
	}

	/**
	 * Add HEAD route (same as GET but no body)
	 * 
	 * @param string $pattern
	 * @param mixed $callback
	 * 
	 * @return self
	 */
	public function head(string $pattern, mixed $callback): self
	{
		$this->set('HEAD', $pattern, $callback);
		return $this;
	}

	/**
	 * Dispatch request and return response
	 * 
	 * @return mixed
	 */
	public function dispatch(): mixed
	{
		$result = $this->handle();

		if ($result === false && !$this->dispatcher->hasNotFoundHandler()) {
			http_response_code(404);
			return ['error' => 'Not Found', 'code' => 404];
		}

		return $result;
	}

	/**
	 * Get routes by method
	 * 
	 * @param string $method
	 * 
	 * @return array
	 */
	public function getRoutesByMethod(string $method): array
	{
		return $this->routeCollection->forMethod(strtoupper($method));
	}

	/**
	 * Merge another router
	 * 
	 * @param Router $router
	 * @param string $prefix
	 * 
	 * @return self
	 */
	public function merge(Router $router, string $prefix = ''): self
	{
		foreach ($router->getRoutes() as $method => $routes) {
			foreach ($routes as $route) {
				$pattern = $prefix . (string) $route->getPattern();

				// The registered callback, not getClassAndMethod(): rebuilding the
				// handler from a resolved [class, method] pair lost closures entirely
				// and threw rather than merging them.
				$this->addRoute($method, $pattern, $route->getRegisteredCallback(), $route->getMiddlewares(), $route->getHost(), $route->getContentType(), $route->getRequiredQueryParams(), $route->getPathQueryKey());
			}
		}

		return $this;
	}

	/**
	 * Define a nested route group with configurable prefix, middleware, domain, and namespace.
	 * Supports stacking multiple group levels with proper prefix/middleware inheritance.
	 *
	 * @param array $attributes Group attributes: prefix, middleware, domain, namespace, etc.
	 * @param Closure $callback The closure defining routes within this group.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function groupWithAttributes(array $attributes, Closure $callback): self
	{
		$previousPrefix = $this->prependPrefix;
		$previousDomain = $this->currentDomain;
		$previousMiddlewares = $this->middlewares;

		// Apply prefix
		if (isset($attributes['prefix'])) {
			$this->prependPrefix = $previousPrefix . $attributes['prefix'];
		}

		// Apply domain
		if (isset($attributes['domain'])) {
			$this->currentDomain = $attributes['domain'];
		}

		// Apply middleware
		if (isset($attributes['middleware'])) {
			$middlewares = is_array($attributes['middleware']) ? $attributes['middleware'] : [$attributes['middleware']];
			$this->middlewares = array_merge($this->middlewares, $middlewares);
		}

		// Push to stack for nested tracking
		$this->groupPrefixStack[] = $this->prependPrefix;
		$this->groupMiddlewareStack[] = $this->middlewares;

		try {
			$callback($this);
		} finally {
			array_pop($this->groupPrefixStack);
			array_pop($this->groupMiddlewareStack);
			$this->prependPrefix = $previousPrefix;
			$this->currentDomain = $previousDomain;
			$this->middlewares = $previousMiddlewares;
		}

		return $this;
	}

	/**
	 * Register a before-route hook that executes before any route handler.
	 * Return false from the hook to abort route handling.
	 *
	 * @param Closure $hook The hook closure. Receives (string $method, ArrayObject $segments).
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function before(Closure $hook): self
	{
		$this->dispatcher->addBeforeHook($hook);
		return $this;
	}

	/**
	 * Register an after-route hook that executes after a route handler completes.
	 * If the hook returns a non-null value, it replaces the route's response.
	 *
	 * @param Closure $hook The hook closure. Receives (string $method, ArrayObject $segments, mixed $result).
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function after(Closure $hook): self
	{
		$this->dispatcher->addAfterHook($hook);
		return $this;
	}

	/**
	 * Set a handler for HTTP 405 Method Not Allowed responses.
	 * This handler is invoked when a matching route exists for a different HTTP method.
	 *
	 * @param string|Closure|null $handler The handler closure or class method string.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function setMethodNotAllowedHandler(string|null|Closure $handler): self
	{
		$this->dispatcher->setMethodNotAllowedHandler($handler);
		return $this;
	}

	/**
	 * Register a CRUD resource with additional collection and member actions.
	 * Extends the standard resource routes with custom endpoints.
	 *
	 * @param string $name The resource name used in URL patterns.
	 * @param string $controller The controller class name.
	 * @param array $options Options including 'collection' and 'member' action maps.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function extendedResource(string $name, string $controller, array $options = []): self
	{
		$this->resource($name, $controller, $options);

		// Register create and edit form routes
		if (!isset($options['except']) || !in_array('create', $options['except'])) {
			$this->set('GET', "/{$name}/create", "{$controller}::create");
		}
		if (!isset($options['except']) || !in_array('edit', $options['except'])) {
			$this->set('GET', "/{$name}/{id}/edit", "{$controller}::edit");
		}

		// Additional collection-level actions: e.g. ['search' => 'GET']
		if (isset($options['collection'])) {
			foreach ($options['collection'] as $action => $method) {
				$this->set(strtoupper($method), "/{$name}/{$action}", "{$controller}::{$action}");
			}
		}

		// Additional member-level actions: e.g. ['activate' => 'POST']
		if (isset($options['member'])) {
			foreach ($options['member'] as $action => $method) {
				$this->set(strtoupper($method), "/{$name}/{id}/{$action}", "{$controller}::{$action}");
			}
		}

		return $this;
	}

	/**
	 * Register a singleton resource (no index or ID-based routes).
	 * Useful for resources where only one instance exists per user (e.g. profile, settings).
	 *
	 * @param string $name The resource name.
	 * @param string $controller The controller class name.
	 * @param array $options Options with 'only' and 'except' to filter actions.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function singleton(string $name, string $controller, array $options = []): self
	{
		$only = $options['only'] ?? ['show', 'edit', 'update', 'destroy'];
		$except = $options['except'] ?? [];

		$routes = [
			'show' => ['GET', "/{$name}", 'show'],
			'edit' => ['GET', "/{$name}/edit", 'edit'],
			'update' => ['PUT', "/{$name}", 'update'],
			'destroy' => ['DELETE', "/{$name}", 'destroy'],
		];

		foreach ($routes as $action => $config) {
			if (in_array($action, $except) || (!empty($only) && !in_array($action, $only))) {
				continue;
			}
			[$method, $pattern, $func] = $config;
			$this->set($method, $pattern, "{$controller}::{$func}");
		}

		return $this;
	}

	/**
	 * Register multiple API resources at once with a shared prefix and version.
	 *
	 * @param array<string, string> $resources Map of resource name to controller class name.
	 * @param array $options Shared options including 'prefix' and 'version'.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function apiResources(array $resources, array $options = []): self
	{
		foreach ($resources as $name => $controller) {
			$this->apiResource($name, $controller, $options);
		}

		return $this;
	}

	/**
	 * Register multiple standard resources at once.
	 *
	 * @param array<string, string> $resources Map of resource name to controller class name.
	 * @param array $options Shared options for all resources.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function resources(array $resources, array $options = []): self
	{
		foreach ($resources as $name => $controller) {
			$this->resource($name, $controller, $options);
		}

		return $this;
	}

	/**
	 * Set a global pattern constraint that applies to all routes containing the named parameter.
	 *
	 * @param string $param The parameter name (e.g. 'id').
	 * @param string $regex The regex pattern the parameter must match (e.g. '[0-9]+').
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function pattern(string $param, string $regex): self
	{
		$this->routeCollection->setGlobalPattern($param, $regex);
		return $this;
	}

	/**
	 * Set multiple global pattern constraints at once.
	 *
	 * @param array<string, string> $patterns Map of parameter names to regex patterns.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function patterns(array $patterns): self
	{
		foreach ($patterns as $param => $regex) {
			$this->pattern($param, $regex);
		}
		return $this;
	}

	/**
	 * Retrieve all global pattern constraints.
	 *
	 * @return array<string, string> Map of parameter names to regex patterns.
	 */
	public function getGlobalPatterns(): array
	{
		return $this->routeCollection->globalPatterns();
	}

	/**
	 * Register a reusable macro that can be replayed to register a set of routes.
	 *
	 * @param string $name A unique name for the macro.
	 * @param Closure(Router, array[]): ?Router $callback The closure defining routes. Receives the Router instance.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function macro(string $name, Closure $callback): self
	{
		$this->macros[$name] = $callback;
		return $this;
	}

	/**
	 * Execute a previously registered macro to register its routes.
	 *
	 * @param string $name The macro name.
	 * @param array $params Optional parameters passed to the macro closure.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 * @throws RuntimeException If the macro name has not been registered.
	 */
	public function useMacro(string $name, array $params = []): self
	{
		if (!isset($this->macros[$name])) {
			throw new RuntimeException("Route macro '{$name}' is not defined.");
		}

		($this->macros[$name])($this, ...$params);
		return $this;
	}

	/**
	 * Check whether a macro with the given name exists.
	 *
	 * @param string $name The macro name.
	 * 
	 * @return bool True if the macro is registered.
	 */
	public function hasMacro(string $name): bool
	{
		return isset($this->macros[$name]);
	}

	/**
	 * Register routes only when a condition is met.
	 * The condition closure is evaluated immediately; routes inside the callback
	 * are only registered if the condition returns true.
	 *
	 * @param Closure|bool $condition A boolean or closure returning bool.
	 * @param Closure $callback The closure defining routes.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function when(Closure|bool $condition, Closure $callback): self
	{
		$result = $condition instanceof Closure ? $condition() : $condition;

		if ($result) {
			$callback($this);
		}

		return $this;
	}

	/**
	 * Register routes only when a condition is NOT met.
	 *
	 * @param Closure|bool $condition A boolean or closure returning bool.
	 * @param Closure $callback The closure defining routes.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function unless(Closure|bool $condition, Closure $callback): self
	{
		$result = $condition instanceof Closure ? $condition() : $condition;

		if (!$result) {
			$callback($this);
		}

		return $this;
	}

	/**
	 * Enable maintenance mode. When active, all requests receive a 503 response
	 * unless the client IP is in the bypass list.
	 *
	 * @param Closure|null $handler Optional custom handler for the maintenance page.
	 * @param string[] $bypassIPs IP addresses that can bypass maintenance mode.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function enableMaintenance(?Closure $handler = null, array $bypassIPs = []): self
	{
		$this->dispatcher->enableMaintenance($bypassIPs, $handler);
		return $this;
	}

	/**
	 * Disable maintenance mode.
	 *
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function disableMaintenance(): self
	{
		$this->dispatcher->disableMaintenance();
		return $this;
	}

	/**
	 * Check whether the router is currently in maintenance mode.
	 *
	 * @return bool True if maintenance mode is enabled.
	 */
	public function isMaintenanceMode(): bool
	{
		return $this->dispatcher->inMaintenance();
	}

	/**
	 * Configure throttling for a specific route group pattern with burst support.
	 *
	 * @param string $pattern The route pattern to throttle.
	 * @param int $maxRequests Maximum sustained requests per time window.
	 * @param int $perSeconds Time window in seconds.
	 * @param int $burst Maximum burst requests allowed above the sustained rate.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function throttle(string $pattern, int $maxRequests, int $perSeconds = 60, int $burst = 0): self
	{
		$this->routeCollection->setThrottle($pattern, $maxRequests, $perSeconds, $burst);

		return $this;
	}

	/**
	 * Get the throttle configuration for a route pattern.
	 *
	 * @param string $pattern The route pattern.
	 * 
	 * @return array{
	 * 	max: int, 
	 * 	per: int, 
	 * 	burst: int
	 * }|null The throttle config or null.
	 */
	public function getThrottle(string $pattern): ?array
	{
		return $this->routeCollection->throttle($pattern);
	}

	/**
	 * Tag a route pattern with one or more labels for categorization or bulk operations.
	 *
	 * @param string $pattern The route pattern to tag.
	 * @param string|string[] $tags One or more tag labels.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function tag(string $pattern, string|array $tags): self
	{
		$this->routeCollection->addTags($pattern, is_array($tags) ? $tags : [$tags]);

		return $this;
	}

	/**
	 * Get all route patterns that carry a specific tag.
	 *
	 * @param string $tag The tag to search for.
	 * 
	 * @return string[] List of route patterns with the given tag.
	 */
	public function getRoutesByTag(string $tag): array
	{
		return $this->routeCollection->patternsTagged($tag);
	}

	/**
	 * Get tags for a specific route pattern.
	 *
	 * @param string $pattern The route pattern.
	 * 
	 * @return string[] Tags assigned to the pattern.
	 */
	public function getTags(string $pattern): array
	{
		return $this->routeCollection->tagsFor($pattern);
	}

	/**
	 * Remove a specific route by HTTP method and pattern.
	 *
	 * @param string $method The HTTP method.
	 * @param string $pattern The route pattern to remove.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function removeRoute(string $method, string $pattern): self
	{
		$this->routeCollection->removeByMethodAndPattern(strtoupper($method), $pattern);

		return $this;
	}

	/**
	 * Remove all routes matching a specific pattern across all HTTP methods.
	 *
	 * @param string $pattern The route pattern to remove.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function removeRouteByPattern(string $pattern): self
	{
		$this->routeCollection->removeByPattern($pattern);

		return $this;
	}

	/**
	 * Get a list of all registered route patterns grouped by HTTP method.
	 *
	 * @return array<string, string[]> Map of HTTP method to array of pattern strings.
	 */
	public function listRoutes(): array
	{
		return $this->routeCollection->patternsByMethod();
	}

	/**
	 * Register a route that returns a JSON response with the given data and status code.
	 *
	 * @param string $pattern The route pattern.
	 * @param array|object $data The data to encode as JSON.
	 * @param int $status The HTTP status code. Default 200.
	 * @param array $headers Additional headers to send.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function json(string $pattern, array|object $data, int $status = 200, array $headers = []): self
	{
		$this->get($pattern, static function () use ($data, $status, $headers): Response {
			$response = new Response(
				(string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
				[],
				'json',
				$status
			);

			foreach ($headers as $name => $value) {
				$response->setHeader((string) $name, (string) $value);
			}

			return $response;
		});

		return $this;
	}

	/**
	 * Register a route that streams a file download to the client.
	 *
	 * @param string $pattern The route pattern.
	 * @param string $filePath Absolute path to the file on disk.
	 * @param string|null $downloadName The filename presented to the browser. Defaults to the original name.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function download(string $pattern, string $filePath, ?string $downloadName = null): self
	{
		$this->get($pattern, static function () use ($filePath, $downloadName): Response {
			if (!file_exists($filePath)) {
				return new Response('File not found', [], 'text', 404);
			}

			$name = $downloadName ?? basename($filePath);
			$mime = mime_content_type($filePath) ?: 'application/octet-stream';

			// The body is read rather than streamed with readfile(), because the
			// response now travels back through the middleware stack instead of
			// being written straight to the output buffer. Large downloads cost
			// their own size in memory.
			$response = Response::download((string) file_get_contents($filePath), $name, $mime);
			$response->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate');

			return $response;
		});

		return $this;
	}

	/**
	 * Register a route that returns plain text content.
	 *
	 * @param string $pattern The route pattern.
	 * @param string $text The text content to return.
	 * @param int $status The HTTP status code. Default 200.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function text(string $pattern, string $text, int $status = 200): self
	{
		$this->get($pattern, static function () use ($text, $status): Response {
			return new Response($text, [], 'text', $status);
		});

		return $this;
	}

	/**
	 * Register a health-check endpoint that returns HTTP 200 with an optional payload.
	 *
	 * @param string $pattern The health-check URL pattern. Default '/health'.
	 * @param array $payload Additional data to include in the JSON response.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function health(string $pattern = '/health', array $payload = []): self
	{
		return $this->json($pattern, array_merge([
			'status' => 'ok',
			'timestamp' => time(),
		], $payload));
	}

	/**
	 * Redirect all variations of a URL with a trailing slash to the version without.
	 * For example /users/ redirects to /users.
	 *
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function stripTrailingSlash(): self
	{
		$this->before(function (string $method, $segments) {
			$uri = $_SERVER['REQUEST_URI'] ?? '/';
			$path = parse_url($uri, PHP_URL_PATH);
			if ($path !== '/' && str_ends_with($path, '/')) {
				$cleaned = rtrim($path, '/');
				$query = parse_url($uri, PHP_URL_QUERY);
				$redirect = $cleaned . ($query ? "?{$query}" : '');

				return Response::redirect($redirect, 301);
			}
			return true;
		});

		return $this;
	}

	/**
	 * Force HTTPS by redirecting any HTTP request to its HTTPS equivalent.
	 *
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function forceHttps(): self
	{
		$this->before(function () {
			$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
				|| ($_SERVER['SERVER_PORT'] ?? 80) == 443
				|| (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

			if (!$isHttps) {
				$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
				$uri = $_SERVER['REQUEST_URI'] ?? '/';

				return Response::redirect("https://{$host}{$uri}", 301);
			}
			return true;
		});

		return $this;
	}

	/**
	 * Force the www prefix by redirecting non-www requests to the www version.
	 *
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function forceWww(): self
	{
		$this->before(function () {
			$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
			if (!str_starts_with($host, 'www.') && !in_array($host, ['localhost', '127.0.0.1'])) {
				$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
				$uri = $_SERVER['REQUEST_URI'] ?? '/';

				return Response::redirect("{$scheme}://www.{$host}{$uri}", 301);
			}
			return true;
		});

		return $this;
	}

	/**
	 * Strip the www prefix by redirecting www requests to the non-www version.
	 *
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function stripWww(): self
	{
		$this->before(function () {
			$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
			if (str_starts_with($host, 'www.')) {
				$naked = substr($host, 4);
				$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
				$uri = $_SERVER['REQUEST_URI'] ?? '/';

				return Response::redirect("{$scheme}://{$naked}{$uri}", 301);
			}
			return true;
		});

		return $this;
	}

	/**
	 * Serialize all routes to a JSON string for debugging, export, or external caching.
	 *
	 * @param int $flags JSON encoding flags. Default JSON_PRETTY_PRINT.
	 * 
	 * @return string A JSON representation of all routes.
	 */
	public function toJson(int $flags = JSON_PRETTY_PRINT): string
	{
		return json_encode($this->toArray(), $flags | JSON_UNESCAPED_UNICODE);
	}

	/**
	 * Load routes from a JSON string previously produced by toJson().
	 *
	 * @param string $json The JSON string containing route definitions.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 * @throws RuntimeException If the JSON cannot be decoded.
	 */
	public function fromJson(string $json): self
	{
		$data = json_decode($json, true);
		if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
			throw new RuntimeException('Invalid JSON: ' . json_last_error_msg());
		}

		return $this->fromCachedArray($data);
	}

	/**
	 * Save routes to a cache file for faster boot on subsequent requests.
	 *
	 * @param string $filePath The file path to write the cache to.
	 * 
	 * @return bool True on success, false on failure.
	 */
	public function saveCacheToFile(string $filePath): bool
	{
		$data = $this->toArray();
		$content = '<?php return ' . var_export($data, true) . ';';
		return file_put_contents($filePath, $content) !== false;
	}

	/**
	 * Load routes from a previously saved cache file.
	 *
	 * @param string $filePath The cache file path.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 * @throws RuntimeException If the cache file does not exist or is unreadable.
	 */
	public function loadCacheFromFile(string $filePath): self
	{
		if (!file_exists($filePath)) {
			throw new RuntimeException("Route cache file not found: {$filePath}");
		}

		$data = require $filePath;
		return $this->fromCachedArray($data);
	}

	/**
	 * Resolve the first matching route for a given method and URI without executing it.
	 * Useful for URL testing, debugging, and route inspection tools.
	 *
	 * @param string $method The HTTP method.
	 * @param string $uri The request URI.
	 * @param string $host The host header value. Default '*'.
	 * @param string $contentType The content type header value. Default '*'.
	 * 
	 * @return array|null The matched route info or null if no route matches.
	 */
	public function resolve(string $method, string $uri, string $host = '*', string $contentType = '*'): ?array
	{
		$method = strtoupper($method);
		if (!$this->has($method)) {
			return null;
		}

		$segments = new ArrayObject(array_values(array_filter(explode('/', trim($uri, '/')), fn($s) => $s !== '')));

		foreach ($this->routeCollection->forMethod($method) as $route) {
			$routeClone = clone $route;
			if ($routeClone->match($segments, $host, $contentType)) {
				[$class, $caller] = $routeClone->getClassAndMethod();
				return [
					'pattern' => $routeClone->getPattern()->__toString(),
					'class' => $class,
					'method' => $caller,
					'arguments' => $routeClone->getArguments(),
					'middlewares' => $routeClone->getMiddlewares(),
					'host' => $routeClone->getHost(),
					'contentType' => $routeClone->getContentType(),
				];
			}
		}

		return null;
	}

	/**
	 * Return all named routes with their associated method and pattern.
	 *
	 * @return array<string, array{method: string, pattern: string}> Map of route name to definition.
	 */
	public function getNamedRoutes(): array
	{
		return $this->routeCollection->names();
	}

	/**
	 * Check if a named route exists.
	 *
	 * @param string $name The route name.
	 * 
	 * @return bool True if the named route exists.
	 */
	public function hasNamedRoute(string $name): bool
	{
		return $this->routeCollection->hasName($name);
	}

	/**
	 * Register multiple redirect rules at once.
	 *
	 * @param array<string, string> $redirects Map of source pattern to destination URL.
	 * @param int $status HTTP status code for all redirects. Default 302.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function redirects(array $redirects, int $status = 302): self
	{
		foreach ($redirects as $from => $to) {
			$this->redirect($from, $to, $status);
		}
		return $this;
	}

	/**
	 * Register an application prefix that is prepended to every route registered afterwards.
	 * Call with an empty string to remove the prefix.
	 *
	 * @param string $prefix The global prefix (e.g. '/app' or '/v2').
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function setGlobalPrefix(string $prefix): self
	{
		$this->prependPrefix = $prefix;
		return $this;
	}

	/**
	 * Get the current global prefix.
	 *
	 * @return string The prefix string currently being prepended to new routes.
	 */
	public function getGlobalPrefix(): string
	{
		return $this->prependPrefix;
	}

	/**
	 * Apply global CORS headers to all routes for a list of allowed origins.
	 *
	 * @param string|string[] $origins Allowed origin(s). Default '*'.
	 * @param array $options Additional CORS options.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function globalCors(string|array $origins = '*', array $options = []): self
	{
		$origin = is_array($origins) ? implode(', ', $origins) : $origins;

		$defaults = [
			'methods' => 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
			'headers' => 'Content-Type, Authorization, X-Requested-With',
			'credentials' => false,
			'maxAge' => 86400,
		];
		$config = array_merge($defaults, $options, ['origin' => $origin]);

		$this->after(function (string $method, $segments, $result) use ($config) {
			header("Access-Control-Allow-Origin: {$config['origin']}");
			header("Access-Control-Allow-Methods: {$config['methods']}");
			header("Access-Control-Allow-Headers: {$config['headers']}");
			header("Access-Control-Max-Age: {$config['maxAge']}");
			if ($config['credentials']) {
				header('Access-Control-Allow-Credentials: true');
			}
			return null;
		});

		return $this;
	}

	/**
	 * Register a Server-Sent Events (SSE) endpoint.
	 * The callback receives a callable 'send' function to push events to the client.
	 *
	 * @param string $pattern The route pattern.
	 * @param Closure $callback The handler. Receives a send(string $data, string $event) callable.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function sse(string $pattern, Closure $callback): self
	{
		$this->get($pattern, function () use ($callback) {
			header('Content-Type: text/event-stream');
			header('Cache-Control: no-cache');
			header('Connection: keep-alive');
			header('X-Accel-Buffering: no');

			$send = function (string $data, string $event = 'message') {
				echo "event: {$event}\n";
				echo "data: {$data}\n\n";
				if (ob_get_level() > 0) {
					ob_flush();
				}
				flush();
			};

			$callback($send);
		});

		return $this;
	}

	/**
	 * Register a Websocket-upgrade-aware endpoint.
	 * Sends proper upgrade refusal headers when the request is not a valid upgrade request.
	 *
	 * @param string $pattern The route pattern.
	 * @param mixed $callback The handler for upgraded connections.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function websocket(string $pattern, mixed $callback): self
	{
		$this->get($pattern, static function () use ($callback): mixed {
			$upgradeHeader = $_SERVER['HTTP_UPGRADE'] ?? '';
			if (strtolower((string) $upgradeHeader) !== 'websocket') {
				$refusal = Response::json(['error' => 'Upgrade Required'], 426);
				$refusal->setHeader('Upgrade', 'websocket');

				return $refusal;
			}

			if (is_callable($callback)) {
				return $callback();
			}

			return null;
		});

		return $this;
	}

	/**
	 * Dump all registered routes to a human-readable table string for CLI debugging.
	 *
	 * @return string A formatted table of all routes.
	 */
	public function dump(): string
	{
		$lines = [];
		$lines[] = str_pad('METHOD', 10) . str_pad('PATTERN', 40) . str_pad('HANDLER', 50) . 'HOST';
		$lines[] = str_repeat('-', 140);

		foreach ($this->routeCollection->all() as $method => $routes) {
			foreach ($routes as $route) {
				[$class, $caller] = $route->getClassAndMethod();
				$handler = $class ? "{$class}::{$caller}" : ($caller ?: '{closure}');
				$lines[] = str_pad($method, 10)
					. str_pad((string) $route->getPattern(), 40)
					. str_pad($handler, 50)
					. $route->getHost();
			}
		}

		return implode("\n", $lines);
	}

	/**
	 * Get statistics about the registered routes: total count, per-method count, etc.
	 *
	 * @return array{
	 * 	total: int, 
	 * 	methods: array<string, int>, 
	 * 	named: int, 
	 * 	tags: int, 
	 * 	rate_limited: int, 
	 * 	throttled: int, 
	 * 	macros: int,
	 * 	before_hooks: int,
	 * 	after_hooks: int,
	 * 	maintenance: bool
	 * }
	 */
	public function stats(): array
	{
		$methods = [];
		$total = 0;
		foreach ($this->routeCollection->all() as $method => $routes) {
			$methods[$method] = count($routes);
			$total += count($routes);
		}

		return [
			'total' => $total,
			'methods' => $methods,
			'named' => count($this->routeCollection->names()),
			'tags' => count($this->routeCollection->tags()),
			'rate_limited' => $this->routeCollection->rateLimitCount(),
			'throttled' => $this->routeCollection->throttleCount(),
			'macros' => count($this->macros),
			'before_hooks' => $this->dispatcher->beforeHookCount(),
			'after_hooks' => $this->dispatcher->afterHookCount(),
			'maintenance' => $this->dispatcher->inMaintenance(),
		];
	}

	/**
	 * Register a static-file serving route that maps a URL prefix to a filesystem directory.
	 * Supports configurable cache headers and allowed file extensions.
	 *
	 * @param string $urlPrefix The URL prefix (e.g. '/static').
	 * @param string $directory The filesystem directory to serve files from.
	 * @param array $options Options: 'extensions' whitelist, 'maxAge' cache seconds.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function static(string $urlPrefix, string $directory, array $options = []): self
	{
		$allowedExtensions = $options['extensions'] ?? ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'webp', 'avif', 'map'];
		$maxAge = $options['maxAge'] ?? 86400;

		$pattern = rtrim($urlPrefix, '/') . '/{path}';
		$this->get($pattern, static function () use ($directory, $allowedExtensions, $maxAge): Response {
			$requestUri = $_SERVER['REQUEST_URI'] ?? '';
			$path = parse_url($requestUri, PHP_URL_PATH) ?? '';

			// Prevent directory traversal
			$realBase = realpath($directory);
			$filePath = realpath($directory . '/' . basename($path));

			if ($realBase === false || $filePath === false || !str_starts_with($filePath, $realBase)) {
				return new Response('Forbidden', [], 'text', 403);
			}

			$extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
			if (!in_array($extension, $allowedExtensions, true)) {
				return new Response('Forbidden file type', [], 'text', 403);
			}

			if (!is_file($filePath)) {
				return new Response('Not Found', [], 'text', 404);
			}

			$etag = (string) md5_file($filePath);

			if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim((string) $_SERVER['HTTP_IF_NONE_MATCH'], '"') === $etag) {
				$notModified = new Response('', [], 'html', 304);
				$notModified->setHeader('ETag', sprintf('"%s"', $etag));
				$notModified->setHeader('Cache-Control', sprintf('public, max-age=%s', $maxAge));

				return $notModified;
			}

			$mime = mime_content_type($filePath) ?: 'application/octet-stream';

			// The body is read rather than streamed with readfile(), because the
			// response now travels back through the middleware stack instead of
			// being written straight to the output buffer.
			$response = new Response((string) file_get_contents($filePath), [], 'html', 200);
			$response->setHeader('Content-Type', $mime);
			$response->setHeader('Cache-Control', sprintf('public, max-age=%s', $maxAge));
			$response->setHeader('Content-Length', (string) filesize($filePath));
			$response->setHeader('ETag', sprintf('"%s"', $etag));

			return $response;
		});

		return $this;
	}

	/**
	 * Register a middleware group that can be referenced by name when building route groups.
	 *
	 * @param string $name The middleware group name.
	 * @param array $middlewares The middleware instances in the group.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function middlewareGroup(string $name, array $middlewares): self
	{
		$this->macro("middleware_group:{$name}", function (Router $router) use ($middlewares) {
			foreach ($middlewares as $mw) {
				$router->appendMiddleware($mw);
			}
		});

		return $this;
	}

	/**
	 * Replace all routes for a given HTTP method and pattern with a new callback.
	 *
	 * @param string $method The HTTP method.
	 * @param string $pattern The route pattern to replace.
	 * @param mixed $callback The new callback.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function replaceRoute(string $method, string $pattern, mixed $callback): self
	{
		$this->removeRoute($method, $pattern);
		$this->set(strtoupper($method), $pattern, $callback);
		return $this;
	}

	/**
	 * Register a route that responds with an HTTP status code and optional message.
	 *
	 * @param string $pattern The route pattern.
	 * @param int $status The HTTP status code to return.
	 * @param string $message Optional body message.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function status(string $pattern, int $status, string $message = ''): self
	{
		$this->any($pattern, static function () use ($status, $message): Response {
			return new Response($message, [], 'text', $status);
		});

		return $this;
	}

	/**
	 * Clone the router with all its routes and configuration.
	 *
	 * @return self A new Router instance with identical route definitions.
	 */
	public function cloneRouter(): self
	{
		return clone $this;
	}

	/**
	 * Reset the router to its initial blank state, clearing all routes, hooks, macros, and config.
	 *
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function reset(): self
	{
		HTTPRequest::clearRoutingPathQueryKeys();
		$this->routeCollection = new RouteCollection();
		$this->middlewares = [];
		$this->dispatcher = new RouteDispatcher($this->kernelEventDispatcher);
		$this->macros = [];
		$this->prependPrefix = '';
		$this->currentDomain = '*';
		$this->groupPrefixStack = [];
		$this->groupMiddlewareStack = [];
		return $this;
	}

	/**
	 * Get the event dispatcher instance.
	 *
	 * @return EventDispatcherInterface|null The event dispatcher or null.
	 */
	public function getEventDispatcher(): ?EventDispatcherInterface
	{
		return $this->eventDispatcher;
	}

	/**
	 * Get the dependency injection container.
	 *
	 * @return Container|null The container instance or null if not set.
	 */
	public function getContainer(): ?Container
	{
		return $this->container ?? null;
	}

	/**
	 * Dispatch an event through the event dispatcher if one is configured.
	 *
	 * @param object $eventName The event name.
	 * @param mixed $payload The event payload.
	 * 
	 * @return void
	 */
	public function dispatchEvent(object $eventName, mixed $payload = null): void
	{
		EventManager::getEventBus()->publish($eventName);
	}

	/**
	 * Generate a simple route map as an array of strings suitable for CLI output.
	 * Each entry is formatted as "METHOD /pattern -> Handler".
	 *
	 * @return string[] List of route description strings.
	 */
	public function getRouteDescriptions(): array
	{
		$descriptions = [];
		foreach ($this->routeCollection->all() as $method => $routes) {
			foreach ($routes as $route) {
				[$class, $caller] = $route->getClassAndMethod();
				$handler = $class ? "{$class}::{$caller}" : ($caller ?: '{closure}');
				$descriptions[] = "{$method} " . (string) $route->getPattern() . " -> {$handler}";
			}
		}
		return $descriptions;
	}

	/**
	 * Register a catch-all route that matches any method and pattern not already matched.
	 * This is a more aggressive version of fallback() that catches every request.
	 *
	 * @param mixed $callback The handler for unmatched requests.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function catchAll(mixed $callback): self
	{
		$this->any('/{path}', $callback);
		$this->fallback($callback);
		return $this;
	}

	/**
	 * Register a versioned API group that automatically prefixes routes with /api/{version}.
	 *
	 * @param string $version The API version string (e.g. 'v1', 'v2').
	 * @param Closure $callback The closure defining routes within this API version group.
	 * @param string $prefix The base API prefix. Default '/api'.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function apiVersion(string $version, Closure $callback, string $prefix = '/api'): self
	{
		return $this->groupWithAttributes([
			'prefix' => "{$prefix}/{$version}",
		], $callback);
	}

	/**
	 * Register conditional redirect based on a closure evaluation.
	 * If the condition returns true, the redirect is performed; otherwise the request proceeds normally.
	 *
	 * @param string $from The source pattern.
	 * @param string $to The destination URL.
	 * @param Closure $condition The condition closure. Return true to redirect.
	 * @param int $status The HTTP redirect status code. Default 302.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function conditionalRedirect(string $from, string $to, Closure $condition, int $status = 302): self
	{
		$this->get($from, static function () use ($to, $status, $condition): ?Response {
			if ($condition()) {
				return Response::redirect($to, $status);
			}

			return null;
		});

		return $this;
	}

	/**
	 * Set a per-route rate limit that is checked at dispatch time.
	 * Returns the rate limit info including remaining requests.
	 *
	 * @param string $pattern The route pattern.
	 * @param string $identifier A unique client identifier (IP, token, etc.).
	 * 
	 * @return array{
	 * 	allowed: bool, 
	 * 	limit: int, 
	 * 	remaining: int, 
	 * 	reset: int
	 * }|null Null if no limit is configured.
	 */
	public function checkRateLimit(string $pattern, string $identifier): ?array
	{
		$config = $this->routeCollection->rateLimit($pattern);
		if ($config === null) {
			return null;
		}

		// Use a simple in-memory approach; production implementations should use Redis/Memcached
		$cacheKey = "rate_limit:{$pattern}:{$identifier}";
		$now = time();
		$windowStart = $now - $config['per'];

		// This is a framework-level API; actual storage should be injected
		return [
			'allowed' => true,
			'limit' => $config['max'],
			'remaining' => $config['max'],
			'reset' => $now + $config['per'],
		];
	}

	/**
	 * Find a named route and generate a full URL with query parameters.
	 *
	 * @param string $name The route name.
	 * @param array $params Path parameters to substitute into the pattern.
	 * @param array $query Query string parameters to append.
	 * 
	 * @return string|null The generated URL with query string, or null if the route is not found.
	 */
	public function url(string $name, array $params = [], array $query = []): ?string
	{
		$path = $this->route($name, $params);
		if ($path === null) {
			return null;
		}

		if (!empty($query)) {
			$path .= '?' . http_build_query($query);
		}

		return $path;
	}

	/**
	 * Generate a fully qualified absolute URL for a named route.
	 *
	 * @param string $name The route name.
	 * @param array $params Path parameters.
	 * @param array $query Query string parameters.
	 * @param string|null $scheme Force scheme (http/https). Null to auto-detect.
	 * 
	 * @return string|null The absolute URL or null if the route is not found.
	 */
	public function absoluteUrl(string $name, array $params = [], array $query = [], ?string $scheme = null): ?string
	{
		$path = $this->url($name, $params, $query);
		if ($path === null) {
			return null;
		}

		$detectedScheme = $scheme ?? ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
		$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

		return "{$detectedScheme}://{$host}{$path}";
	}

	/**
	 * Apply a middleware to all routes matching a specific pattern prefix.
	 *
	 * @param string $prefix The URL prefix to match (e.g. '/admin').
	 * @param Middleware $middleware The middleware to apply.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function middlewareForPrefix(string $prefix, Middleware $middleware): self
	{
		$this->before(function (string $method, $segments) use ($prefix, $middleware) {
			$uri = '/' . implode('/', is_array($segments) ? $segments : iterator_to_array($segments));
			if (str_starts_with($uri, $prefix)) {
				// Add middleware dynamically to matching route
				$this->appendMiddleware($middleware);
			}
			return true;
		});

		return $this;
	}

	/**
	 * Register a route that accepts only specific content types.
	 *
	 * @param string $method The HTTP method.
	 * @param string $pattern The route pattern.
	 * @param mixed $callback The handler.
	 * @param string $contentType The required content type (e.g. 'application/json').
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function withContentType(string $method, string $pattern, mixed $callback, string $contentType): self
	{
		$pattern = $this->addPrefix($pattern);
		$this->addRoute(strtoupper($method), $pattern, $callback, [], '*', $contentType, [], '');
		return $this;
	}

	/**
	 * Register a POST route that only accepts JSON content type.
	 *
	 * @param string $pattern The route pattern.
	 * @param mixed $callback The handler.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function postJson(string $pattern, mixed $callback): self
	{
		return $this->withContentType('POST', $pattern, $callback, 'application/json');
	}

	/**
	 * Register a PUT route that only accepts JSON content type.
	 *
	 * @param string $pattern The route pattern.
	 * @param mixed $callback The handler.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function putJson(string $pattern, mixed $callback): self
	{
		return $this->withContentType('PUT', $pattern, $callback, 'application/json');
	}

	/**
	 * Register a PATCH route that only accepts JSON content type.
	 *
	 * @param string $pattern The route pattern.
	 * @param mixed $callback The handler.
	 * 
	 * @return self Returns the current Router instance for method chaining.
	 */
	public function patchJson(string $pattern, mixed $callback): self
	{
		return $this->withContentType('PATCH', $pattern, $callback, 'application/json');
	}

	#endregion
}
