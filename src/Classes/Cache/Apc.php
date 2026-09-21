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
use function apc_clear_cache;
use function apc_exists;

/**
 * Thin wrapper around the legacy APC/APCu user cache API.
 */
class ApcCache extends BaseClass
{

	/**
	 * Returns whether one or more APC user cache keys exist.
	 *
	 * @param mixed $keys A single key string or a list of keys.
	 *
	 * @return mixed Result from {@see apc_exists()}.
	 */
	public function isExists(mixed $keys)
	{
		if (!function_exists('apc_exists')) {
			throw new \Exception('apc_exists method is not exists');
		}

		return \apc_exists($keys);
	}

	/**
	 * Deletes a value from the APC user cache.
	 *
	 * @param string $key Cache key.
	 *
	 * @return mixed Result from {@see apc_delete()}.
	 */
	public function delete(string $key)
	{
		if (!function_exists('apc_delete')) {
			throw new \Exception('apc_delete method is not exists');
		}

		return \apc_delete($key);
	}

	/**
	 * Compiles and caches a PHP file in the opcode cache.
	 *
	 * @param string $filename Path to the PHP file.
	 * @param bool $atomic Whether to compile atomically.
	 *
	 * @return mixed Result from {@see apc_compile_file()}.
	 */
	public function compile(string $filename, bool$atomic = false)
	{
		if (!function_exists('apc_compile_file')) {
			throw new \Exception('apc_compile_file method is not exists');
		}

		return \apc_compile_file($filename, $atomic);
	}

	/**
	 * Clears the APC user cache segment.
	 *
	 * @return mixed Result from {@see apc_clear_cache()}.
	 */
	public function clear()
	{
		if (!function_exists('apc_clear_cache')) {
			throw new \Exception('apc_clear_cache method is not exists');
		}

		return \apc_clear_cache('user');
	}

	/**
	 * Stores a value in the APC user cache with a TTL and request timestamp metadata.
	 *
	 * @param string $key Cache key.
	 * @param int $validTime Time to live in seconds.
	 * @param mixed $buffer Serializable payload.
	 *
	 * @return mixed Result from {@see apc_store()}.
	 */
	public function set(string $key, int $validTime, mixed $buffer)
	{
		if (!function_exists('apc_store')) {
			throw new \Exception('apc_store method is not exists');
		}

		return \apc_store($key, array($_SERVER['REQUEST_TIME'], $buffer), $validTime);
	}

	/**
	 * Adds a value only if the key does not already exist.
	 *
	 * @param string $key Cache key.
	 * @param mixed $value Serializable payload.
	 * @param int $ttl Time to live in seconds.
	 *
	 * @return void
	 */
	public function add(string $key, mixed $value, int $ttl)
	{
		if (!function_exists('apc_add')) {
			throw new \Exception('apc_add method is not exists');
		}

		\apc_add($key, $value, $ttl);
	}

	/**
	 * Adds multiple key/value pairs with the same TTL.
	 *
	 * @param array $values Map of cache keys to values.
	 * @param int $ttl Time to live in seconds.
	 *
	 * @return void
	 */
	public function adds(array $values, int $ttl)
	{
		if (!function_exists('apc_add')) {
			throw new \Exception('apc_add method is not exists');
		}

		\apc_add($values, null, $ttl);
	}

	/**
	 * Fetches a cached entry and optionally expires it when older than a limit.
	 *
	 * @param string $key Cache key.
	 * @param int $limit Minimum acceptable request timestamp; deletes the key when exceeded.
	 *
	 * @return mixed Cached payload or false when missing.
	 */
	public function get(string $key, int $limit)
	{
		if (!function_exists('apc_fetch')) {
			throw new \Exception('apc_fetch method is not exists');
		}

		$cache = \apc_fetch($key, $limit);

		if ($limit > 0 && $limit > $cache[0]) {
			$this->delete($key);
		}

		return $cache[1];
	}
}
