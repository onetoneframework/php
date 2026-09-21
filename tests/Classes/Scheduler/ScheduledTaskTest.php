<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\Scheduler;

use Clover\Classes\Scheduler\CronExpression;
use Clover\Classes\Scheduler\ScheduledTask;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ScheduledTaskTest extends TestCase
{
	public function testMetadataAndTimezoneAreExposed(): void
	{
		$cron = new CronExpression('0 9 * * *');
		$timezone = new DateTimeZone('Asia/Seoul');
		$task = new ScheduledTask('daily-report', $cron, static fn (): string => 'done', $timezone, 'Daily report');

		self::assertSame('daily-report', $task->getName());
		self::assertSame('Daily report', $task->getDescription());
		self::assertSame($cron, $task->getCron());
		self::assertSame($timezone, $task->getTimezone());
		self::assertSame('idle', $task->getLastStatus());
		self::assertNull($task->getLastRunAt());
		self::assertFalse($task->isRunning());
	}

	public function testIsDueEvaluatesCronInTaskTimezone(): void
	{
		$task = new ScheduledTask(
			'daily-report',
			new CronExpression('0 9 * * *'),
			static fn (): null => null,
			new DateTimeZone('Asia/Seoul')
		);

		self::assertTrue($task->isDue($this->at('2026-07-21 00:00:00')));
		self::assertFalse($task->isDue($this->at('2026-07-21 01:00:00')));
	}

	public function testRunReturnsCallbackValueAndRecordsSuccess(): void
	{
		$now = $this->at('2026-07-21 12:30:00');
		$task = new ScheduledTask('export', new CronExpression('* * * * *'), static fn (): string => 'exported');

		$result = $task->run($now);

		self::assertSame('exported', $result);
		self::assertSame('success', $task->getLastStatus());
		self::assertSame($now, $task->getLastRunAt());
		self::assertFalse($task->isRunning());
	}

	public function testRunRecordsFailureAndRestoresRunningState(): void
	{
		$now = $this->at('2026-07-21 12:30:00');
		$task = new ScheduledTask('export', new CronExpression('* * * * *'), static function (): void {
			throw new RuntimeException('export failed');
		});

		try {
			$task->run($now);
			self::fail('Task exceptions must be propagated.');
		} catch (RuntimeException $exception) {
			self::assertSame('export failed', $exception->getMessage());
		}

		self::assertSame('failed', $task->getLastStatus());
		self::assertSame($now, $task->getLastRunAt());
		self::assertFalse($task->isRunning());
	}

	public function testOverlapGuardSkipsNestedExecution(): void
	{
		$now = $this->at('2026-07-21 12:30:00');
		$callbackCalls = 0;
		$nestedResult = 'not-called';
		$task = null;
		$task = new ScheduledTask('non-overlapping', new CronExpression('* * * * *'), static function () use (&$task, &$callbackCalls, &$nestedResult, $now): string {
			$callbackCalls++;
			$nestedResult = $task->run($now);

			return 'outer-result';
		});

		$result = $task->run($now);

		self::assertSame('outer-result', $result);
		self::assertNull($nestedResult);
		self::assertSame(1, $callbackCalls);
		self::assertSame('success', $task->getLastStatus());
		self::assertFalse($task->isRunning());
	}

	private function at(string $value): DateTimeImmutable
	{
		return new DateTimeImmutable($value, new DateTimeZone('UTC'));
	}
}
