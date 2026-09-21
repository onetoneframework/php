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
use Memcached;
use Memcache;

/**
 * Memcache/Memcached adapter with timestamped payloads and simple TTL handling.
 */
class MemcachedCache extends BaseClass
{
	/**
	 * Active backend identifier: "memcached" or "memcache".
	 *
	 * @var string|null
	 */
	protected $type;

	/**
	 * Underlying Memcached or Memcache client instance.
	 *
	 * @var Memcached|Memcache
	 */
	protected Memcached | Memcache $cache;

	/**
	 * Instantiates whichever Memcache extension is available.
	 */
	public function __construct()
	{
		if ($this->isMemcachedClassExists()) {
			$this->cache = new Memcached;
			$this->type = "memcached";
		} else if ($this->isMemcacheClassExists()) {
			$this->cache = new Memcache;
			$this->type = "memcache";
		}
	}

	/**
	 * Returns whether the legacy Memcache extension class is available.
	 *
	 * @return bool True when {@see Memcache} exists.
	 */
	public function isMemcacheClassExists()
	{
		return class_exists('Memcache');
	}

	/**
	 * Returns whether the Memcached extension class is available.
	 *
	 * @return bool True when {@see Memcached} exists.
	 */
	public function isMemcachedClassExists()
	{
		return class_exists('Memcached');
	}

	/**
	 * Registers a Memcache/Memcached server endpoint.
	 *
	 * @param string $host Server hostname or IP.
	 * @param int|string $port TCP port.
	 *
	 * @return void
	 */
	public function connect($host, $port)
	{
		$this->cache->addServer($host, $port);
	}

	/**
	 * Flushes all items from the cache cluster.
	 *
	 * @return mixed Result from the underlying flush operation.
	 */
	public function truncate()
	{
		return $this->cache->flush();
	}

	/**
	 * Stores a timestamped payload with a TTL, using the active backend semantics.
	 *
	 * @param string $key Cache key.
	 * @param int $validTime Time to live in seconds.
	 * @param mixed $buffer Serializable payload.
	 *
	 * @return mixed Store operation result.
	 */
	public function set($key, $validTime, $buffer)
	{
		if ($this->type == "memcached") {
			return $this->cache->set($key, array(time(), $buffer), $validTime);
		} else if ($this->type == "memcache") {
			return $this->cache->set($key, array(time(), $buffer), \MEMCACHE_COMPRESSED, $validTime);
		}
	}

	/**
	 * Decrements a numeric cache item.
	 *
	 * @param string $key Cache key.
	 * @param int $amount Amount to subtract.
	 *
	 * @return mixed Result from the underlying decrement call.
	 */
	public function decrement(string $key, int $amount)
	{
		return $this->cache->decrement($key, $amount);
	}

	/**
	 * Increments a numeric cache item.
	 *
	 * @param string $key Cache key.
	 * @param int $amount Amount to add.
	 *
	 * @return mixed Result from the underlying increment call.
	 */
	public function increment(string $key, int $amount)
	{
		return $this->cache->increment($key, $amount);
	}

	/**
	 * Returns whether a key resolves to a stored value.
	 *
	 * @param string $key Cache key.
	 *
	 * @return bool True when {@see get()} would return data.
	 */
	public function isExists(string $key)
	{
		return $this->get($key) !== false;
	}

	/**
	 * Deletes a key from the cache.
	 *
	 * @param string $key Cache key.
	 *
	 * @return mixed Result from the underlying delete call.
	 */
	public function delete(string $key)
	{
		return $this->cache->delete($key);
	}

	/**
	 * Fetches the stored payload and drops the key when older than the limit.
	 *
	 * @param string $key Cache key.
	 * @param int $limit Minimum acceptable timestamp; deletes stale entries.
	 *
	 * @return mixed Cached payload segment or false when missing.
	 */
	public function get(string $key, int $limit = 0)
	{
		$cache = $this->cache->get($key);

		if ($limit > 0 && $limit > $cache[0]) {
			$this->delete($key);
		}

		return $cache[1];
	}
}
