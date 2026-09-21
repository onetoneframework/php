<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Routing;

use App\Provider\GraphQLRouteProvider;
use App\Provider\ProfilerRouteProvider;
use Clover\Classes\Directory\Handler as DirectoryHandler;
use Clover\Classes\Debug\Profiler;
use Clover\Classes\File\Handler as FileHandler;
use Clover\Classes\OperationSystem;
use Clover\Classes\Routing\Router;
use Clover\Framework\Event\KernelSpanFinished;
use Clover\Framework\Event\KernelSpanStarted;
use Clover\Framework\Event\RoutingLifecycleEvent;
use Clover\Framework\Livewire\LivewireRouteProvider;
use Clover\Framework\Module\ModuleRegistry;
use RuntimeException;
use function array_key_exists;
use function dirname;
use function filter_var;
use function is_array;
use function is_string;
use function sprintf;
use function str_replace;
use function uniqid;
use function var_export;
use const DIRECTORY_SEPARATOR;
use const FILTER_VALIDATE_BOOL;

final class RouteRegistry
{
	private const CACHE_DIRECTORY_PERMISSION = 0777;
	private const CACHE_FORMAT_VERSION = 2;
	private const CACHE_FORMAT_VERSION_KEY = 'format_version';
	private const CACHE_MODULE_NAMES_KEY = 'module_names';
	private const CACHE_ROUTES_KEY = 'routes';

	/** @var callable(object): void|null */
	private $eventDispatcher;
	private readonly ModuleRegistry $moduleRegistry;

	public function __construct(
		private readonly string $basePath,
		callable|null $eventDispatcher = null,
		ModuleRegistry|null $moduleRegistry = null
	) {
		$this->eventDispatcher = $eventDispatcher;
		$this->moduleRegistry = $moduleRegistry ?? new ModuleRegistry();
	}

	public function load(): Router
	{
		$router = new Router();
		$cachePath = $this->getCachePath();
		$cacheEnabled = $this->isCacheEnabled();

		if ($cacheEnabled && FileHandler::isExists($cachePath)) {
			if ($this->loadCachedRoutes($router, $cachePath)) {
				return $router;
			}
		} else {
			$this->dispatchEvent(new RoutingLifecycleEvent(RoutingLifecycleEvent::CACHE_MISS, ['path' => $cachePath]));
		}

		$this->registerDiscoveredRoutes($router);

		if ($cacheEnabled) {
			$this->writeCache($router, $cachePath);
		}

		return $router;
	}

	public function getCachePath(): string
	{
		$configuredPath = $_ENV['ROUTE_CACHE_PATH'] ?? '';

		if (is_string($configuredPath) && $configuredPath !== '') {
			return $this->normalizePath($configuredPath);
		}

		return $this->joinPath($this->basePath, 'App', 'Cache', 'routes.cache.php');
	}

	private function isCacheEnabled(): bool
	{
		return filter_var($_ENV['ROUTE_CACHE'] ?? false, FILTER_VALIDATE_BOOL) === true;
	}

	private function loadCachedRoutes(Router $router, string $cachePath): bool
	{
		$profilerEnabled = Profiler::isEnabled();
		$cacheSpan = '';
		if ($profilerEnabled) {
			$cacheSpan = uniqid('pf_', true);
			$this->dispatchEvent(new KernelSpanStarted($cacheSpan, 'Kernel::RoutingCacheLoad', $cachePath));
		}

		try {
			/** @var mixed $cached */
			$cached = include $cachePath;

			if (!is_array($cached)) {
				$this->dispatchEvent(new RoutingLifecycleEvent(RoutingLifecycleEvent::CACHE_MISS, [
					'path' => $cachePath,
					'reason' => 'invalid_cache_payload',
				]));

				return false;
			}

			$cachedRoutes = $cached;

			if (
				!array_key_exists(self::CACHE_FORMAT_VERSION_KEY, $cached)
				&& $this->moduleRegistry->names() !== []
			) {
				$this->dispatchEvent(new RoutingLifecycleEvent(RoutingLifecycleEvent::CACHE_MISS, [
					'path' => $cachePath,
					'reason' => 'module_metadata_missing',
				]));

				return false;
			}

			if (array_key_exists(self::CACHE_FORMAT_VERSION_KEY, $cached)) {
				if (
					($cached[self::CACHE_FORMAT_VERSION_KEY] ?? null) !== self::CACHE_FORMAT_VERSION
					|| !is_array($cached[self::CACHE_MODULE_NAMES_KEY] ?? null)
					|| !is_array($cached[self::CACHE_ROUTES_KEY] ?? null)
				) {
					$this->dispatchEvent(new RoutingLifecycleEvent(RoutingLifecycleEvent::CACHE_MISS, [
						'path' => $cachePath,
						'reason' => 'invalid_cache_metadata',
					]));

					return false;
				}

				if ($cached[self::CACHE_MODULE_NAMES_KEY] !== $this->moduleRegistry->names()) {
					$this->dispatchEvent(new RoutingLifecycleEvent(RoutingLifecycleEvent::CACHE_MISS, [
						'path' => $cachePath,
						'reason' => 'module_configuration_changed',
					]));

					return false;
				}

				$cachedRoutes = $cached[self::CACHE_ROUTES_KEY];
			}

			$router->fromCachedArray($cachedRoutes);
			$this->dispatchEvent(new RoutingLifecycleEvent(RoutingLifecycleEvent::CACHE_HIT, ['path' => $cachePath]));

			return true;
		} finally {
			if ($profilerEnabled) {
				$this->dispatchEvent(new KernelSpanFinished($cacheSpan));
			}
		}
	}

	private function registerDiscoveredRoutes(Router $router): void
	{
		$profilerEnabled = Profiler::isEnabled();
		$scanSpan = '';
		if ($profilerEnabled) {
			$scanSpan = uniqid('pf_', true);
			$this->dispatchEvent(new KernelSpanStarted($scanSpan, 'Kernel::RoutingScan', 'RouteRegistry::registerDiscoveredRoutes'));
		}

		try {
			$this->loadRouteDirectory($router, $this->joinPath($this->basePath, 'App', 'Controller'));

			if (OperationSystem::isCommandLineInterface()) {
				$this->loadRouteDirectory($router, $this->joinPath($this->basePath, 'App', 'Command'));
			}

			$this->loadRouteDirectory($router, $this->joinPath(dirname($this->basePath), 'src', 'Command'));

			ProfilerRouteProvider::register($router);
			GraphQLRouteProvider::register($router);
			LivewireRouteProvider::register($router);

			foreach ($this->moduleRegistry->all() as $module) {
				$module->registerRoutes($router);
			}
		} finally {
			if ($profilerEnabled) {
				$this->dispatchEvent(new KernelSpanFinished($scanSpan));
			}
		}
	}

	private function loadRouteDirectory(Router $router, string $path): void
	{
		if (!DirectoryHandler::exists($path)) {
			return;
		}

		$router->fromDirectory($path);
	}

	private function writeCache(Router $router, string $cachePath): void
	{
		$this->dispatchEvent(new RoutingLifecycleEvent(RoutingLifecycleEvent::CACHE_WRITE_STARTED, ['path' => $cachePath]));
		$profilerEnabled = Profiler::isEnabled();
		$writeCacheSpan = '';
		if ($profilerEnabled) {
			$writeCacheSpan = uniqid('pf_', true);
			$this->dispatchEvent(new KernelSpanStarted($writeCacheSpan, 'Kernel::RoutingCacheWrite', $cachePath));
		}

		try {
			$cachePayload = [
				self::CACHE_FORMAT_VERSION_KEY => self::CACHE_FORMAT_VERSION,
				self::CACHE_MODULE_NAMES_KEY => $this->moduleRegistry->names(),
				self::CACHE_ROUTES_KEY => $router->toArray(),
			];
			$exportString = var_export($cachePayload, true);
			$directoryPath = dirname($cachePath);
			DirectoryHandler::ensureExists($directoryPath, self::CACHE_DIRECTORY_PERMISSION);
			$php = sprintf("<?php\nreturn %s;\n", $exportString);

			if (!FileHandler::write($cachePath, $php)) {
				throw new RuntimeException(sprintf('Failed to write route cache: %s', $cachePath));
			}

			$this->dispatchEvent(new RoutingLifecycleEvent(RoutingLifecycleEvent::CACHE_WRITE_FINISHED, ['path' => $cachePath]));
		} finally {
			if ($profilerEnabled) {
				$this->dispatchEvent(new KernelSpanFinished($writeCacheSpan));
			}
		}
	}

	private function joinPath(string $basePath, string ...$segments): string
	{
		$path = $this->normalizePath($basePath);

		foreach ($segments as $segment) {
			$trimmedSegment = trim($segment, '/\\');

			if ($trimmedSegment === '') {
				continue;
			}

			$path = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $trimmedSegment;
		}

		return $path;
	}

	private function normalizePath(string $path): string
	{
		return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
	}

	private function dispatchEvent(object $event): void
	{
		if ($this->eventDispatcher === null) {
			return;
		}

		($this->eventDispatcher)($event);
	}
}
