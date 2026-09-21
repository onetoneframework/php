<?php

declare(strict_types=1);

namespace Clover\Tests\Command;

use Clover\Classes\CLI\Input;
use Clover\Classes\DependencyInjection\Container;
use Clover\Classes\Scheduler\Scheduler;
use Clover\Framework\Context\ApplicationContext;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ScheduleListCommand;
use ScheduleRunCommand;

final class ScheduleCommandTest extends TestCase
{
	protected function tearDown(): void
	{
		ApplicationContext::setContainer(new Container());

		parent::tearDown();
	}

	public function testScheduleRunConfigureDeclaresTaskAndNowOptions(): void
	{
		$command = new ScheduleRunCommand();

		$command->configure();

		$this->assertCount(2, $command->options);
		$this->assertSame('task', $command->options[0]->name);
		$this->assertNull($command->options[0]->default);
		$this->assertSame('now', $command->options[1]->name);
		$this->assertNull($command->options[1]->default);
	}

	public function testScheduleCommandsExposeStableNamesAndDescriptions(): void
	{
		$list = new ScheduleListCommand();
		$run = new ScheduleRunCommand();

		$this->assertSame('schedule:list', $list->getName());
		$this->assertSame('List all registered scheduled tasks', $list->getDescription());
		$this->assertSame('schedule:run', $run->getName());
		$this->assertSame('Run all scheduled tasks that are due now', $run->getDescription());
	}

	public function testScheduleListReportsMissingSchedulerService(): void
	{
		ApplicationContext::setContainer(new Container());
		$command = new ScheduleListCommand();

		$this->expectOutputString(
			'Scheduler service is not registered. Check App/Configure/dependencies.php.' . PHP_EOL
		);

		$this->assertFalse($command->run(new Input(['php_console', 'schedule:list'])));
	}

	public function testScheduleListReportsEmptyScheduler(): void
	{
		$this->setScheduler(new Scheduler());
		$command = new ScheduleListCommand();

		$this->expectOutputString(
			'No scheduled tasks are registered. Register them in App/Configure/schedule.php.' . PHP_EOL
		);

		$this->assertTrue($command->run(new Input(['php_console', 'schedule:list'])));
	}

	public function testScheduleRunRejectsUnknownNamedTask(): void
	{
		$this->setScheduler(new Scheduler());
		$command = new ScheduleRunCommand();
		$command->configure();
		$input = new Input(
			['php_console', 'schedule:run', '--task=missing', '--now=2026-01-02T03:04:05+00:00'],
			[],
			$command->options
		);

		$this->expectOutputString('Unknown scheduled task: missing' . PHP_EOL);

		$this->assertFalse($command->run($input));
	}

	public function testScheduleRunExecutesNamedTaskAtProvidedInstant(): void
	{
		$executions = 0;
		$scheduler = new Scheduler();
		$scheduler->call('reports:daily', static function () use (&$executions): void {
			$executions++;
		}, '0 0 * * *');
		$this->setScheduler($scheduler);

		$command = new ScheduleRunCommand();
		$command->configure();
		$input = new Input(
			['php_console', 'schedule:run', '--task=reports:daily', '--now=2026-01-02T03:04:05+00:00'],
			[],
			$command->options
		);

		$this->expectOutputString('[SUCCESS] reports:daily' . PHP_EOL);

		$this->assertTrue($command->run($input));
		$this->assertSame(1, $executions);
		$this->assertSame(
			'2026-01-02T03:04:05+00:00',
			$scheduler->get('reports:daily')->getLastRunAt()?->format(DATE_ATOM)
		);
	}

	public function testScheduleRunReturnsFailureWhenNamedTaskThrows(): void
	{
		$scheduler = new Scheduler();
		$scheduler->call('reports:broken', static function (): void {
			throw new RuntimeException('report generation failed');
		}, '0 0 * * *');
		$this->setScheduler($scheduler);

		$command = new ScheduleRunCommand();
		$command->configure();
		$input = new Input(
			['php_console', 'schedule:run', '--task=reports:broken', '--now=2026-01-02T03:04:05+00:00'],
			[],
			$command->options
		);

		$this->expectOutputString('[FAILED] reports:broken - report generation failed' . PHP_EOL);

		$this->assertFalse($command->run($input));
	}

	public function testScheduleRunReportsWhenNothingIsDueAtProvidedInstant(): void
	{
		$scheduler = new Scheduler();
		$scheduler->call('new-year', static function (): void {
		}, '0 0 1 1 *');
		$this->setScheduler($scheduler);

		$command = new ScheduleRunCommand();
		$command->configure();
		$input = new Input(
			['php_console', 'schedule:run', '--now=2026-02-02T03:04:05+00:00'],
			[],
			$command->options
		);

		$this->expectOutputString(
			'No scheduled tasks are due at 2026-02-02T03:04:05+00:00.' . PHP_EOL
		);

		$this->assertTrue($command->run($input));
	}

	private function setScheduler(Scheduler $scheduler): void
	{
		$container = new Container();
		$container->set(Scheduler::class, $scheduler);
		ApplicationContext::setContainer($container);
	}
}
