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
 * Proxy Authenticate Header
 */
class ProxyAuthenticate extends Header
{

	/**
	 * Send Proxy-Authenticate header
	 * 
	 * @return void
	 */
	public static function response(string $value, bool $replace = true, int $responseCode = 0): void
	{
		parent::responseWithKey('Proxy-Authenticate', $value, $replace, $responseCode);
	}

	/**
	 * Set basic realm
	 * 
	 * @return void
	 */
	public static function basicRealm($value): void
	{
		$key = "Basic realm";
		$data = "$key=\"$value\"";

		self::response($data);
	}
}
