<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\Cache;

use Clover\Classes\Cache\ArrayCache;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

final class ArrayCacheTest extends TestCase
{
	private const CACHE_KEY = 'example';

	private const INITIAL_TIME = 1_000;

	private const TIME_TO_LIVE_SECONDS = 60;

	private int $currentTime;

	private ArrayCache $cache;

	protected function setUp(): void
	{
		$this->currentTime = self::INITIAL_TIME;
		$this->cache = new ArrayCache(function (): int {
			return $this->currentTime;
		});
	}

	public function testStoresAndRetrievesAValueBeforeExpiration(): void
	{
		$this->cache->set(self::CACHE_KEY, 'cached-value', self::TIME_TO_LIVE_SECONDS);

		self::assertTrue($this->cache->has(self::CACHE_KEY));
		self::assertSame('cached-value', $this->cache->get(self::CACHE_KEY, 'fallback'));
		self::assertSame(self::TIME_TO_LIVE_SECONDS, $this->cache->getTimeToLive(self::CACHE_KEY));
	}

	public function testReturnsTheProvidedDefaultForAMissingValue(): void
	{
		self::assertSame('fallback', $this->cache->get(self::CACHE_KEY, 'fallback'));
		self::assertSame(0, $this->cache->getTimeToLive(self::CACHE_KEY));
	}

	public function testRemovesAValueWhenItsTimeToLiveExpires(): void
	{
		$this->cache->set(self::CACHE_KEY, 'cached-value', self::TIME_TO_LIVE_SECONDS);
		$this->currentTime += self::TIME_TO_LIVE_SECONDS;

		self::assertFalse($this->cache->has(self::CACHE_KEY));
		self::assertSame('fallback', $this->cache->get(self::CACHE_KEY, 'fallback'));
	}

	public function testIncrementCreatesAnIntegerCounter(): void
	{
		$currentValue = $this->cache->increment(self::CACHE_KEY, 1, self::TIME_TO_LIVE_SECONDS);

		self::assertSame(1, $currentValue);
		self::assertSame(1, $this->cache->get(self::CACHE_KEY, 0));
	}

	public function testIncrementPreservesTheOriginalExpiration(): void
	{
		$this->cache->increment(self::CACHE_KEY, 1, self::TIME_TO_LIVE_SECONDS);
		$this->currentTime += 15;

		$currentValue = $this->cache->increment(self::CACHE_KEY, 1, self::TIME_TO_LIVE_SECONDS);

		self::assertSame(2, $currentValue);
		self::assertSame(45, $this->cache->getTimeToLive(self::CACHE_KEY));
	}

	public function testIncrementRejectsANonIntegerValue(): void
	{
		$this->cache->set(self::CACHE_KEY, 'not-a-counter', self::TIME_TO_LIVE_SECONDS);

		$this->expectException(UnexpectedValueException::class);
		$this->expectExceptionMessage('Only integer cache values can be incremented.');

		$this->cache->increment(self::CACHE_KEY, 1, self::TIME_TO_LIVE_SECONDS);
	}

	public function testSetRejectsANonPositiveTimeToLive(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('The cache time to live must be at least one second.');

		$this->cache->set(self::CACHE_KEY, 'cached-value', 0);
	}

	public function testDeleteRemovesOnlyTheSelectedValue(): void
	{
		$this->cache->set(self::CACHE_KEY, 'cached-value', self::TIME_TO_LIVE_SECONDS);
		$this->cache->set('retained', 'retained-value', self::TIME_TO_LIVE_SECONDS);

		$this->cache->delete(self::CACHE_KEY);

		self::assertFalse($this->cache->has(self::CACHE_KEY));
		self::assertTrue($this->cache->has('retained'));
	}

	public function testClearRemovesAllValues(): void
	{
		$this->cache->set(self::CACHE_KEY, 'cached-value', self::TIME_TO_LIVE_SECONDS);
		$this->cache->set('another', 'another-value', self::TIME_TO_LIVE_SECONDS);

		$this->cache->clear();

		self::assertFalse($this->cache->has(self::CACHE_KEY));
		self::assertFalse($this->cache->has('another'));
	}

	public function testStoresFalsyScalarValues(): void
	{
		$this->cache->set('zero', 0, self::TIME_TO_LIVE_SECONDS);
		$this->cache->set('false', false, self::TIME_TO_LIVE_SECONDS);
		$this->cache->set('empty-string', '', self::TIME_TO_LIVE_SECONDS);

		self::assertSame(0, $this->cache->get('zero', 99));
		self::assertFalse($this->cache->get('false', true));
		self::assertSame('', $this->cache->get('empty-string', 'fallback'));
	}

	public function testIncrementRestartsExpiredCounterWithFreshTimeToLive(): void
	{
		$this->cache->increment(self::CACHE_KEY, 4, 10);
		$this->currentTime += 10;

		self::assertSame(3, $this->cache->increment(self::CACHE_KEY, 3, 25));
		self::assertSame(25, $this->cache->getTimeToLive(self::CACHE_KEY));
	}

	public function testEmptyKeyIsRejected(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('The cache key must not be empty.');

		$this->cache->has('');
	}

	public function testClockMustReturnIntegerTimestamp(): void
	{
		$cache = new ArrayCache(static fn (): string => 'not-a-timestamp');

		$this->expectException(UnexpectedValueException::class);
		$this->expectExceptionMessage('The cache clock must return an integer timestamp.');

		$cache->set('key', 'value', 10);
	}
}
