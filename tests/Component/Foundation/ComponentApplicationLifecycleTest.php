<?php

declare(strict_types=1);

namespace Clover\Tests\Component\Foundation;

use Clover\Component\Foundation\Application;
use Clover\Support\ServiceProvider;
use PHPUnit\Framework\TestCase;

final class ComponentApplicationLifecycleTest extends TestCase
{
	protected function tearDown(): void
	{
		Application::setInstance(null);
		BootTrackingProvider::$registerCalls = 0;
		BootTrackingProvider::$bootCalls = 0;
	}

	public function testBootOnlyRunsProviderBootOnce(): void
	{
		$application = new Application(__DIR__);
		$application->register(BootTrackingProvider::class);

		$application->boot();
		$application->boot();

		$this->assertTrue($application->isBooted());
		$this->assertSame(__DIR__, $application->getBasePath());
		$this->assertSame(1, BootTrackingProvider::$registerCalls);
		$this->assertSame(1, BootTrackingProvider::$bootCalls);
	}

	public function testRegisterBootsProviderImmediatelyAfterApplicationHasBooted(): void
	{
		$application = new Application();
		$application->boot();

		$application->register(BootTrackingProvider::class);

		$this->assertSame(1, BootTrackingProvider::$registerCalls);
		$this->assertSame(1, BootTrackingProvider::$bootCalls);
	}

	public function testRegisteringSameProviderTwiceDoesNotRegisterItTwice(): void
	{
		$application = new Application();

		$first = $application->register(BootTrackingProvider::class);
		$second = $application->register(BootTrackingProvider::class);

		$this->assertSame($first, $second);
		$this->assertSame(1, BootTrackingProvider::$registerCalls);
	}

	public function testForcedProviderRegistrationReplacesLoadedProvider(): void
	{
		$application = new Application();
		$first = $application->register(BootTrackingProvider::class);

		$second = $application->register(BootTrackingProvider::class, true);

		$this->assertNotSame($first, $second);
		$this->assertSame(2, BootTrackingProvider::$registerCalls);
	}

	public function testAddDeclaredProviderDoesNotDuplicateExistingProvider(): void
	{
		$application = new Application();

		$application->addDeclaredProvider(BootTrackingProvider::class);
		$application->addDeclaredProvider(BootTrackingProvider::class);

		$this->assertSame([BootTrackingProvider::class], $application->getDeclaredProviders());
	}

	public function testSetDeclaredProvidersReindexesInputList(): void
	{
		$application = new Application();

		$application->setDeclaredProviders([
			5 => BootTrackingProvider::class,
			9 => SecondaryBootTrackingProvider::class,
		]);

		$this->assertSame(
			[BootTrackingProvider::class, SecondaryBootTrackingProvider::class],
			$application->getDeclaredProviders()
		);
	}

	public function testEnvironmentPathOverrideTrimsTrailingSeparators(): void
	{
		$application = new Application('C:\\application\\');

		$application->useEnvironmentPath('C:\\configuration\\');

		$this->assertSame('C:\\application', $application->getBasePath());
		$this->assertSame('C:\\configuration', $application->getEnvironmentPath());
	}

	public function testStaticInstanceTracksMostRecentlyConstructedApplication(): void
	{
		$first = new Application('first');
		$this->assertSame($first, Application::getInstance());

		$second = new Application('second');

		$this->assertSame($second, Application::getInstance());
	}
}

final class BootTrackingProvider extends ServiceProvider
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

final class SecondaryBootTrackingProvider extends ServiceProvider
{
}
