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
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CronExpressionTest extends TestCase
{
	private function at(string $value, string $timezone = 'UTC'): DateTimeImmutable
	{
		return new DateTimeImmutable($value, new DateTimeZone($timezone));
	}

	public function testEveryMinuteWildcardMatchesAnyInstant(): void
	{
		$cron = new CronExpression('* * * * *');

		self::assertTrue($cron->isDue($this->at('2026-04-23 12:00:00')));
		self::assertTrue($cron->isDue($this->at('2026-04-23 23:59:00')));
	}

	public function testExactTimeOnlyMatchesExactInstant(): void
	{
		$cron = new CronExpression('30 9 * * *');

		self::assertTrue($cron->isDue($this->at('2026-04-23 09:30:00')));
		self::assertFalse($cron->isDue($this->at('2026-04-23 09:31:00')));
		self::assertFalse($cron->isDue($this->at('2026-04-23 10:30:00')));
	}

	public function testStepExpressionMatchesEveryFifteenMinutes(): void
	{
		$cron = new CronExpression('*/15 * * * *');

		self::assertTrue($cron->isDue($this->at('2026-04-23 12:00:00')));
		self::assertTrue($cron->isDue($this->at('2026-04-23 12:15:00')));
		self::assertTrue($cron->isDue($this->at('2026-04-23 12:30:00')));
		self::assertFalse($cron->isDue($this->at('2026-04-23 12:10:00')));
	}

	public function testRangeAndListCombination(): void
	{
		$cron = new CronExpression('0 9-11,14 * * 1-5');

		// Monday 09:00
		self::assertTrue($cron->isDue($this->at('2026-04-20 09:00:00')));
		// Monday 14:00
		self::assertTrue($cron->isDue($this->at('2026-04-20 14:00:00')));
		// Monday 12:00 (hour not in set)
		self::assertFalse($cron->isDue($this->at('2026-04-20 12:00:00')));
		// Saturday 09:00 (day not in 1-5)
		self::assertFalse($cron->isDue($this->at('2026-04-25 09:00:00')));
	}

	public function testDayOfMonthAndDayOfWeekUseOrSemantics(): void
	{
		// When both are restricted, POSIX cron requires OR semantics.
		$cron = new CronExpression('0 0 1 * 1');

		// First of the month (Wednesday) → matches by day of month.
		self::assertTrue($cron->isDue($this->at('2026-04-01 00:00:00')));
		// Monday that isn't the 1st → matches by day of week.
		self::assertTrue($cron->isDue($this->at('2026-04-20 00:00:00')));
		// Neither the 1st nor a Monday → no match.
		self::assertFalse($cron->isDue($this->at('2026-04-23 00:00:00')));
	}

	public function testSundayAcceptsBothZeroAndSeven(): void
	{
		$cron = new CronExpression('0 0 * * 7');

		// 2026-04-26 is a Sunday.
		self::assertTrue($cron->isDue($this->at('2026-04-26 00:00:00')));
		self::assertFalse($cron->isDue($this->at('2026-04-27 00:00:00')));
	}

	public function testDailyMacroMatchesMidnightOnly(): void
	{
		$cron = new CronExpression('@daily');

		self::assertTrue($cron->isDue($this->at('2026-04-23 00:00:00')));
		self::assertFalse($cron->isDue($this->at('2026-04-23 00:01:00')));
	}

	public function testHourlyMacroMatchesEveryTopOfHour(): void
	{
		$cron = new CronExpression('@hourly');

		self::assertTrue($cron->isDue($this->at('2026-04-23 12:00:00')));
		self::assertFalse($cron->isDue($this->at('2026-04-23 12:30:00')));
	}

	public function testTimezoneOverrideShiftsEvaluation(): void
	{
		$cron = new CronExpression('0 9 * * *');
		$seoul = new DateTimeZone('Asia/Seoul');

		// 00:00 UTC == 09:00 Asia/Seoul.
		$now = $this->at('2026-04-23 00:00:00');
		self::assertTrue($cron->isDue($now, $seoul));

		// 01:00 UTC == 10:00 Asia/Seoul → no longer matches.
		self::assertFalse($cron->isDue($this->at('2026-04-23 01:00:00'), $seoul));
	}

	public function testEmptyExpressionIsRejected(): void
	{
		$this->expectException(InvalidArgumentException::class);

		new CronExpression('   ');
	}

	public function testWrongNumberOfFieldsIsRejected(): void
	{
		$this->expectException(InvalidArgumentException::class);

		new CronExpression('0 0 * *');
	}

	public function testOutOfRangeValueIsRejected(): void
	{
		$this->expectException(InvalidArgumentException::class);

		new CronExpression('60 * * * *');
	}

	public function testZeroStepIsRejected(): void
	{
		$this->expectException(InvalidArgumentException::class);

		new CronExpression('*/0 * * * *');
	}
}
