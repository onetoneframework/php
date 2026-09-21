<?php

declare(strict_types=1);

namespace Clover\Tests\Service;

use Clover\Classes\Scheduler\Scheduler;
use Clover\Component\Foundation\Application;
use Clover\Service\SchedulerServiceProvider;
use PHPUnit\Framework\TestCase;

final class SchedulerServiceProviderTest extends TestCase
{
	private array $originalEnvironment = [];

	protected function setUp(): void
	{
		parent::setUp();
		$this->originalEnvironment = $_ENV;
		unset($_ENV['APP_TIMEZONE']);
	}

	protected function tearDown(): void
	{
		$_ENV = $this->originalEnvironment;
		Application::setInstance(null);

		parent::tearDown();
	}

	public function testRegisterCreatesSharedSchedulerAndAlias(): void
	{
		$application = new Application();
		$provider = new SchedulerServiceProvider($application);

		$provider->register();

		$scheduler = $application->make(Scheduler::class);
		$this->assertInstanceOf(Scheduler::class, $scheduler);
		$this->assertSame($scheduler, $application->make(Scheduler::class));
		$this->assertSame($scheduler, $application->make('scheduler'));
	}

	public function testConfiguredTimezoneIsUsedByScheduler(): void
	{
		$_ENV['APP_TIMEZONE'] = 'Asia/Seoul';
		$application = new Application();
		$provider = new SchedulerServiceProvider($application);

		$provider->register();

		$scheduler = $application->make(Scheduler::class);
		$this->assertSame('Asia/Seoul', $scheduler->getDefaultTimezone()->getName());
	}

	public function testBlankTimezoneFallsBackToUtc(): void
	{
		$_ENV['APP_TIMEZONE'] = '   ';
		$application = new Application();
		$provider = new SchedulerServiceProvider($application);

		$provider->register();

		$scheduler = $application->make(Scheduler::class);
		$this->assertSame('UTC', $scheduler->getDefaultTimezone()->getName());
	}

	public function testInvalidTimezoneFallsBackToUtc(): void
	{
		$_ENV['APP_TIMEZONE'] = 'Mars/Olympus';
		$application = new Application();
		$provider = new SchedulerServiceProvider($application);

		$provider->register();

		$scheduler = $application->make(Scheduler::class);
		$this->assertSame('UTC', $scheduler->getDefaultTimezone()->getName());
	}
}
