<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Cache;

use Closure;
use Clover\Contract\CacheStoreInterface;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Stores expiring cache values in process memory.
 */
final class ArrayCache implements CacheStoreInterface
{
	private const MINIMUM_TIME_TO_LIVE_SECONDS = 1;

	private const MISSING_TIME_TO_LIVE_SECONDS = 0;

	/** @var array<string, array{value: mixed, expires_at: int}> */
	private array $items = [];

	private Closure $clock;

	public function __construct(?callable $clock = null)
	{
		$this->clock = $clock !== null
			? Closure::fromCallable($clock)
			: static fn (): int => time();
	}

	public function has(string $key): bool
	{
		$this->validateKey($key);
		$this->removeIfExpired($key);

		return isset($this->items[$key]);
	}

	public function get(string $key, mixed $default): mixed
	{
		if (!$this->has($key)) {
			return $default;
		}

		return $this->items[$key]['value'];
	}

	public function set(string $key, mixed $value, int $timeToLiveSeconds): void
	{
		$this->validateKey($key);
		$this->validateTimeToLive($timeToLiveSeconds);

		$this->items[$key] = [
			'value' => $value,
			'expires_at' => $this->now() + $timeToLiveSeconds,
		];
	}

	public function increment(string $key, int $amount, int $timeToLiveSeconds): int
	{
		$this->validateKey($key);
		$this->validateTimeToLive($timeToLiveSeconds);
		$this->removeIfExpired($key);

		if (!isset($this->items[$key])) {
			$this->set($key, $amount, $timeToLiveSeconds);

			return $amount;
		}

		$currentValue = $this->items[$key]['value'];
		if (!is_int($currentValue)) {
			throw new UnexpectedValueException('Only integer cache values can be incremented.');
		}

		$incrementedValue = $currentValue + $amount;
		$this->items[$key]['value'] = $incrementedValue;

		return $incrementedValue;
	}

	public function delete(string $key): void
	{
		$this->validateKey($key);
		unset($this->items[$key]);
	}

	public function clear(): void
	{
		$this->items = [];
	}

	public function getTimeToLive(string $key): int
	{
		if (!$this->has($key)) {
			return self::MISSING_TIME_TO_LIVE_SECONDS;
		}

		return $this->items[$key]['expires_at'] - $this->now();
	}

	private function validateKey(string $key): void
	{
		if ($key === '') {
			throw new InvalidArgumentException('The cache key must not be empty.');
		}
	}

	private function validateTimeToLive(int $timeToLiveSeconds): void
	{
		if ($timeToLiveSeconds < self::MINIMUM_TIME_TO_LIVE_SECONDS) {
			throw new InvalidArgumentException('The cache time to live must be at least one second.');
		}
	}

	private function removeIfExpired(string $key): void
	{
		if (isset($this->items[$key]) && $this->items[$key]['expires_at'] <= $this->now()) {
			unset($this->items[$key]);
		}
	}

	private function now(): int
	{
		$currentTime = ($this->clock)();
		if (!is_int($currentTime)) {
			throw new UnexpectedValueException('The cache clock must return an integer timestamp.');
		}

		return $currentTime;
	}
}
