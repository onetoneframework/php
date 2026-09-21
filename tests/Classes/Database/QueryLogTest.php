<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\Database;

use Clover\Classes\Database\QueryLog;
use PHPUnit\Framework\TestCase;

/**
 * Characterisation tests for the query log ActiveRecord used to keep itself.
 *
 * The state is process-wide and stayed that way through the move, so every test
 * here restores it afterwards rather than assuming a fresh class.
 */
final class QueryLogTest extends TestCase
{
	private int $originalLimit = 5000;

	protected function setUp(): void
	{
		parent::setUp();
		$this->originalLimit = QueryLog::limit();
		QueryLog::disable();
		QueryLog::flush();
	}

	protected function tearDown(): void
	{
		QueryLog::disable();
		QueryLog::flush();
		QueryLog::configureLimit($this->originalLimit);
		parent::tearDown();
	}

	public function testNothingIsRecordedWhileLoggingIsOff(): void
	{
		QueryLog::record('SELECT 1', [], 1.0);

		$this->assertSame([], QueryLog::flush());
		$this->assertFalse(QueryLog::isEnabled());
	}

	public function testEnablingRecordsSubsequentQueries(): void
	{
		QueryLog::enable();
		QueryLog::record('SELECT ?', [7], 1.2345);

		$this->assertTrue(QueryLog::isEnabled());
		$this->assertSame(
			[['sql' => 'SELECT ?', 'bindings' => [7], 'time_ms' => 1.235]],
			QueryLog::flush(),
			'The duration is rounded to three places, as it always was.'
		);
	}

	public function testDisablingStopsRecordingWithoutDroppingWhatWasRecorded(): void
	{
		QueryLog::enable();
		QueryLog::record('SELECT 1', [], 1.0);
		QueryLog::disable();
		QueryLog::record('SELECT 2', [], 1.0);

		$log = QueryLog::flush();

		$this->assertCount(1, $log);
		$this->assertSame('SELECT 1', $log[0]['sql']);
	}

	public function testFlushingEmptiesTheLog(): void
	{
		QueryLog::enable();
		QueryLog::record('SELECT 1', [], 1.0);

		$this->assertCount(1, QueryLog::flush());
		$this->assertSame([], QueryLog::flush(), 'The second read is empty.');
	}

	public function testTheOldestEntriesAreDroppedOnceTheLimitIsPassed(): void
	{
		QueryLog::configureLimit(3);
		QueryLog::enable();

		foreach (range(1, 5) as $number) {
			QueryLog::record('SELECT ' . $number, [], 1.0);
		}

		$log = QueryLog::flush();

		$this->assertCount(3, $log);
		$this->assertSame(['SELECT 3', 'SELECT 4', 'SELECT 5'], array_column($log, 'sql'));
	}

	public function testTheLimitIsTakenAsGivenWithoutAFloor(): void
	{
		QueryLog::configureLimit(1);

		$this->assertSame(1, QueryLog::limit(), 'configurePool() assigned this outright and still does.');
	}
}
