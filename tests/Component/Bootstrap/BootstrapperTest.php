<?php

declare(strict_types=1);

namespace Clover\Tests\Component\Bootstrap;

use Clover\Component\Bootstrap\BootProviders;
use Clover\Component\Bootstrap\HandleExceptions;
use Clover\Component\Bootstrap\LoadEnvironmentVariables;
use Clover\Component\Bootstrap\RegisterProviders;
use Clover\Component\Contract\ExceptionHandlerInterface;
use Clover\Component\Exception\Handler;
use Clover\Component\Foundation\Application;
use Clover\Component\Http\Request;
use Clover\Component\Http\Response;
use Clover\Support\ServiceProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class BootstrapperTest extends TestCase
{
	protected function tearDown(): void
	{
		Application::setInstance(null);
		BootstrapProviderOne::$registerCalls = 0;
		BootstrapProviderOne::$bootCalls = 0;
		BootstrapProviderTwo::$registerCalls = 0;
		BootstrapProviderTwo::$bootCalls = 0;

		parent::tearDown();
	}

	public function testRegisterProvidersRegistersEveryDeclaredProvider(): void
	{
		$application = new Application();
		$application->setDeclaredProviders([
			BootstrapProviderOne::class,
			BootstrapProviderTwo::class,
		]);

		(new RegisterProviders())->bootstrap($application);

		$this->assertSame(1, BootstrapProviderOne::$registerCalls);
		$this->assertSame(1, BootstrapProviderTwo::$registerCalls);
		$this->assertSame(0, BootstrapProviderOne::$bootCalls);
		$this->assertSame(0, BootstrapProviderTwo::$bootCalls);
	}

	public function testBootProvidersBootsPreviouslyRegisteredProviders(): void
	{
		$application = new Application();
		$application->register(BootstrapProviderOne::class);
		$application->register(BootstrapProviderTwo::class);

		(new BootProviders())->bootstrap($application);

		$this->assertTrue($application->isBooted());
		$this->assertSame(1, BootstrapProviderOne::$bootCalls);
		$this->assertSame(1, BootstrapProviderTwo::$bootCalls);
	}

	public function testHandleExceptionsRegistersDefaultHandlerWhenMissing(): void
	{
		$application = new Application();

		(new HandleExceptions())->bootstrap($application);

		$this->assertInstanceOf(Handler::class, $application->make(ExceptionHandlerInterface::class));
	}

	public function testHandleExceptionsPreservesApplicationHandler(): void
	{
		$application = new Application();
		$handler = new BootstrapExceptionHandler();
		$application->singleton(ExceptionHandlerInterface::class, $handler);

		(new HandleExceptions())->bootstrap($application);

		$this->assertSame($handler, $application->make(ExceptionHandlerInterface::class));
	}

	public function testLoadEnvironmentVariablesAllowsApplicationWithoutBasePath(): void
	{
		$application = new Application();

		(new LoadEnvironmentVariables())->bootstrap($application);

		$this->assertSame('', $application->getEnvironmentPath());
	}

	public function testLoadEnvironmentVariablesRejectsConfiguredDirectoryWithoutEnvironmentFile(): void
	{
		$application = new Application();
		$missingPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'clover_missing_env_' . bin2hex(random_bytes(8));
		$application->useEnvironmentPath($missingPath);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage($missingPath);

		(new LoadEnvironmentVariables())->bootstrap($application);
	}
}

final class BootstrapProviderOne extends ServiceProvider
{
	public static int $registerCalls = 0;
	public static int $bootCalls = 0;

	public function register(): void
	{
		self::$registerCalls++;
	}

	public function boot(): void
	{
		self::$bootCalls++;
	}
}

final class BootstrapProviderTwo extends ServiceProvider
{
	public static int $registerCalls = 0;
	public static int $bootCalls = 0;

	public function register(): void
	{
		self::$registerCalls++;
	}

	public function boot(): void
	{
		self::$bootCalls++;
	}
}

final class BootstrapExceptionHandler implements ExceptionHandlerInterface
{
	public function report(Throwable $throwable): void
	{
	}

	public function render(Request $request, Throwable $throwable): Response
	{
		return Response::text('handled', 500);
	}
}
