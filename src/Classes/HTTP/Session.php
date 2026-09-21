<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\HTTP;

use Clover\Classes\BaseClass;

/**
 * Class Session
 *
 * @package Clover\Classes\HTTP
 */
class Session extends BaseClass
{

	/**
	 * Start session
	 *
	 * @param array $options [name: string, sid_length: int, sid_bits_per_character: int, use_strict_mode: bool, referer_check: string]
	 * 
	 * @return void
	 */
	public static function start(array $options = []): bool
	{
		if (!self::isExtensionLoaded()) {
			return false;
		}

		return session_start($options);
	}

	/**
	 * Get the current session name
	 *
	 * @return bool|string
	 */
	public static function getCurrentName(): bool|string
	{
		return session_name();
	}

	/**
	 * Get the current session module name
	 *
	 * @return bool|string
	 */
	public static function getModuleName(): bool|string
	{
		return session_module_name();
	}

	/**
	 * Change the current session module name
	 *
	 * @param string|null $module
	 * 
	 * @return bool|string The new session module name on success, false on failure.
	 */
	public static function setModuleName(string|null $module = null): bool|string
	{
		return session_module_name($module);
	}

	/**
	 * Get the current session name
	 *
	 * @return bool|string
	 */
	public static function setName(string|null $name = null): bool|string
	{
		return session_name($name);
	}

	/**
	 * Set the session cookie lifetime
	 *
	 * @param int $lifetime Lifetime of the session cookie in seconds. A value of 0 means "until the browser is closed".
	 * 
	 * @return bool True on success, false on failure.
	 */
	public static function setCookieLifetime(int $lifetime): bool
	{
		return self::setCookieParameters($lifetime);
	}

	/**
	 * Set the session cookie parameters
	 * 
	 * @param array|int $lifetime_or_options [lifetime: int, path: string, domain: string, samesite: string]
	 * @param string|null $path Path for the session cookie (default: "/").
	 * @param string|null $domain Domain for the session cookie (default: current domain).
	 * @param bool|null $secure Whether to set the session cookie as secure (only sent over HTTPS).
	 * @param bool|null $httponly Whether to set the session cookie as HTTP-only (not accessible via JavaScript).
	 * 
	 * @return bool True on success, false on failure.
	 */
	public static function setCookieParameters(array|int $lifetime_or_options, string|null $path = null, string|null $domain = null, bool|null $secure = null, bool|null $httponly = null): bool
	{
		return session_set_cookie_params($lifetime_or_options, $path, $domain, $secure, $httponly);
	}

	/**
	 * Check that php session extension is exsits
	 *
	 * @return bool True if the session extension is loaded, false otherwise.
	 */
	public static function isExtensionLoaded(): bool
	{
		if (!extension_loaded('session')) {
			return false;
		}

		return true;
	}

	/**
	 * Get session status code
	 *
	 * @return int The return value can be one of the following constants:
	 */
	public static function getStatus(): int
	{
		return session_status();
	}

	/**
	 * Check that session is booted
	 *
	 * @return bool True if the session is not started and no session exists, false otherwise.
	 */
	public static function isBooted(): bool
	{
		return self::getStatus() === PHP_SESSION_NONE;
	}

	/**
	 * Get a current session identify
	 *
	 * @return string The session id or an empty string if there is no current session (no matter if it was started or not).
	 */
	public static function getId(): bool|string
	{
		$sessionId = session_id();

		return $sessionId;
	}

	/**
	 * Set a session identify
	 *
	 * @param string $id The new session id. If id is specified, the current session id is replaced with id. If id is not specified or is an empty string, the current session id is not changed.
	 * 
	 * @return bool|string The new session id on success, false on failure.
	 */
	public static function setId(string $id): bool|string
	{
		return session_id($id);
	}

	/**
	 * Set the cache limiter
	 *
	 * @param string $value The new cache limiter. Possible values are "public", "private", "private_no_expire", and "nocache". If value is specified, the current cache limiter is replaced with value. If value is not specified or is an empty string, the current cache limiter is not changed.
	 * 
	 * @return void
	 */
	public static function setCacheLimiter(string $value): void
	{
		session_cache_limiter($value);
	}
	
	/**
	 * Get the current cache limiter
	 *
	 * @return bool|string The current cache limiter on success, false on failure.
	 */
	public static function getCacheLimiter(): bool|string
	{
		return session_cache_limiter();
	}

	/**
	 * Set the cache expire time
	 *
	 * @param int $value The cache expire time in minutes. A value of 0 means "until the browser is closed".
	 * 
	 * @return bool|int The new cache expire time in minutes on success, false on failure.
	 */
	public static function setCacheExpire(int $value): bool|int
	{
		return session_cache_expire($value);
	}

	/**
	 * Get the current cache expire time
	 *
	 * @return bool|int The current cache expire time in minutes on success, false on failure.
	 */
	public static function getCacheExpire(): bool|int
	{
		return session_cache_expire();
	}

	/**
	 * Abort session
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function abort(): bool
	{
		return session_abort();
	}

	/**
	 * Check that session id is exists
	 *
	 * @return bool
	 */
	public static function hasId(): bool
	{
		if (self::getId() == '') {
			return false;
		}

		return true;
	}

	/**
	 * Check that session is active
	 *
	 * @return bool
	 */
	public static function isActive(): bool
	{
		if (self::getStatus() == PHP_SESSION_ACTIVE) {
			return false;
		}

		return true;
	}

	/**
	 * Check that session is exists
	 *
	 * @return bool
	 */
	public static function isExists(): bool
	{
		if (self::getStatus() == PHP_SESSION_NONE) {
			return false;
		}

		return true;
	}

	/**
	 * Check that session is disabled
	 *
	 * @return bool
	 */
	public static function isDisabled(): bool
	{
		if (self::getStatus() == PHP_SESSION_DISABLED) {
			return false;
		}

		return true;
	}

	/**
	 * Check that session is started
	 *
	 * @return bool
	 */
	public static function isStarted(): bool
	{
		if (!self::isExists() && empty($_SESSION)) {
			return false;
		}

		return true;
	}

	/**
	 * Get a save path of session
	 *
	 * @return bool|string
	 */
	public static function getSavePath(): bool|string
	{
		return session_save_path();
	}

	/**
	 * Change save path of session
	 *
	 * @param string $path
	 *
	 * @return string|boolean
	 */
	public static function setSavePath(string $path = ''): bool|string
	{
		return session_save_path($path);
	}

	/**
	 * Reset session
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function reset(): bool
	{
		return session_reset();
	}

	/**
	 * Get the current session cookie parameters
	 *
	 * @return array An associative array containing the current session cookie parameters, with the keys "lifetime", "path", "domain", "secure", and "httponly".
	 */
	public static function getCookieParameters(): array
	{
		return session_get_cookie_params();
	}

	/**
	 * Encode the current session data as a string
	 *
	 * @return bool|string The encoded session data on success, false on failure.
	 */
	public static function encode(): bool|string
	{
		return session_encode();
	}

	/**
	 * Decode session data from a string
	 *
	 * @param string $data The encoded session data to decode.
	 * 
	 * @return bool|string True on success, false on failure.
	 */
	public static function decode(string $data): bool|string
	{
		return session_decode($data);
	}

	/**
	 * Perform session data garbage collection
	 *
	 * @return bool|int The number of deleted sessions on success, or false on failure.
	 */
	public static function garbageCollect(): bool|int
	{
		return session_gc();
	}

	/**
	 * Create a new session id
	 *
	 * @param string $prefix The prefix of the new session id. If prefix is specified, the new session id will be prefixed with prefix. If prefix is not specified or is an empty string, the new session id will not be prefixed.
	 * 
	 * @return bool|string The new session id on success, false on failure.
	 */
	public static function createNewId(string $prefix): bool|string
	{
		return session_create_id($prefix);
	}

	/**
	 * Write session data and end session End the current session and store session data.
	 * @return bool True on success, false on failure.
	 */
	public static function commit(): void
	{
		session_commit();
	}

	/**
	 * Update the current session id with a newly generated one session_regenerate_id() will replace the current session id with a new one, and keep the current session information.
	 * 
	 * @param bool $deleteOldSession Whether to delete the old session data associated with the old session id. If true, the old session data will be deleted. If false, the old session data will not be deleted and will be accessible through the new session id until it is garbage collected.
	 * 
	 * @return bool True on success, false on failure.
	 */
	public static function regenerateId(bool $deleteOldSession = true): bool
	{
		return session_regenerate_id($deleteOldSession);
	}

	/**
	 * Change session availability
	 *
	 * @return bool True if the session module is configured to use cookies, false otherwise.
	 */
	public static function useCookies(): bool
	{
		if (ini_get('session.use_cookies')) {
			return true;
		}

		return false;
	}

	/**
	 * Destory session
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function destroy(): bool
	{
		return session_destroy();
	}

	/**
	 * Set session item
	 *
	 * @param string $key Key of the session item.
	 * @param mixed $value Value of the session item.
	 * @param bool $overwrite Whether to overwrite the session item if it already exists (default: true).
	 *
	 * @return bool True on success, false on failure. If overwrite is false and the session item already exists, the method will return false without modifying the existing session item.
	 */
	public static function set(string $key, mixed $value, bool $overwrite = true): bool
	{
		if (isset($_SESSION[$key]) && !$overwrite) {
			return false;
		}

		$_SESSION[$key] = $value;

		return true;
	}

	/**
	 * Get session item
	 *
	 * @return mixed
	 */
	public static function get(string $key): mixed
	{
		return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
	}

	/**
	 * Check that session item is exists
	 *
	 * @return bool
	 */
	public static function has(string $key): bool
	{
		return isset($_SESSION[$key]);
	}

	/**
	 * Unset a session item by its name.
	 *
	 * @param string $key Name of the session item to unset.
	 * 
	 * @return bool True if the session item was successfully unset, false if the session item did not exist.
	 */
	public static function unsetItem(string $key): bool
	{
		if (isset($_SESSION[$key])) {
			unset($_SESSION[$key]);
			return true;
		}
		return false;
	}

	/**
	 * Get all session items as an associative array.
	 *
	 * @return array An associative array of all session items, where the keys are item names and the values are item values.
	 */
	public static function all(): array
	{
		return $_SESSION;
	}

	/**
	 * Clear all session items by unsetting them.
	 *
	 * @return void
	 */
	public static function clear(): void
	{
		foreach ($_SESSION as $key => $value) {
			self::unsetItem($key);
		}
	}

	/**
	 * Register a shutdown function to write session data and end session
	 *
	 * @return void
	 */
	public static function registerShutdown(): void
	{
		session_register_shutdown();
	}

	/**
	 * Unset all session items
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function unset(): bool
	{
		return session_unset();
	}

	/**
	 * Write session data and end session End the current session and store session data.
	 * @return bool True on success, false on failure.
	 */
	public static function close(): bool
	{
		return session_write_close();
	}
}
