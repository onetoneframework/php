<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Database;

use function count;

/**
 * The recorded query log: whether recording is on, the entries so far, and the
 * bound the log is trimmed to.
 *
 * Lifted out of ActiveRecord unchanged. The state is process-wide, as it always
 * was — enableQueryLog() on one model turns recording on for every model — so
 * it stays static here rather than becoming per-instance, which would have been
 * a behaviour change rather than a move.
 *
 * @package Clover\Classes\Database
 *
 * @phpstan-type LogEntry array{sql: string, bindings: array, time_ms: float}
 */
class QueryLog
{
	/**
	 * Whether query logging is enabled.
	 *
	 * @var bool
	 */
	private static bool $logging = false;

	/**
	 * Recorded query log entries.
	 *
	 * @var array<int, LogEntry>
	 */
	private static array $entries = [];

	/**
	 * Maximum number of entries kept before the oldest are dropped.
	 *
	 * @var int
	 */
	private static int $limit = 5000;

	/**
	 * Enable query logging. All subsequent queries will be recorded.
	 *
	 * @return void
	 */
	public static function enable(): void
	{
		self::$logging = true;
	}

	/**
	 * Disable query logging.
	 *
	 * @return void
	 */
	public static function disable(): void
	{
		self::$logging = false;
	}

	/**
	 * Whether recording is currently on.
	 *
	 * @return bool
	 */
	public static function isEnabled(): bool
	{
		return self::$logging;
	}

	/**
	 * Retrieve and flush the query log.
	 *
	 * @return array<int, LogEntry>
	 */
	public static function flush(): array
	{
		$log = self::$entries;
		self::$entries = [];

		return $log;
	}

	/**
	 * The bound the log is trimmed to.
	 *
	 * @return int
	 */
	public static function limit(): int
	{
		return self::$limit;
	}

	/**
	 * Set the log bound directly, without the floor setLimit-style callers apply.
	 *
	 * configurePool() assigned this property outright rather than going through a
	 * setter, so a configured value is taken as given; keeping that here is what
	 * makes the move a move.
	 *
	 * @param int $limit Maximum entries kept.
	 *
	 * @return void
	 */
	public static function configureLimit(int $limit): void
	{
		self::$limit = $limit;
	}

	/**
	 * Record a query to the internal log.
	 * Drops oldest entries when the log exceeds the limit to prevent
	 * unbounded memory growth in long-running workers/daemons.
	 *
	 * @param string $sql      SQL statement
	 * @param array  $bindings Bound parameters
	 * @param float  $timeMs   Execution time in milliseconds
	 *
	 * @return void
	 */
	public static function record(string $sql, array $bindings, float $timeMs): void
	{
		if (!self::$logging) {
			return;
		}

		self::$entries[] = [
			'sql' => $sql,
			'bindings' => $bindings,
			'time_ms' => round($timeMs, 3),
		];

		// Bounded: drop oldest entries when limit is exceeded
		if (count(self::$entries) > self::$limit) {
			self::$entries = array_slice(self::$entries, -self::$limit);
		}
	}
}
