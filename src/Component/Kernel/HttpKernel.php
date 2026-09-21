<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Component\Kernel;

use Clover\Component\Bootstrap\BootProviders;
use Clover\Component\Bootstrap\HandleExceptions;
use Clover\Component\Bootstrap\LoadEnvironmentVariables;
use Clover\Component\Bootstrap\RegisterProviders;
use Clover\Component\Contract\BootstrapperInterface;
use Clover\Component\Contract\ExceptionHandlerInterface;
use Clover\Component\Contract\MiddlewareInterface;
use Clover\Component\Contract\TerminableMiddlewareInterface;
use Clover\Component\Foundation\Application;
use Clover\Component\Http\Request;
use Clover\Component\Http\Response;
use Clover\Component\Pipeline\Pipeline;
use Clover\Component\Routing\RouteRequestHandler;
use Clover\Component\Routing\Router;
use Clover\Contract\ContainerInterface;
use Clover\Contract\KernelInterface;
use Clover\Service\AIOrchestrationServiceProvider;
use Clover\Service\RoutingServiceProvider;
use RuntimeException;
use Throwable;
use function array_key_exists;
use function array_merge;
use function array_push;
use function class_exists;
use function is_object;
use function is_string;
use function sprintf;

/**
 * HttpKernel Class
 *
 * Turns an HTTP request into a response: start the application once, wrap the router in the
 * middleware the request earns, run it, and hand back what comes out.
 *
 * **An application replaces this by subclassing it.** Declare the subclass in
 * `App/Configure/kernel.php`:
 *
 * ```php
 * return static function (Application $app): void {
 *     $app->singleton(KernelInterface::class, new App\Http\Kernel($app));
 * };
 * ```
 *
 * and override the properties rather than the methods - `$middleware` for layers every request
 * passes through, `$middlewareGroups` for named bundles, `$routeMiddleware` for aliases a route
 * can ask for by name, `$bootstrappers` to change what start-up does. The methods exist to be
 * overridden too, but a subclass that only swaps a list should not have to know how the list is
 * used.
 *
 * Middleware are resolved from the container at dispatch time, not at registration, so a
 * middleware may depend on services that providers are still registering when routes are declared.
 */
class HttpKernel implements KernelInterface
{
	/**
	 * @var array<int, class-string<BootstrapperInterface>> Start-up steps, in order.
	 *
	 * Environment first because everything after it reads configuration; the exception handler
	 * next so a failure in provider registration can still be rendered; providers registered
	 * before any are booted, so a provider's `boot()` sees every binding.
	 */
	protected array $bootstrappers = [
		LoadEnvironmentVariables::class,
		HandleExceptions::class,
		RegisterProviders::class,
		BootProviders::class,
	];

	/**
	 * @var array<int, class-string> Service providers the framework itself needs.
	 *
	 * Registered ahead of whatever the application declares in `App/Configure/providers.php`, so
	 * the routing this kernel dispatches through exists even for an application that declares no
	 * providers at all. An application that lists the same provider gets it once -
	 * {@see Application::register()} keys on the class name.
	 */
	protected array $providers = [
		RoutingServiceProvider::class,
		AIOrchestrationServiceProvider::class,
	];

	/**
	 * @var array<int, class-string<MiddlewareInterface>> Layers every request passes through.
	 */
	protected array $middleware = [];

	/**
	 * @var array<string, array<int, string>> Named bundles of middleware.
	 */
	protected array $middlewareGroups = [];

	/**
	 * @var array<string, class-string<MiddlewareInterface>> Aliases a route may ask for by name.
	 */
	protected array $routeMiddleware = [];

	/**
	 * @var ContainerInterface The application container.
	 */
	protected ContainerInterface $app;

	/**
	 * @var bool Whether start-up has already run.
	 */
	protected bool $bootstrapped = false;

	/**
	 * @var array<int, array{request: Request, response: Response, middleware: array<int, MiddlewareInterface>}>
	 *      Requests handled but not yet terminated.
	 */
	protected array $pendingTermination = [];

	/**
	 * HttpKernel constructor.
	 *
	 * @param ContainerInterface $app The application container.
	 */
	public function __construct(ContainerInterface $app)
	{
		$this->app = $app;
	}

	/**
	 * Run every start-up step exactly once.
	 *
	 * Start-up needs a full `Application` - providers, paths, a boot phase - so a kernel handed a
	 * bare container skips it. That is the case in a unit test that wires one router by hand, and
	 * it is deliberate: such a kernel still dispatches, it just has nothing to start.
	 *
	 * @return void
	 */
	public function bootstrap(): void
	{
		if ($this->bootstrapped) {
			return;
		}

		$this->bootstrapped = true;

		if (!$this->app instanceof Application) {
			return;
		}

		// The framework's own providers go in front of the application's, so a provider the kernel
		// depends on is registered before one that might rely on it.
		$this->app->setDeclaredProviders(array_merge($this->providers, $this->app->getDeclaredProviders()));

		foreach ($this->bootstrappers as $bootstrapper) {
			$this->resolveBootstrapper($bootstrapper)->bootstrap($this->app);
		}
	}

	/**
	 * The service providers this kernel registers before the application's own.
	 *
	 * @return array<int, class-string>
	 */
	public function getProviders(): array
	{
		return $this->providers;
	}

	/**
	 * Boot the kernel.
	 *
	 * Kept for {@see KernelInterface}; start-up is {@see self::bootstrap()}.
	 *
	 * @return void
	 */
	public function boot(): void
	{
		$this->bootstrap();
	}

	/**
	 * Handle an incoming HTTP request.
	 *
	 * @param Request $request The incoming HTTP request.
	 *
	 * @return Response The HTTP response generated.
	 */
	public function handle(Request $request): Response
	{
		$middleware = [];

		try {
			$this->bootstrap();

			$router = $this->resolveRouter();
			$routeMatch = $router->match($request);

			if ($routeMatch === null) {
				$response = $router->respondToUnmatchedRequest($request);
			} else {
				$middleware = $this->buildMiddlewareStack($routeMatch['route']->getMiddleware());
				$destination = new RouteRequestHandler($router, $routeMatch['route'], $routeMatch['parameters']);
				$response = (new Pipeline($middleware, $destination))->handle($request);
			}
		} catch (Throwable $throwable) {
			$response = $this->renderThrowable($request, $throwable);
		}

		$this->pendingTermination[] = [
			'request' => $request,
			'response' => $response,
			'middleware' => $middleware,
		];

		return $response;
	}

	/**
	 * Run post-response work for every request this kernel handled.
	 *
	 * Called after the response has been sent, so anything slow here costs the next request rather
	 * than this one. A failure is swallowed: the client already has its bytes and there is nothing
	 * left to tell it, but the exception handler still records what happened.
	 *
	 * @return void
	 */
	public function terminate(): void
	{
		$pending = $this->pendingTermination;
		$this->pendingTermination = [];

		foreach ($pending as $entry) {
			foreach ($entry['middleware'] as $layer) {
				if (!$layer instanceof TerminableMiddlewareInterface) {
					continue;
				}

				try {
					$layer->terminate($entry['request'], $entry['response']);
				} catch (Throwable $throwable) {
					$this->reportThrowable($throwable);
				}
			}
		}
	}

	/**
	 * Get the application container instance.
	 *
	 * @return ContainerInterface The application container.
	 */
	public function getContainer(): ContainerInterface
	{
		return $this->app;
	}

	/**
	 * The middleware every request passes through.
	 *
	 * @return array<int, class-string<MiddlewareInterface>>
	 */
	public function getMiddleware(): array
	{
		return $this->middleware;
	}

	/**
	 * The named middleware bundles.
	 *
	 * @return array<string, array<int, string>>
	 */
	public function getMiddlewareGroups(): array
	{
		return $this->middlewareGroups;
	}

	/**
	 * The middleware aliases a route may ask for by name.
	 *
	 * @return array<string, class-string<MiddlewareInterface>>
	 */
	public function getRouteMiddleware(): array
	{
		return $this->routeMiddleware;
	}

	/**
	 * Build the ordered middleware instances for one request.
	 *
	 * Global middleware run outermost, then whatever the route asked for. A name may be an alias,
	 * a group - which expands, and may itself contain aliases and groups - or a class name.
	 *
	 * @param array<int, string> $routeMiddleware Names the matched route declared.
	 *
	 * @return array<int, MiddlewareInterface>
	 */
	protected function buildMiddlewareStack(array $routeMiddleware): array
	{
		$names = array_merge($this->middleware, $this->expandMiddlewareNames($routeMiddleware));
		$instances = [];

		foreach ($names as $name) {
			$instances[] = $this->resolveMiddleware($name);
		}

		return $instances;
	}

	/**
	 * Expand aliases and groups into a flat list of middleware class names.
	 *
	 * A group may contain another group. Expansion tracks the groups already entered and refuses
	 * to enter one twice, because a group that contains itself would otherwise expand until the
	 * process runs out of memory, and a stack overflow does not say which group was at fault.
	 *
	 * @param array<int, string> $names        Names to expand.
	 * @param array<string, true> $enteredGroups Groups already being expanded.
	 *
	 * @throws RuntimeException When a middleware group contains itself.
	 *
	 * @return array<int, string>
	 */
	protected function expandMiddlewareNames(array $names, array $enteredGroups = []): array
	{
		$expanded = [];

		foreach ($names as $name) {
			$alias = $this->routeMiddleware[$name] ?? null;

			if (is_string($alias)) {
				$expanded[] = $alias;
				continue;
			}

			if (!array_key_exists($name, $this->middlewareGroups)) {
				$expanded[] = $name;
				continue;
			}

			if (array_key_exists($name, $enteredGroups)) {
				throw new RuntimeException(sprintf(
					'Middleware group `%s` contains itself.',
					$name
				));
			}

			$enteredGroups[$name] = true;
			array_push($expanded, ...$this->expandMiddlewareNames($this->middlewareGroups[$name], $enteredGroups));
			unset($enteredGroups[$name]);
		}

		return $expanded;
	}

	/**
	 * Build one middleware instance.
	 *
	 * @param string $middleware A middleware class name.
	 *
	 * @throws RuntimeException When the name does not resolve to a middleware.
	 *
	 * @return MiddlewareInterface
	 */
	protected function resolveMiddleware(string $middleware): MiddlewareInterface
	{
		$resolved = $this->app->make($middleware);

		if ($resolved instanceof MiddlewareInterface) {
			return $resolved;
		}

		if (is_object($resolved)) {
			throw new RuntimeException(sprintf(
				'Middleware `%s` must implement %s.',
				$middleware,
				MiddlewareInterface::class
			));
		}

		throw new RuntimeException(sprintf('Middleware `%s` could not be resolved.', $middleware));
	}

	/**
	 * Build one bootstrapper instance.
	 *
	 * @param string $bootstrapper A bootstrapper class name.
	 *
	 * @throws RuntimeException When the name does not resolve to a bootstrapper.
	 *
	 * @return BootstrapperInterface
	 */
	protected function resolveBootstrapper(string $bootstrapper): BootstrapperInterface
	{
		if (!class_exists($bootstrapper)) {
			throw new RuntimeException(sprintf('Bootstrapper `%s` does not exist.', $bootstrapper));
		}

		$resolved = new $bootstrapper();

		if (!$resolved instanceof BootstrapperInterface) {
			throw new RuntimeException(sprintf(
				'Bootstrapper `%s` must implement %s.',
				$bootstrapper,
				BootstrapperInterface::class
			));
		}

		return $resolved;
	}

	/**
	 * Resolve the router this kernel dispatches through.
	 *
	 * @throws RuntimeException When the container holds no usable router.
	 *
	 * @return Router
	 */
	protected function resolveRouter(): Router
	{
		$router = $this->app->make(Router::class);

		if (!$router instanceof Router) {
			throw new RuntimeException(sprintf(
				'The resolved router must be an instance of %s.',
				Router::class
			));
		}

		return $router;
	}

	/**
	 * Turn a throwable into a response through the bound exception handler.
	 *
	 * If rendering itself fails there is nothing left to delegate to, so a bare 500 is written
	 * directly rather than letting a second throwable escape the kernel.
	 *
	 * @param Request   $request   The request being handled.
	 * @param Throwable $throwable The throwable that escaped.
	 *
	 * @return Response
	 */
	protected function renderThrowable(Request $request, Throwable $throwable): Response
	{
		$handler = $this->resolveExceptionHandler();

		if ($handler === null) {
			return Response::text('Server Error', 500);
		}

		try {
			$handler->report($throwable);

			return $handler->render($request, $throwable);
		} catch (Throwable) {
			return Response::text('Server Error', 500);
		}
	}

	/**
	 * Record a throwable through the bound exception handler, if there is one.
	 *
	 * @param Throwable $throwable The throwable to record.
	 *
	 * @return void
	 */
	protected function reportThrowable(Throwable $throwable): void
	{
		$handler = $this->resolveExceptionHandler();

		if ($handler === null) {
			return;
		}

		try {
			$handler->report($throwable);
		} catch (Throwable) {
			// Reporting a failure must not itself fail the request.
		}
	}

	/**
	 * The bound exception handler, or null when none is bound.
	 *
	 * @return ExceptionHandlerInterface|null
	 */
	protected function resolveExceptionHandler(): ?ExceptionHandlerInterface
	{
		try {
			$handler = $this->app->make(ExceptionHandlerInterface::class);
		} catch (Throwable) {
			return null;
		}

		return $handler instanceof ExceptionHandlerInterface ? $handler : null;
	}
}
