<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\Database;

use Clover\Classes\Database\QueryResultCache;
use PHPUnit\Framework\TestCase;

/**
 * Characterisation tests for the query result cache ActiveRecord used to keep
 * itself: bounded, LRU-ordered, with a per-entry TTL.
 *
 * The store is process-wide and stayed that way through the move, so every test
 * here restores it afterwards rather than assuming a fresh class.
 */
final class QueryResultCacheTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		QueryResultCache::flush();
		QueryResultCache::configureLimit(1000);
	}

	protected function tearDown(): void
	{
		QueryResultCache::flush();
		QueryResultCache::configureLimit(1000);
		parent::tearDown();
	}

	public function testAMissingKeyIsNull(): void
	{
		$this->assertNull(QueryResultCache::get('absent'));
	}

	public function testAStoredValueComesBack(): void
	{
		QueryResultCache::put('k', ['a' => 1], 60);

		$this->assertSame(['a' => 1], QueryResultCache::get('k'));
	}

	public function testAZeroTtlNeverExpires(): void
	{
		QueryResultCache::put('forever', 'value', 0);

		$this->assertSame('value', QueryResultCache::get('forever'));
	}

	public function testAnExpiredEntryIsDroppedOnRead(): void
	{
		QueryResultCache::put('stale', 'value', 60);

		// Reach in and age the entry rather than sleeping for a minute.
		$property = new \ReflectionProperty(QueryResultCache::class, 'entries');
		$property->setAccessible(true);
		$entries = $property->getValue();
		$entries['stale']['expires'] = time() - 1;
		$property->setValue(null, $entries);

		$this->assertNull(QueryResultCache::get('stale'));
		$this->assertSame([], $property->getValue(), 'Reading an expired entry removes it.');
	}

	public function testReadingAnEntryMovesItToTheEndForLru(): void
	{
		QueryResultCache::put('first', 1, 60);
		QueryResultCache::put('second', 2, 60);

		QueryResultCache::get('first');

		$property = new \ReflectionProperty(QueryResultCache::class, 'entries');
		$property->setAccessible(true);

		$this->assertSame(['second', 'first'], array_keys($property->getValue()));
	}

	public function testReachingTheLimitEvictsTheOldestFifth(): void
	{
		QueryResultCache::configureLimit(10);

		foreach (range(1, 10) as $number) {
			QueryResultCache::put('k' . $number, $number, 60);
		}

		// The eleventh put trips the eviction: 20% of 10 is 2, so k1 and k2 go.
		QueryResultCache::put('k11', 11, 60);

		$this->assertNull(QueryResultCache::get('k1'));
		$this->assertNull(QueryResultCache::get('k2'));
		$this->assertSame(3, QueryResultCache::get('k3'));
		$this->assertSame(11, QueryResultCache::get('k11'));
	}

	public function testFlushEmptiesTheStore(): void
	{
		QueryResultCache::put('k', 'v', 60);
		QueryResultCache::flush();

		$this->assertNull(QueryResultCache::get('k'));
	}

	public function testSetLimitAppliesAFloorOfTen(): void
	{
		QueryResultCache::setLimit(1);

		$property = new \ReflectionProperty(QueryResultCache::class, 'limit');
		$property->setAccessible(true);

		$this->assertSame(10, $property->getValue(), 'setQueryCacheLimit() has always clamped.');
	}

	public function testConfigureLimitTakesTheValueAsGiven(): void
	{
		QueryResultCache::configureLimit(1);

		$property = new \ReflectionProperty(QueryResultCache::class, 'limit');
		$property->setAccessible(true);

		$this->assertSame(1, $property->getValue(), 'configurePool() assigned this outright and still does.');
	}
}
