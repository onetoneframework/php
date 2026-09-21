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
 * The in-memory query result cache: bounded, LRU-ordered, with a per-entry TTL.
 *
 * Lifted out of ActiveRecord unchanged, bodies included. The store is
 * process-wide, as it always was — flushCache() on one model empties it for
 * every model — so it stays static here rather than becoming per-instance,
 * which would have been a behaviour change rather than a move.
 *
 * @package Clover\Classes\Database
 *
 * @phpstan-type CacheEntry array{data: mixed, expires: int}
 */
class QueryResultCache
{
	/**
	 * In-memory query result cache indexed by SQL hash.
	 * Bounded to the configured limit with LRU eviction.
	 *
	 * @var array<string, CacheEntry>
	 */
	private static array $entries = [];

	/**
	 * Maximum number of entries in the query cache. When exceeded,
	 * the oldest 20% of entries are evicted to prevent unbounded memory growth.
	 *
	 * @var int
	 */
	private static int $limit = 1000;

	/**
	 * Retrieve a cached result by cache key, or null if expired/missing.
	 * Moves accessed entries to the end to implement LRU ordering.
	 *
	 * @param string $key  Cache key (typically an SQL hash)
	 *
	 * @return mixed|null  Cached value or null
	 */
	public static function get(string $key): mixed
	{
		if (!isset(self::$entries[$key])) {
			return null;
		}

		$entry = self::$entries[$key];

		if ($entry['expires'] > 0 && $entry['expires'] < time()) {
			unset(self::$entries[$key]);

			return null;
		}

		// LRU: move to end
		unset(self::$entries[$key]);
		self::$entries[$key] = $entry;

		return $entry['data'];
	}

	/**
	 * Store a value in the query cache.
	 * Evicts the oldest 20% of entries when the cache limit is reached.
	 *
	 * @param string $key   Cache key
	 * @param mixed  $data  Data to cache
	 * @param int    $ttl   Time-to-live in seconds (0 = forever)
	 *
	 * @return void
	 */
	public static function put(string $key, mixed $data, int $ttl): void
	{
		// Bounded eviction: remove oldest 20% when limit is reached
		if (count(self::$entries) >= self::$limit) {
			$evictCount = (int) (self::$limit * 0.2);
			self::$entries = array_slice(self::$entries, $evictCount, null, true);
		}

		self::$entries[$key] = [
			'data' => $data,
			'expires' => $ttl > 0 ? time() + $ttl : 0,
		];
	}

	/**
	 * Set the cache bound directly, without the floor setLimit() applies.
	 *
	 * configurePool() assigned this property outright rather than going through
	 * setQueryCacheLimit(), so a configured value is taken as given - a limit
	 * below ten included. Keeping both paths is what makes the move a move.
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
	 * Flush all entries from the in-memory query cache.
	 *
	 * @return void
	 */
	public static function flush(): void
	{
		self::$entries = [];
	}

	/**
	 * Set the maximum number of query cache entries.
	 *
	 * @param int $limit  Max cache entries (default 1000)
	 *
	 * @return void
	 */
	public static function setLimit(int $limit): void
	{
		self::$limit = max(10, $limit);
	}
}
