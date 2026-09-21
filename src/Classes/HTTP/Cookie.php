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
use function array_key_exists;

/**
 * Class Cookie
 *
 * @package Clover\Classes\HTTP
 */
class Cookie extends BaseClass
{
	/**
	 * Check if a cookie with the given key exists.
	 *
	 * @param string $key Key of the cookie to check for existence.
	 * 
	 * @return bool True if the cookie exists and is not null, false otherwise.
	 */
	public static function has(string $key): bool
	{
		return isset($_COOKIE[$key]) && array_key_exists($key, $_COOKIE) && $_COOKIE[$key] !== null ? true : false;
	}

	/**
	 * Set a raw cookie without URL encoding.
	 *
	 * @param string $key Key of the cookie.
	 * @param string $value Value of the cookie (default: empty string).
	 * @param int    $expired Expiration time as a Unix timestamp (default: 0, which means "until the browser is closed").
	 * @param string $path Path for the cookie (default: "/").
	 * @param string $domain Domain for the cookie (default: current domain).
	 * @param bool   $secure Whether to set the cookie as secure (only sent over HTTPS).
	 * @param bool   $httponly Whether to set the cookie as HTTP-only (not accessible via JavaScript).
	 * 
	 * @return bool True on success, false on failure.
	 */
	public static function setRaw(string $key, string $value, int $expired = 0, string $path = "", string $domain = "", bool $secure = false, bool $httponly = false): bool
	{
		return setrawcookie($key, $value, $expired, $path, $domain, $secure, $httponly);
	}

	/**
	 * Get the value of a cookie by its key.
	 *
	 * @param string $key Key of the cookie to retrieve.
	 * @param mixed  $default Default value to return if the cookie does not exist (default: null).
	 * 
	 * @return mixed The value of the cookie if it exists, otherwise the default value.
	 */
	public static function get(string $key, mixed $default = null): mixed
	{
		if (self::has($key)) {
			return $_COOKIE[$key];
		}

		return $default;
	}

	/**
	 * Set a cookie with optional URL encoding.
	 *
	 * @param string $key Key of the cookie.
	 * @param string $value Value of the cookie (default: empty string).
	 * @param int    $expired Expiration time as a Unix timestamp (default: 0, which means "until the browser is closed").
	 * @param string $path Path for the cookie (default: "/").
	 * @param string $domain Domain for the cookie (default: current domain).
	 * @param bool   $secure Whether to set the cookie as secure (only sent over HTTPS).
	 * @param bool   $httponly Whether to set the cookie as HTTP-only (not accessible via JavaScript).
	 * @param bool   $urlEncode Whether to URL encode the cookie value (default: true).
	 * 
	 * @return bool True on success, false on failure.
	 */
	public static function set(string $key, string $value, int $expired = 0, string $path = "", string $domain = "", bool $secure = false, bool $httponly = false, bool $urlEncode = true): bool
	{
		if ($urlEncode) {
			return setcookie($key, $value, $expired, $path, $domain, $secure, $httponly);
		}

		return self::setRaw($key, $value, $expired, $path, $domain, $secure, $httponly);
	}

	/**
	 * Unset a cookie by its name.
	 *
	 * @param string $name Name of the cookie to unset.
	 * 
	 * @return bool True if the cookie was successfully unset, false if the cookie did not exist.
	 */
	public static function unset(string $name): bool
	{
		if (isset($_COOKIE[$name])) {
			unset($_COOKIE[$name]);

			return true;
		}

		return false;
	}

	/**
	 * Get all cookies as an associative array.
	 *
	 * @return array An associative array of all cookies, where the keys are cookie names and the values are cookie values.
	 */
	public static function all(): array
	{
		return $_COOKIE;
	}

	/**
	 * Clear all cookies by unsetting them.
	 *
	 * @return void
	 */
	public static function clear(): void
	{
		foreach ($_COOKIE as $key => $value) {
			self::unset($key);
		}
	}

	/**
	 * Get the value of a cookie by its key without URL decoding.
	 *
	 * @param string $key Key of the cookie to retrieve.
	 * @param mixed  $default Default value to return if the cookie does not exist (default: null).
	 * 
	 * @return mixed The value of the cookie if it exists, otherwise the default value.
	 */
	public static function getRaw(string $key, mixed $default = null): mixed
	{
		if (self::has($key)) {
			return $_COOKIE[$key];
		}

		return $default;
	}
}
