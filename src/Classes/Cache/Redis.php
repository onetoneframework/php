<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Cache;

use Clover\Classes\BaseClass;
use Redis as Cache;

/**
 * Minimal Redis helper for connectivity checks, list push, and key/value access.
 */
class RedisCache extends BaseClass
{

	/**
	 * Underlying PHP {@see \Redis} client instance.
	 *
	 * @var Cache
	 */
	private $cache;

	/**
	 * Creates a new Redis client wrapper.
	 */
	public function __construct()
	{
		$this->cache = new Cache();
	}

	/**
	 * Returns whether the PHP Redis extension is available.
	 *
	 * @return bool True when the {@see \Redis} class exists.
	 */
	public function isExists(): bool
	{
		return class_exists('Redis');
	}

	/**
	 * Verifies that the connected Redis server reports a version at least 1.2.
	 *
	 * @param string $host Unused; connection is expected to be configured already.
	 * @param string $port Unused port placeholder for API compatibility.
	 *
	 * @return bool True when the server version is sufficient.
	 */
	public function compareVersion(string $host, string $port = '6379'): bool
	{
		preg_match('/redis_version:(.*?)\n/', $this->cache->info(), $info);

		if (version_compare(trim($info[1]), '1.2') < 0) {
			return false;
		}

		return true;
	}

	/**
	 * Connects to a Redis server.
	 *
	 * @param string $host Server hostname or IP.
	 * @param string $port TCP port (default 6379).
	 *
	 * @return void
	 */
	public function connect(string $host, string $port = '6379')
	{
		$this->cache->connect($host, $port);
	}

	/**
	 * Pushes a value onto the head of a Redis list.
	 *
	 * @param string $key List key.
	 * @param string $value Value to push.
	 *
	 * @return void
	 */
	public function pushList(string $key, string $value)
	{
		$this->cache->lpush($key, $value);
	}

	/**
	 * Deletes keys and refreshes expiry on the primary key.
	 *
	 * @param string $key Primary key.
	 * @param mixed $value Additional keys or members passed to {@see \Redis::del()}.
	 *
	 * @return void
	 */
	public function delete(string $key, mixed $value)
	{
		$this->cache->del($key, $value);

		$this->cache->expireat($key, time() + 3600);
	}

	/**
	 * Sets a string value for a key.
	 *
	 * @param string $key Cache key.
	 * @param mixed $value Serializable payload.
	 *
	 * @return void
	 */
	public function set(string $key, mixed $value)
	{
		$this->cache->set($key, $value);
	}

	/**
	 * Reads a string value for a key when present.
	 *
	 * @param string $key Cache key.
	 *
	 * @return mixed Cached string or empty string when missing.
	 */
	public function get(string $key)
	{
		$data = '';

		if ($this->cache->exists($key)) {
			$data = $this->cache->get($key);
		}

		return $data;
	}
}
