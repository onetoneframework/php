<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Framework\Component;

use Clover\Classes\DependencyInjection\Container;
use Clover\Classes\Event\Dispatcher;
use Clover\Classes\Event\EventManager;
use Clover\Framework\Component\HttpKernel;
use Clover\Framework\Component\Request;
use Clover\Framework\Component\Response;
use Clover\Framework\Component\TraceContext;
use Clover\Framework\Context\ApplicationContext;
use Clover\Framework\Event\AfterResponseSend;
use Clover\Framework\Event\KernelSpanFinished;
use Clover\Framework\Event\KernelSpanStarted;
use PHPUnit\Framework\TestCase;

final class ResponseCompletionProbeKernel extends HttpKernel
{
	/** @var array<int, string> */
	public array $executionOrder = [];

	public function runWithFPM(): void
	{
		$this->executionOrder[] = 'response';
		$this->publishAfterResponseSend();
	}
}

final class ResponsePreparationProbeKernel extends HttpKernel
{
	public function prepareForTransport(Response $response): Response
	{
		return $this->prepareResponse($response);
	}
}

class HttpKernelTest extends TestCase
{
    private string $cacheFile = '';

    protected function tearDown(): void
    {
        $_ENV['APP_MAINTENANCE'] = 'false';
        $_ENV['ROUTE_CACHE'] = 'false';
        $_ENV['ROUTE_CACHE_PATH'] = '';
		unset($_ENV['PROFILER_ENABLED']);
        ApplicationContext::clearInterceptors();
        ApplicationContext::clearEnvironment();
        EventManager::clearInstance();

        if ($this->cacheFile !== '' && file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
            $this->cacheFile = '';
        }
    }

    public function testHandleRequestReturnsResponse(): void
    {
        $container = $this->createMock(Container::class);
        $dispatcher = $this->createMock(Dispatcher::class);

        $kernel = new HttpKernel($dispatcher, $container);

        $request = new Request([], [], [], [], ['REQUEST_URI' => '/'], '');

        $_ENV['APP_MAINTENANCE'] = 'false';
        $this->cacheFile = tempnam(sys_get_temp_dir(), 'http-kernel-route-cache-') . '.php';
        file_put_contents($this->cacheFile, "<?php\nreturn [];\n");
        $_ENV['ROUTE_CACHE'] = 'true';
        $_ENV['ROUTE_CACHE_PATH'] = $this->cacheFile;

        if (!defined('BASE_PATH')) {
            define('BASE_PATH', __DIR__ . '/../../../../root');
        }
        ApplicationContext::setContainer($container);

        $response = $kernel->handleRequest($request);
        $this->assertInstanceOf(Response::class, $response);
		$this->assertSame(404, $response->getStatusCode());
		$this->assertSame('Page not found', $response->getBody());
		$this->assertNotSame('', $response->getHeader('X-Trace-Id'));
    }

	public function testHandleRequestDoesNotDispatchProfilerSpansWhenProfilerIsDisabled(): void
	{
		$_ENV['PROFILER_ENABLED'] = 'false';
		$_ENV['APP_MAINTENANCE'] = 'false';
		$cacheFile = tempnam(sys_get_temp_dir(), 'http-kernel-route-cache-');
		if ($cacheFile === false) {
			self::fail('Failed to create a temporary route cache file.');
		}
		$this->cacheFile = $cacheFile;
		file_put_contents($this->cacheFile, "<?php\nreturn [];\n");
		$_ENV['ROUTE_CACHE'] = 'true';
		$_ENV['ROUTE_CACHE_PATH'] = $this->cacheFile;
		$spanEvents = [];
		$dispatcher = new Dispatcher();
		$dispatcher->addListener(KernelSpanStarted::class, static function (object $event) use (&$spanEvents): void {
			$spanEvents[] = $event;
		});
		$dispatcher->addListener(KernelSpanFinished::class, static function (object $event) use (&$spanEvents): void {
			$spanEvents[] = $event;
		});
		EventManager::setInstance($dispatcher);
		$container = $this->createMock(Container::class);
		ApplicationContext::setContainer($container);
		$kernel = new HttpKernel($dispatcher, $container);

		$response = $kernel->handleRequest(new Request([], [], [], [], ['REQUEST_URI' => '/'], ''));

		self::assertSame(404, $response->getStatusCode());
		self::assertSame([], $spanEvents);
	}

	public function testHandleRequestDispatchesBalancedProfilerSpansWhenProfilerIsEnabled(): void
	{
		$_ENV['PROFILER_ENABLED'] = 'true';
		$_ENV['APP_MAINTENANCE'] = 'false';
		$cacheFile = tempnam(sys_get_temp_dir(), 'http-kernel-route-cache-');
		if ($cacheFile === false) {
			self::fail('Failed to create a temporary route cache file.');
		}
		$this->cacheFile = $cacheFile;
		file_put_contents($this->cacheFile, "<?php\nreturn [];\n");
		$_ENV['ROUTE_CACHE'] = 'true';
		$_ENV['ROUTE_CACHE_PATH'] = $this->cacheFile;
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
		$container = $this->createMock(Container::class);
		ApplicationContext::setContainer($container);
		$kernel = new HttpKernel($dispatcher, $container);

		$response = $kernel->handleRequest(new Request([], [], [], [], ['REQUEST_URI' => '/'], ''));
		sort($startedTokens);
		sort($finishedTokens);

		self::assertSame(404, $response->getStatusCode());
		self::assertNotEmpty($startedTokens);
		self::assertSame($startedTokens, $finishedTokens);
	}

    public function testMaintenanceMode(): void
    {
        $_ENV['APP_MAINTENANCE'] = 'true';
        $_ENV['ROUTE_CACHE'] = 'false';
        $_ENV['ROUTE_CACHE_PATH'] = '';

        $container = $this->createMock(Container::class);
        $dispatcher = $this->createMock(Dispatcher::class);

        $kernel = new HttpKernel($dispatcher, $container);
        $request = new Request([], [], [], [], ['REQUEST_URI' => '/']);
        ApplicationContext::setContainer($container);

        $response = $kernel->handleRequest($request);

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame('Service unavailable', $response->getBody());
    }

    public function testMaintenanceModeOverridesRouteHandling(): void
    {
        $_ENV['APP_MAINTENANCE'] = 'true';
        $_ENV['ROUTE_CACHE'] = 'true';
        $_ENV['ROUTE_CACHE_PATH'] = __DIR__ . '/missing-cache.php';

        $container = $this->createMock(Container::class);
        $dispatcher = $this->createMock(Dispatcher::class);
        $kernel = new HttpKernel($dispatcher, $container);
        ApplicationContext::setContainer($container);

        $response = $kernel->handleRequest(new Request([], [], [], [], ['REQUEST_URI' => '/']));

        $this->assertSame(503, $response->getStatusCode());
    }

	public function testAfterResponseEventIsPublishedAfterTransportExecution(): void
	{
		$eventDispatcher = new Dispatcher();
		EventManager::setInstance($eventDispatcher);
		$kernel = new ResponseCompletionProbeKernel($eventDispatcher, new Container());

		EventManager::getEventBus()->subscribe(AfterResponseSend::class, static function () use ($kernel): void {
			$kernel->executionOrder[] = 'after-response';
		});

		$kernel->run();

		$this->assertSame(['response', 'after-response'], $kernel->executionOrder);
	}

	public function testResponsePreparationIsTransportNeutral(): void
	{
		$eventDispatcher = new Dispatcher();
		EventManager::setInstance($eventDispatcher);
		TraceContext::bootstrap(['HTTP_X_TRACE_ID' => 'transport-trace']);
		$kernel = new ResponsePreparationProbeKernel($eventDispatcher, new Container());
		$response = new Response(['ok' => true], [], 'json', 202);

		$preparedResponse = $kernel->prepareForTransport($response);

		$this->assertSame(202, $preparedResponse->getStatusCode());
		$this->assertSame('{"ok":true}', $preparedResponse->getBody());
		$this->assertSame('application/json; charset=utf-8', $preparedResponse->getHeader('Content-Type'));
	}
}
