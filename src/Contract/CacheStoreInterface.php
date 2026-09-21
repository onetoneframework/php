<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Contract;

/**
 * Defines the cache operations required by framework services.
 */
interface CacheStoreInterface
{
	public function has(string $key): bool;

	public function get(string $key, mixed $default): mixed;

	public function set(string $key, mixed $value, int $timeToLiveSeconds): void;

	public function increment(string $key, int $amount, int $timeToLiveSeconds): int;

	public function delete(string $key): void;

	public function clear(): void;

	public function getTimeToLive(string $key): int;
}
