<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\App\Http;

use App\Http\Kernel;
use App\Middleware\TrimStringsMiddleware;
use Clover\Component\Foundation\Application;
use Clover\Component\Http\Request;
use Clover\Component\Routing\Router;
use Clover\Contract\KernelInterface;
use Clover\Framework\Component\DotenvLoader;
use PHPUnit\Framework\TestCase;

/**
 * The bundled application under `APP_ENTRY_POINT=application`, end to end.
 *
 * This is the case that used to be impossible: `RoutingServiceProvider::boot()` was an empty stub,
 * so this stack registered no routes and answered 404 to everything. Every assertion below would
 * have failed before the kernel lifecycle existed.
 */
final class ApplicationKernelTest extends TestCase
{
	/** @var array<string, mixed> `$_ENV` as it was before this test booted the application. */
	private array $environmentSnapshot = [];

	/** @var array<string, mixed> `$_SERVER` as it was before this test booted the application. */
	private array $serverSnapshot = [];

	/**
	 * Booting the real kernel runs `LoadEnvironmentVariables`, which reads the repository's own
	 * `.env` into `$_ENV` - and that file sets `USE_PROXY=true`, which makes
	 * `BaseClass::setBaseProxy()` start wrapping objects for every test that runs afterwards in
	 * this process. A test that loads the environment has to put it back.
	 */
	protected function setUp(): void
	{
		$this->environmentSnapshot = $_ENV;
		$this->serverSnapshot = $_SERVER;
		DotenvLoader::reset();
	}

	protected function tearDown(): void
	{
		Application::setInstance(null);

		$_ENV = $this->environmentSnapshot;
		$_SERVER = $this->serverSnapshot;
		DotenvLoader::reset();
	}

	public function testApplicationBindsItsOwnKernel(): void
	{
		$kernel = $this->bootKernel();

		$this->assertInstanceOf(Kernel::class, $kernel);
		$this->assertContains(TrimStringsMiddleware::class, $kernel->getMiddleware());
		$this->assertArrayHasKey('trim', $kernel->getRouteMiddleware());
	}

	public function testBundledRoutesAreRegistered(): void
	{
		$application = $this->bootApplication();
		$router = $application->make(Router::class);

		$this->assertNotNull($router->getRouteByName('home'));
		$this->assertNotNull($router->getRouteByName('health'));
		$this->assertSame('/health', $router->getRouteByName('health')?->getUri());
	}

	public function testHealthRouteAnswersAsJson(): void
	{
		$response = $this->bootKernel()->handle($this->request('GET', '/health'));

		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame('application/json; charset=utf-8', $response->getHeader('content-type'));
		$this->assertSame('{"status":"ok","entry_point":"application"}', $response->getBody());
	}

	/**
	 * The global middleware this application declares actually runs, and the route sees its work.
	 */
	public function testGlobalMiddlewareTrimsQueryValuesBeforeTheRouteSeesThem(): void
	{
		$response = $this->bootKernel()->handle($this->request('GET', '/echo/hi?name=++alice++'));

		$this->assertSame(200, $response->getStatusCode());
		$this->assertStringContainsString('"name":"alice"', (string) $response->getBody());
	}

	public function testUnknownPathIsNotFoundAndWrongMethodIsNotAllowed(): void
	{
		$kernel = $this->bootKernel();

		$missing = $kernel->handle($this->request('GET', '/no-such-route'));
		$wrongMethod = $kernel->handle($this->request('POST', '/health'));

		$this->assertSame(404, $missing->getStatusCode());
		$this->assertSame(405, $wrongMethod->getStatusCode());
		$this->assertSame('GET', $wrongMethod->getHeader('allow'));
	}

	private function bootApplication(): Application
	{
		$application = Application::configure($this->basePath());
		$application->make(KernelInterface::class)->boot();

		return $application;
	}

	private function bootKernel(): Kernel
	{
		$application = Application::configure($this->basePath());
		$kernel = $application->make(KernelInterface::class);

		$this->assertInstanceOf(Kernel::class, $kernel);
		$kernel->boot();

		return $kernel;
	}

	/** The `root/` directory, which is what `root/index.php` passes as the base path. */
	private function basePath(): string
	{
		return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'root';
	}

	private function request(string $method, string $uri): Request
	{
		return new Request(['REQUEST_METHOD' => $method, 'REQUEST_URI' => $uri]);
	}
}
