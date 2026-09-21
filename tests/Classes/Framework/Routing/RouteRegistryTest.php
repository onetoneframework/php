<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Routing;

use Clover\Classes\Directory\Handler as DirectoryHandler;
use Clover\Classes\DependencyInjection\Container;
use Clover\Classes\File\Handler as FileHandler;
use Clover\Classes\Routing\Router;
use Clover\Framework\Event\RoutingLifecycleEvent;
use Clover\Framework\Module\ModuleRegistry;
use Clover\Framework\Routing\RouteRegistry;
use Clover\Implement\ModuleInterface;
use PHPUnit\Framework\TestCase;
use function array_map;
use function in_array;
use function is_array;
use function sprintf;
use function sys_get_temp_dir;
use function tempnam;
use function var_export;
use const DIRECTORY_SEPARATOR;

final class RouteRegistryTest extends TestCase
{
	private string $temporaryDirectory = '';

	protected function setUp(): void
	{
		$_ENV['ROUTE_CACHE'] = 'false';
		$_ENV['ROUTE_CACHE_PATH'] = '';
		$this->temporaryDirectory = DirectoryHandler::createTemporary('route-registry-');
	}

	protected function tearDown(): void
	{
		$_ENV['ROUTE_CACHE'] = 'false';
		$_ENV['ROUTE_CACHE_PATH'] = '';

		if ($this->temporaryDirectory !== '' && DirectoryHandler::exists($this->temporaryDirectory)) {
			DirectoryHandler::delete($this->temporaryDirectory);
		}
	}

	public function testResolvesDefaultCachePathUnderApplicationCacheDirectory(): void
	{
		$registry = new RouteRegistry($this->temporaryDirectory);

		$this->assertSame(
			$this->path($this->temporaryDirectory, 'App', 'Cache', 'routes.cache.php'),
			$registry->getCachePath()
		);
	}

	public function testConfiguredCachePathIsNormalizedToPlatformSeparators(): void
	{
		$_ENV['ROUTE_CACHE_PATH'] = 'var/cache/routes.cache.php';
		$registry = new RouteRegistry($this->temporaryDirectory);

		$this->assertSame(
			implode(DIRECTORY_SEPARATOR, ['var', 'cache', 'routes.cache.php']),
			$registry->getCachePath()
		);
	}

	public function testNonStringConfiguredCachePathFallsBackToDefault(): void
	{
		$_ENV['ROUTE_CACHE_PATH'] = ['invalid'];
		$registry = new RouteRegistry($this->temporaryDirectory);

		$this->assertSame(
			$this->path($this->temporaryDirectory, 'App', 'Cache', 'routes.cache.php'),
			$registry->getCachePath()
		);
	}

	public function testLoadsCachedRoutesWhenCacheFileIsAvailable(): void
	{
		$cachePath = $this->temporaryFilePath();
		$this->assertTrue(FileHandler::write($cachePath, $this->cachePayload($this->cachedRoutes())));
		$_ENV['ROUTE_CACHE'] = 'true';
		$_ENV['ROUTE_CACHE_PATH'] = $cachePath;
		$events = [];

		$registry = new RouteRegistry($this->temporaryDirectory, static function (object $event) use (&$events): void {
			$events[] = $event;
		});
		$router = $registry->load();

		$this->assertSame(['/cached'], $router->listRoutes()['GET'] ?? []);
		$this->assertContains(RoutingLifecycleEvent::CACHE_HIT, $this->eventTypes($events));
		$this->assertNotContains(RoutingLifecycleEvent::CACHE_WRITE_STARTED, $this->eventTypes($events));

		if (FileHandler::isExists($cachePath)) {
			$this->assertTrue(FileHandler::delete($cachePath));
		}
	}

	public function testInvalidCachePayloadFallsBackToRouteDiscoveryAndRewritesCache(): void
	{
		$cachePath = $this->temporaryFilePath();
		$this->assertTrue(FileHandler::write($cachePath, "<?php\nreturn true;\n"));
		$_ENV['ROUTE_CACHE'] = 'true';
		$_ENV['ROUTE_CACHE_PATH'] = $cachePath;
		$events = [];

		$registry = new RouteRegistry($this->temporaryDirectory, static function (object $event) use (&$events): void {
			$events[] = $event;
		});
		$router = $registry->load();
		$routes = $router->listRoutes();

		$this->assertTrue(in_array('/profiler', $routes['GET'] ?? [], true));
		$this->assertContains(RoutingLifecycleEvent::CACHE_MISS, $this->eventTypes($events));
		$this->assertContains(RoutingLifecycleEvent::CACHE_WRITE_FINISHED, $this->eventTypes($events));

		/** @var mixed $cached */
		$cached = include $cachePath;
		$this->assertIsArray($cached);
		$this->assertTrue($this->cacheContainsPattern($cached, '/profiler'));

		if (FileHandler::isExists($cachePath)) {
			$this->assertTrue(FileHandler::delete($cachePath));
		}
	}

	public function testLegacyCacheIsRebuiltWhenAModuleIsEnabled(): void
	{
		$cachePath = $this->temporaryFilePath();
		$this->assertTrue(FileHandler::write($cachePath, $this->cachePayload($this->cachedRoutes())));
		$_ENV['ROUTE_CACHE'] = 'true';
		$_ENV['ROUTE_CACHE_PATH'] = $cachePath;
		$events = [];
		$enabledModules = new ModuleRegistry();
		$enabledModules->register(new RouteRegistryFixtureModule('board'));
		$registry = new RouteRegistry(
			$this->temporaryDirectory,
			static function (object $event) use (&$events): void {
				$events[] = $event;
			},
			$enabledModules
		);

		$router = $registry->load();

		$this->assertTrue(in_array('/module-board', $router->listRoutes()['GET'] ?? [], true));
		$this->assertFalse(in_array('/cached', $router->listRoutes()['GET'] ?? [], true));
		$this->assertContains(RoutingLifecycleEvent::CACHE_MISS, $this->eventTypes($events));
		$this->assertNotContains(RoutingLifecycleEvent::CACHE_HIT, $this->eventTypes($events));

		if (FileHandler::isExists($cachePath)) {
			$this->assertTrue(FileHandler::delete($cachePath));
		}
	}

	public function testModuleChangeInvalidatesCachedRoutes(): void
	{
		$cachePath = $this->path($this->temporaryDirectory, 'routes.cache.php');
		$_ENV['ROUTE_CACHE'] = 'true';
		$_ENV['ROUTE_CACHE_PATH'] = $cachePath;
		$enabledModules = new ModuleRegistry();
		$enabledModules->register(new RouteRegistryFixtureModule('board'));

		$enabledRouter = (new RouteRegistry($this->temporaryDirectory, null, $enabledModules))->load();

		$this->assertTrue(in_array('/module-board', $enabledRouter->listRoutes()['GET'] ?? [], true));

		$events = [];
		$disabledModules = new ModuleRegistry();
		$disabledRouter = (new RouteRegistry(
			$this->temporaryDirectory,
			static function (object $event) use (&$events): void {
				$events[] = $event;
			},
			$disabledModules
		))->load();

		$this->assertFalse(in_array('/module-board', $disabledRouter->listRoutes()['GET'] ?? [], true));
		$this->assertContains(RoutingLifecycleEvent::CACHE_MISS, $this->eventTypes($events));
		$this->assertNotContains(RoutingLifecycleEvent::CACHE_HIT, $this->eventTypes($events));
	}

	/**
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	private function cachedRoutes(): array
	{
		return [
			'GET' => [
				[
					'pattern' => '/cached',
					'callback' => RouteRegistryCachedController::class . '::show',
					'middleware' => [],
					'host' => '*',
					'contentType' => '*',
					'requiredQuery' => [],
					'pathQueryKey' => '',
				],
			],
		];
	}

	/**
	 * @param array<string, array<int, array<string, mixed>>> $routes
	 */
	private function cachePayload(array $routes): string
	{
		return sprintf("<?php\nreturn %s;\n", var_export($routes, true));
	}

	/**
	 * @param object[] $events
	 * @return string[]
	 */
	private function eventTypes(array $events): array
	{
		return array_map(
			static fn(object $event): string => $event instanceof RoutingLifecycleEvent ? $event->type : $event::class,
			$events
		);
	}

	/**
	 * @param array<string, mixed> $cached
	 */
	private function cacheContainsPattern(array $cached, string $pattern): bool
	{
		if (isset($cached['routes']) && is_array($cached['routes'])) {
			$cached = $cached['routes'];
		}

		foreach ($cached as $routes) {
			if (!is_array($routes)) {
				continue;
			}

			foreach ($routes as $route) {
				if (is_array($route) && ($route['pattern'] ?? null) === $pattern) {
					return true;
				}
			}
		}

		return false;
	}

	private function temporaryFilePath(): string
	{
		$path = tempnam(sys_get_temp_dir(), 'route-registry-cache-');

		if ($path === false) {
			$this->fail('Failed to create a temporary cache file.');
		}

		return $path;
	}

	private function path(string $basePath, string ...$segments): string
	{
		$path = $basePath;

		foreach ($segments as $segment) {
			$path = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . trim($segment, '/\\');
		}

		return $path;
	}
}

final class RouteRegistryCachedController
{
	public function show(): string
	{
		return 'cached';
	}
}

final class RouteRegistryFixtureModule implements ModuleInterface
{
	public function __construct(private readonly string $name)
	{
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDependencies(): array
	{
		return [];
	}

	public function getExportedServiceIdentifiers(): array
	{
		return [];
	}

	public function register(Container $container): void
	{
	}

	public function boot(Container $container): void
	{
	}

	public function registerRoutes(Router $router): void
	{
		$router->get('/module-' . $this->name, RouteRegistryCachedController::class . '::show');
	}
}
