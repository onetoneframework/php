<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Component\Kernel;

use Clover\Component\Contract\ExceptionHandlerInterface;
use Clover\Component\Contract\MiddlewareInterface;
use Clover\Component\Contract\RequestHandlerInterface;
use Clover\Component\Contract\TerminableMiddlewareInterface;
use Clover\Component\Foundation\Application;
use Clover\Component\Http\Request;
use Clover\Component\Http\Response;
use Clover\Component\Kernel\HttpKernel;
use Clover\Component\Routing\Router;
use Clover\Contract\KernelInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

/**
 * The kernel lifecycle: start-up, the middleware pipeline, termination and failure rendering.
 *
 * None of this existed before - the kernel dispatched straight to the router with the bootstrapper
 * list commented out - so every case here is new behaviour rather than a regression guard.
 */
final class HttpKernelLifecycleTest extends TestCase
{
	protected function tearDown(): void
	{
		Application::setInstance(null);
		KernelLifecycleLog::$entries = [];
	}

	public function testApplicationCanBindItsOwnKernelOverTheFrameworkDefault(): void
	{
		$application = new Application();

		$this->assertInstanceOf(HttpKernel::class, $application->make(KernelInterface::class));

		$own = new LifecycleTestKernel($application);
		$application->singleton(KernelInterface::class, $own);

		$this->assertSame($own, $application->make(KernelInterface::class));
	}

	public function testBootstrappersRunOnceInOrder(): void
	{
		$application = new Application();
		$kernel = new RecordingBootstrapKernel($application);

		$kernel->bootstrap();
		$kernel->bootstrap();

		$this->assertSame(['first', 'second'], KernelLifecycleLog::$entries);
	}

	public function testGlobalMiddlewareWrapsTheRoute(): void
	{
		$application = $this->applicationWithRoute('/ping', static fn (): string => 'pong');
		$kernel = new LifecycleTestKernel($application);
		$kernel->setMiddleware([StampMiddleware::class]);

		$response = $kernel->handle($this->request('GET', '/ping'));

		$this->assertSame('pong', $response->getBody());
		$this->assertSame('yes', $response->getHeader('x-stamped'));
	}

	/**
	 * The route decides which middleware run, so matching has to happen before the pipeline is
	 * built - and a layer that refuses must stop the action being reached at all.
	 */
	public function testRouteMiddlewareCanRefuseBeforeTheActionRuns(): void
	{
		$application = $this->applicationWithRoute(
			'/guarded',
			static function (): string {
				KernelLifecycleLog::$entries[] = 'action ran';

				return 'secret';
			},
			['block']
		);

		$kernel = new LifecycleTestKernel($application);
		$kernel->setRouteMiddleware(['block' => RefusingMiddleware::class]);

		$response = $kernel->handle($this->request('GET', '/guarded'));

		$this->assertSame(403, $response->getStatusCode());
		$this->assertSame([], KernelLifecycleLog::$entries);
	}

	public function testMiddlewareGroupExpandsAndMayContainAliases(): void
	{
		$application = $this->applicationWithRoute('/grouped', static fn (): string => 'ok', ['web']);
		$kernel = new LifecycleTestKernel($application);
		$kernel->setRouteMiddleware(['stamp' => StampMiddleware::class]);
		$kernel->setMiddlewareGroups(['web' => ['stamp']]);

		$response = $kernel->handle($this->request('GET', '/grouped'));

		$this->assertSame('yes', $response->getHeader('x-stamped'));
	}

	/**
	 * A group containing itself would expand until the process ran out of memory, and a stack
	 * overflow does not name the group at fault.
	 */
	public function testSelfReferencingMiddlewareGroupIsRefusedByName(): void
	{
		$application = $this->applicationWithRoute('/loop', static fn (): string => 'ok', ['web']);
		$kernel = new LifecycleTestKernel($application);
		$kernel->setMiddlewareGroups(['web' => ['web']]);
		$kernel->setExceptionHandler($handler = new RecordingExceptionHandler());

		$response = $kernel->handle($this->request('GET', '/loop'));

		// The kernel contains the failure rather than letting it escape into the web server, so the
		// proof that it was refused by name is what reached the exception handler.
		$this->assertSame(500, $response->getStatusCode());
		$this->assertSame(
			['reported: Middleware group `web` contains itself.'],
			KernelLifecycleLog::$entries
		);
		$this->assertInstanceOf(RuntimeException::class, $handler->lastThrowable);
	}

	/**
	 * An exception handler that throws must not replace the original failure with its own, or a
	 * broken handler would take down every request with a message about the handler.
	 */
	public function testAThrowingExceptionHandlerStillProducesAResponse(): void
	{
		$application = $this->applicationWithRoute('/boom', static function (): string {
			throw new RuntimeException('action exploded');
		});

		$kernel = new LifecycleTestKernel($application);
		$kernel->setExceptionHandler(new RethrowingExceptionHandler());

		$response = $kernel->handle($this->request('GET', '/boom'));

		$this->assertSame(500, $response->getStatusCode());
		$this->assertSame('Server Error', $response->getBody());
	}

	public function testUnmatchedRequestStillAnswersWithoutMiddleware(): void
	{
		$application = $this->applicationWithRoute('/only', static fn (): string => 'ok');
		$kernel = new LifecycleTestKernel($application);

		$this->assertSame(404, $kernel->handle($this->request('GET', '/missing'))->getStatusCode());
	}

	public function testTerminableMiddlewareRunsOnTerminate(): void
	{
		$application = $this->applicationWithRoute('/ping', static fn (): string => 'pong');
		$kernel = new LifecycleTestKernel($application);
		$kernel->setMiddleware([TerminableStampMiddleware::class]);

		$kernel->handle($this->request('GET', '/ping'));

		$this->assertSame([], KernelLifecycleLog::$entries, 'terminate must not run during handle');

		$kernel->terminate();

		$this->assertSame(['terminated'], KernelLifecycleLog::$entries);
	}

	public function testTerminateIsNotRepeatedForAnAlreadyTerminatedRequest(): void
	{
		$application = $this->applicationWithRoute('/ping', static fn (): string => 'pong');
		$kernel = new LifecycleTestKernel($application);
		$kernel->setMiddleware([TerminableStampMiddleware::class]);

		$kernel->handle($this->request('GET', '/ping'));
		$kernel->terminate();
		$kernel->terminate();

		$this->assertSame(['terminated'], KernelLifecycleLog::$entries);
	}

	public function testThrowableFromTheActionIsRenderedByTheBoundHandler(): void
	{
		$application = $this->applicationWithRoute('/boom', static function (): string {
			throw new RuntimeException('action exploded');
		});

		$kernel = new LifecycleTestKernel($application);
		$kernel->setExceptionHandler(new RecordingExceptionHandler());

		$response = $kernel->handle($this->request('GET', '/boom'));

		$this->assertSame(500, $response->getStatusCode());
		$this->assertSame('handled: action exploded', $response->getBody());
		$this->assertSame(['reported: action exploded'], KernelLifecycleLog::$entries);
	}

	/**
	 * Build an application holding one route, with the framework's start-up replaced so the test
	 * does not need `.env`, providers or a filesystem.
	 *
	 * @param string             $uri        Route pattern.
	 * @param callable           $action     Route action.
	 * @param array<int, string> $middleware Route middleware names.
	 *
	 * @return Application
	 */
	private function applicationWithRoute(string $uri, callable $action, array $middleware = []): Application
	{
		$application = new Application();
		$router = new Router($application);
		$router->get($uri, $action)->middleware($middleware);
		$application->singleton(Router::class, $router);

		return $application;
	}

	private function request(string $method, string $uri): Request
	{
		return new Request(['REQUEST_METHOD' => $method, 'REQUEST_URI' => $uri]);
	}
}

final class KernelLifecycleLog
{
	/** @var array<int, string> */
	public static array $entries = [];
}

/**
 * A kernel with start-up disabled and its lists writable, so a test can set one thing at a time.
 */
class LifecycleTestKernel extends HttpKernel
{
	protected array $bootstrappers = [];

	private ?ExceptionHandlerInterface $testExceptionHandler = null;

	/** @param array<int, string> $middleware */
	public function setMiddleware(array $middleware): void
	{
		$this->middleware = $middleware;
	}

	/** @param array<string, array<int, string>> $groups */
	public function setMiddlewareGroups(array $groups): void
	{
		$this->middlewareGroups = $groups;
	}

	/** @param array<string, string> $routeMiddleware */
	public function setRouteMiddleware(array $routeMiddleware): void
	{
		$this->routeMiddleware = $routeMiddleware;
	}

	public function setExceptionHandler(ExceptionHandlerInterface $handler): void
	{
		$this->testExceptionHandler = $handler;
	}

	protected function resolveExceptionHandler(): ?ExceptionHandlerInterface
	{
		return $this->testExceptionHandler;
	}
}

final class RecordingBootstrapKernel extends HttpKernel
{
	protected array $bootstrappers = [FirstBootstrapper::class, SecondBootstrapper::class];
}

final class FirstBootstrapper implements \Clover\Component\Contract\BootstrapperInterface
{
	public function bootstrap(Application $application): void
	{
		KernelLifecycleLog::$entries[] = 'first';
	}
}

final class SecondBootstrapper implements \Clover\Component\Contract\BootstrapperInterface
{
	public function bootstrap(Application $application): void
	{
		KernelLifecycleLog::$entries[] = 'second';
	}
}

final class StampMiddleware implements MiddlewareInterface
{
	public function process(Request $request, RequestHandlerInterface $handler): Response
	{
		return $handler->handle($request)->setHeader('X-Stamped', 'yes');
	}
}

final class RefusingMiddleware implements MiddlewareInterface
{
	public function process(Request $request, RequestHandlerInterface $handler): Response
	{
		return Response::text('Forbidden', 403);
	}
}

final class TerminableStampMiddleware implements TerminableMiddlewareInterface
{
	public function process(Request $request, RequestHandlerInterface $handler): Response
	{
		return $handler->handle($request);
	}

	public function terminate(Request $request, Response $response): void
	{
		KernelLifecycleLog::$entries[] = 'terminated';
	}
}

final class RecordingExceptionHandler implements ExceptionHandlerInterface
{
	public ?Throwable $lastThrowable = null;

	public function report(Throwable $throwable): void
	{
		$this->lastThrowable = $throwable;
		KernelLifecycleLog::$entries[] = 'reported: ' . $throwable->getMessage();
	}

	public function render(Request $request, Throwable $throwable): Response
	{
		return Response::text('handled: ' . $throwable->getMessage(), 500);
	}
}

/** Lets a throwable escape the kernel so a test can assert on it directly. */
final class RethrowingExceptionHandler implements ExceptionHandlerInterface
{
	public function report(Throwable $throwable): void
	{
	}

	public function render(Request $request, Throwable $throwable): Response
	{
		throw $throwable;
	}
}
