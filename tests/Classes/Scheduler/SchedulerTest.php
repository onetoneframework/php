<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\Scheduler;

use Clover\Classes\Scheduler\Scheduler;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SchedulerTest extends TestCase
{
	private function at(string $value): DateTimeImmutable
	{
		return new DateTimeImmutable($value, new DateTimeZone('UTC'));
	}

	public function testCallRegistersTaskAndAppearsInAll(): void
	{
		$scheduler = new Scheduler();
		$scheduler->call('demo', static fn () => null, '* * * * *', 'demo task');

		self::assertTrue($scheduler->has('demo'));

		$tasks = $scheduler->all();
		self::assertCount(1, $tasks);
		self::assertSame('demo', $tasks[0]->getName());
		self::assertSame('demo task', $tasks[0]->getDescription());
	}

	public function testDuplicateTaskNameIsRejected(): void
	{
		$scheduler = new Scheduler();
		$scheduler->call('demo', static fn () => null, '* * * * *');

		$this->expectException(InvalidArgumentException::class);
		$scheduler->call('demo', static fn () => null, '@daily');
	}

	public function testEmptyTaskNameIsRejected(): void
	{
		$scheduler = new Scheduler();

		$this->expectException(InvalidArgumentException::class);
		$scheduler->call('', static fn () => null, '* * * * *');
	}

	public function testRunExecutesOnlyDueTasks(): void
	{
		$scheduler = new Scheduler();
		$dailyRuns = 0;
		$hourlyRuns = 0;

		$scheduler->call('daily', static function () use (&$dailyRuns): void {
			$dailyRuns++;
		}, '@daily');

		$scheduler->call('hourly', static function () use (&$hourlyRuns): void {
			$hourlyRuns++;
		}, '@hourly');

		// Midnight: both are due.
		$results = $scheduler->run($this->at('2026-04-23 00:00:00'));
		self::assertCount(2, $results);
		self::assertSame(1, $dailyRuns);
		self::assertSame(1, $hourlyRuns);

		// 12:00: only hourly is due.
		$results = $scheduler->run($this->at('2026-04-23 12:00:00'));
		self::assertCount(1, $results);
		self::assertSame('hourly', $results[0]['name']);
		self::assertSame('success', $results[0]['status']);
		self::assertSame(2, $hourlyRuns);
	}

	public function testFailingTaskDoesNotBlockSiblings(): void
	{
		$scheduler = new Scheduler();
		$okRuns = 0;

		$scheduler->call('broken', static function (): void {
			throw new RuntimeException('boom');
		}, '* * * * *');

		$scheduler->call('ok', static function () use (&$okRuns): void {
			$okRuns++;
		}, '* * * * *');

		$results = $scheduler->run($this->at('2026-04-23 12:00:00'));

		self::assertCount(2, $results);
		self::assertSame(1, $okRuns);

		$byName = [];
		foreach ($results as $result) {
			$byName[$result['name']] = $result;
		}

		self::assertSame('failed', $byName['broken']['status']);
		self::assertSame('boom', $byName['broken']['error']);
		self::assertSame('success', $byName['ok']['status']);
	}

	public function testRunTaskIgnoresCron(): void
	{
		$scheduler = new Scheduler();
		$count = 0;

		$scheduler->call('manual', static function () use (&$count): void {
			$count++;
		}, '0 0 1 1 *');

		$now = $this->at('2026-04-23 15:30:00');
		$result = $scheduler->runTask('manual', $now);

		self::assertSame('success', $result['status']);
		self::assertSame(1, $count);
		self::assertEquals($now, $scheduler->get('manual')->getLastRunAt());
	}

	public function testRunTaskRejectsUnknownName(): void
	{
		$scheduler = new Scheduler();

		$this->expectException(InvalidArgumentException::class);
		$scheduler->runTask('nope');
	}

	public function testDueTasksDoesNotMutateTaskState(): void
	{
		$scheduler = new Scheduler();
		$scheduler->call('demo', static fn () => null, '* * * * *');

		$due = $scheduler->dueTasks($this->at('2026-04-23 12:00:00'));

		self::assertCount(1, $due);
		self::assertNull($due[0]->getLastRunAt());
		self::assertSame('idle', $due[0]->getLastStatus());
	}
}
