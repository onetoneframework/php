<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes;

/**
 * Cache Control Header
 */
class CacheControl extends Header
{
	/**
	 * Send Cache-Control header
	 * 
	 * @return void
	 */
	public static function response(string $value, bool $replace = true, int $responseCode = 0): void
	{
		parent::responseWithKey('Cache-Control', $value, $replace, $responseCode);
	}

	/**
	 * Set public cache
	 * 
	 * @param string $value
	 * 
	 * @return void
	 */
	public function minFresh(string $value): void
	{
		$key = "min-fresh";
		$data = "$key=$value";

		self::response($data);
	}

	/**
	 * Set max stale cache
	 * 
	 * @param string $value
	 * 
	 * @return void
	 */
	public function maxStale(string $value): void
	{
		$key = "max-stale";
		$data = "$key=[=$value]";

		self::response($data);
	}

	/**
	 * Set max age cache
	 * 
	 * @param string|int $value
	 * 
	 * @return void
	 */
	public function maxAge(string|int $value): void
	{
		$key = "max-age";
		$data = "$key=$value";

		self::response($data);
	}

	/**
	 * Set public cache
	 * 
	 * @return void
	 */
	public function onlyIfCached(): void
	{
		self::response('only-if-cached');
	}

	/**
	 * Set no-store cache
	 * 
	 * @return void
	 */
	public function noStore(): void
	{
		self::response('no-store');
	}

	/**
	 * Set no-transform cache
	 * 
	 * @return void
	 */
	public function noTransform(): void
	{
		self::response('no-transform');
	}

	/**
	 * Set no-cache cache
	 * 
	 * @return void
	 */
	public function noCache(): void
	{
		self::response('no-cache');
	}
}
