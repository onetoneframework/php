<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Middleware;

use Clover\Classes\Debug\Profiler;
use Clover\Classes\DependencyInjection\Container;
use Clover\Classes\HTTP\Request as LegacyRequest;
use Clover\Framework\Component\Response;
use Clover\Framework\Component\Request;
use Clover\Framework\Contract\MiddlewareInterface;
use Clover\Framework\Contract\RequestHandlerInterface;
use Clover\Framework\Event\KernelSpanFinished;
use Clover\Framework\Event\KernelSpanStarted;
use Clover\Framework\Event\RoutingLifecycleEvent;
use Clover\Framework\Module\ModuleRegistry;
use Clover\Framework\Routing\RouteRegistry;
use RuntimeException;
use function define;
use function defined;
use function dirname;
use function is_array;
use function is_int;
use function is_scalar;
use function is_string;
use function strtoupper;
use const DIRECTORY_SEPARATOR;

/*
 * Routing Middleware
 * 
 * This middleware is responsible for handling routing. It checks for a cached route configuration and loads it if available. If not, it scans the specified directories for controller and command classes to build the routing configuration. It then handles the incoming request using the router and returns the appropriate response.
 */
class RoutingMiddleware implements MiddlewareInterface
{
	private const FRAMEWORK_ROOT_DEPTH = 3;
	private const APPLICATION_ROOT_DIRECTORY = 'root';

	/** @var callable(object): void|null */
	private $eventDispatcher;

	/**
	 * @param callable(object): void|null $eventDispatcher
	 */
	public function __construct(private readonly Container $container, callable|null $eventDispatcher = null)
	{
		$this->eventDispatcher = $eventDispatcher;
	}

    /**
     * Process an incoming server request and return a response, optionally delegating to the next middleware component to create the response.
     * 
     * @param Request $request
     * @param RequestHandlerInterface $handler
     * @return Response
     */
	public function process(Request $request, RequestHandlerInterface $handler): Response
	{
		$this->dispatchEvent(new RoutingLifecycleEvent(RoutingLifecycleEvent::MIDDLEWARE_STARTED));
		$profilerEnabled = Profiler::isEnabled();
		$routingSpan = '';
		if ($profilerEnabled) {
			$routingSpan = uniqid('pf_', true);
			$this->dispatchEvent(new KernelSpanStarted(
				$routingSpan,
				'Kernel::RoutingMiddleware',
				'RoutingMiddleware::process'
			));
		}

		try {
			if (!defined('BASE_PATH')) {
				define('BASE_PATH', dirname(__DIR__, self::FRAMEWORK_ROOT_DEPTH) . DIRECTORY_SEPARATOR . self::APPLICATION_ROOT_DIRECTORY);
			}

			$moduleRegistry = new ModuleRegistry();

			if ($this->container->has(ModuleRegistry::class)) {
				$moduleRegistry = $this->container->get(ModuleRegistry::class);
			}

			if (!$moduleRegistry instanceof ModuleRegistry) {
				throw new RuntimeException('The module registry container binding is invalid.');
			}

			$routeRegistry = new RouteRegistry(BASE_PATH, $this->eventDispatcher, $moduleRegistry);
			$router = $routeRegistry->load();
			$router->setContainer($this->container);
			$this->synchronizeLegacyRequestState($request);
			$routerResult = $router->handle();

			if ($routerResult === false) {
				$this->dispatchEvent(new RoutingLifecycleEvent(RoutingLifecycleEvent::FALLBACK_TRIGGERED));
				$this->dispatchEvent(new RoutingLifecycleEvent(RoutingLifecycleEvent::MIDDLEWARE_FINISHED));
				return $handler->handle($request);
			}

			$response = $this->normalizeRouterResult($routerResult);
			$this->dispatchEvent(new RoutingLifecycleEvent(RoutingLifecycleEvent::RESPONSE_RESOLVED));
			$this->dispatchEvent(new RoutingLifecycleEvent(RoutingLifecycleEvent::MIDDLEWARE_FINISHED));
			return $response;
		} catch (\Throwable $e) {
			$this->dispatchEvent(new RoutingLifecycleEvent(RoutingLifecycleEvent::MIDDLEWARE_FINISHED, ['exception' => $e->getMessage()]));
			throw $e;
		} finally {
			if ($profilerEnabled) {
				$this->dispatchEvent(new KernelSpanFinished($routingSpan));
			}
		}
	}

	/**
	 * @param callable(object): void|null $eventDispatcher
	 */
	public function setEventDispatcher(callable|null $eventDispatcher): static
	{
		$this->eventDispatcher = $eventDispatcher;
		return $this;
	}

	private function synchronizeLegacyRequestState(Request $request): void
	{
		$_GET = $request->get;
		$_POST = $request->post;
		$_FILES = $request->files;
		$_COOKIE = $request->cookie;

		$server = [];
		foreach ($request->server as $key => $value) {
			if (is_string($key)) {
				$server[strtoupper($key)] = $value;
				continue;
			}

			$server[$key] = $value;
		}

		$_SERVER = $server;
		$_SERVER['REQUEST_METHOD'] = isset($_SERVER['REQUEST_METHOD']) && is_string($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== ''
			? strtoupper((string) $_SERVER['REQUEST_METHOD'])
			: 'GET';
		$_SERVER['REQUEST_URI'] = isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] !== ''
			? (string) $_SERVER['REQUEST_URI']
			: '/';
		$_SERVER['HTTP_HOST'] = isset($_SERVER['HTTP_HOST']) && is_string($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== ''
			? (string) $_SERVER['HTTP_HOST']
			: 'localhost';
		$_SERVER['REMOTE_ADDR'] = isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] !== ''
			? (string) $_SERVER['REMOTE_ADDR']
			: '127.0.0.1';
		$_SERVER['QUERY_STRING'] = isset($_SERVER['QUERY_STRING']) && is_string($_SERVER['QUERY_STRING'])
			? (string) $_SERVER['QUERY_STRING']
			: '';
		$_SERVER['SCRIPT_NAME'] = isset($_SERVER['SCRIPT_NAME']) && is_string($_SERVER['SCRIPT_NAME']) && $_SERVER['SCRIPT_NAME'] !== ''
			? (string) $_SERVER['SCRIPT_NAME']
			: '/index.php';

		LegacyRequest::clearRoutingPathQueryKeys();
	}

	private function normalizeRouterResult(mixed $routerResult): Response
	{
		if ($routerResult instanceof Response) {
			return $routerResult;
		}

		if (is_array($routerResult)) {
			$statusCode = is_int($routerResult['code'] ?? null) ? $routerResult['code'] : 200;
			return new Response($routerResult, [], 'json', $statusCode);
		}

		if (is_scalar($routerResult) || $routerResult instanceof \Stringable) {
			return new Response((string) $routerResult, [], 'html');
		}

		return new Response('', [], 'html', 204);
	}

	private function dispatchEvent(object $event): void
	{
		if ($this->eventDispatcher === null) {
			return;
		}

		($this->eventDispatcher)($event);
	}
}
