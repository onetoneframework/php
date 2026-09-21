<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Routing;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Database\Driver\PHPDataObject;
use Clover\Classes\DependencyInjection\Container;
use Clover\Classes\Event\Dispatcher;
use Clover\Classes\Event\EventManager;
use Clover\Classes\Routing\Router;
use Clover\Classes\Routing\RouteAnnotationReader;
use Clover\Classes\Routing\RouteAnnotationReaderBuilder;
use Clover\Framework\Context\ApplicationContext;
use Clover\Framework\Event\KernelSpanFinished;
use Clover\Framework\Event\KernelSpanStarted;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RouterTest extends TestCase
{
    protected function tearDown(): void
    {
        $_SERVER = [];
		unset($_ENV['PROFILER_ENABLED']);
		EventManager::clearInstance();
    }

    public function testRouting(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['CONTENT_TYPE'] = 'text/plain';

        $router = new Router();
        $router->get('/test', function () {
            return 'This is test page';
        });
        $response = $router->handle();

        $this->assertSame('This is test page', $response);
    }

    public function testDispatchReturnsNotFoundPayloadWhenNoRoute(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/missing';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['CONTENT_TYPE'] = 'text/plain';

        $router = new Router();
        $response = $router->dispatch();

        $this->assertSame(['error' => 'Not Found', 'code' => 404], $response);
    }

    public function testHandleReturnsMethodNotAllowedWithAllowedMethods(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/resource';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'text/plain';

        $router = new Router();
        $router->get('/resource', static fn() => 'ok');

        $response = $router->handle();

        $this->assertIsArray($response);
        $this->assertSame(405, $response['code']);
        $this->assertSame('Method Not Allowed', $response['error']);
        $this->assertSame(['GET'], $response['allowed']);
    }

    public function testBeforeHookCanAbortRouting(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/hooked';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['CONTENT_TYPE'] = 'text/plain';

        $router = new Router();
        $router->before(static fn() => false);
        $router->get('/hooked', static fn() => 'never');

        $this->assertFalse($router->handle());
    }

	public function testHandleDoesNotDispatchProfilerSpansWhenProfilerIsDisabled(): void
	{
		$_ENV['PROFILER_ENABLED'] = 'false';
		$_SERVER['HTTP_HOST'] = 'localhost';
		$_SERVER['REQUEST_URI'] = '/without-profiler';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['CONTENT_TYPE'] = 'text/plain';
		$spanEvents = [];
		$dispatcher = new Dispatcher();
		$dispatcher->addListener(KernelSpanStarted::class, static function (object $event) use (&$spanEvents): void {
			$spanEvents[] = $event;
		});
		$dispatcher->addListener(KernelSpanFinished::class, static function (object $event) use (&$spanEvents): void {
			$spanEvents[] = $event;
		});
		EventManager::setInstance($dispatcher);
		$router = new Router();
		$router->get('/without-profiler', static fn(): string => 'ok');

		$result = $router->handle();

		self::assertSame('ok', $result);
		self::assertSame([], $spanEvents);
	}

	public function testHandleClosesRouterAndExecutorSpansWhenRouteThrows(): void
	{
		$_ENV['PROFILER_ENABLED'] = 'true';
		$_SERVER['HTTP_HOST'] = 'localhost';
		$_SERVER['REQUEST_URI'] = '/profiler-error';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['CONTENT_TYPE'] = 'text/plain';
		$startedTokens = [];
		$finishedTokens = [];
		$dispatcher = new Dispatcher();
		$dispatcher->addListener(KernelSpanStarted::class, static function (KernelSpanStarted $event) use (&$startedTokens): void {
			$startedTokens[] = $event->token;
		});
		$dispatcher->addListener(KernelSpanFinished::class, static function (KernelSpanFinished $event) use (&$finishedTokens): void {
			$finishedTokens[] = $event->token;
		});
		EventManager::setInstance($dispatcher);
		$router = new Router();
		$router->get('/profiler-error', static function (): string {
			throw new RuntimeException('Route failed.');
		});

		$this->expectException(RuntimeException::class);

		try {
			$router->handle();
		} finally {
			sort($startedTokens);
			sort($finishedTokens);
			self::assertCount(2, $startedTokens);
			self::assertSame($startedTokens, $finishedTokens);
		}
	}

    public function testRouterBuildsAnnotationReaderViaBuilder(): void
    {
        $customReader = new RouteAnnotationReader();
        $builder = RouteAnnotationReaderBuilder::create()->withAnnotationReader($customReader);
        $router = new Router(null, $builder);

        $reflection = new ReflectionClass($router);
        $property = $reflection->getProperty('annotationReader');

        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $property->setAccessible(true);
        }

        $this->assertSame($customReader, $property->getValue($router));
    }

	public function testGroupRegistersNestedRoutePrefixes(): void
	{
		$_SERVER['HTTP_HOST'] = 'localhost';
		$_SERVER['REQUEST_URI'] = '/api/v1/users';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['CONTENT_TYPE'] = 'text/plain';

		$router = new Router();
		$router->group('/api', static function (Router $groupedRouter): void {
			$groupedRouter->group('/v1', static function (Router $versionedRouter): void {
				$versionedRouter->get('/users', static fn(): string => 'grouped');
			});
		});

		$this->assertSame(['/api/v1/users'], $router->listRoutes()['GET']);
		$this->assertSame('grouped', $router->handle());
	}

	public function testGroupRestoresStateWhenRegistrationThrows(): void
	{
		$router = new Router();

		try {
			$router->group('/api', static function (Router $groupedRouter): void {
				$groupedRouter->get('/partial', static fn(): string => 'partial');

				throw new \RuntimeException('Route registration failed.');
			});

			$this->fail('The route group callback should propagate its exception.');
		} catch (\RuntimeException $exception) {
			$this->assertSame('Route registration failed.', $exception->getMessage());
		}

		$router->get('/health', static fn(): string => 'healthy');

		$this->assertSame(['/api/partial', '/health'], $router->listRoutes()['GET']);
	}

	public function testGroupPreservesNonCallableNoOpBehavior(): void
	{
		$router = new Router();
		$router->group('/ignored', new \stdClass());
		$router->get('/health', static fn(): string => 'healthy');

		$this->assertSame(['/health'], $router->listRoutes()['GET']);
	}

	public function testRouteDiscoveryDefersStaticDependencyResolutionUntilExecution(): void
	{
		$_SERVER['HTTP_HOST'] = 'localhost';
		$_SERVER['REQUEST_URI'] = '/lazy-injection';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['CONTENT_TYPE'] = 'text/plain';
		$resolutionCount = 0;
		$container = new Container();
		$container->set(
			PHPDataObject::class,
			static function (Container $container) use (&$resolutionCount): PHPDataObject {
				$resolutionCount++;

				return new PHPDataObject();
			}
		);
		ApplicationContext::setContainer($container);
		$router = new Router();
		$router->setContainer($container);
		$fixturePath = __DIR__
			. DIRECTORY_SEPARATOR
			. 'LazyInjectionControllerFixture.php';

		$router->fromFile($fixturePath);

		$this->assertSame(0, $resolutionCount);
		$this->assertSame('injected', $router->handle());
		$this->assertSame(1, $resolutionCount);
	}
}
