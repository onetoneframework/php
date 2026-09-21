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
 * Content Type Header
 */
class ContentType extends Header
{

	/**
	 * Send Content-Type header
	 * 
	 * @param string $value
	 * @param bool $replace
	 * @param int $responseCode
	 * 
	 * @return void
	 */
	public static function response(string $value, bool $replace = true, int $responseCode = 0): void
	{
		parent::responseWithKey('Content-Type', $value, $replace, $responseCode);
	}

	/**
	 * Set html content type
	 * 
	 * @return void
	 */
	public static function urlencodedForm(): void
	{
		self::response("application/x-www-form-urlencoded");
	}

	/**
	 * Set html content type
	 * 
	 * @return void
	 */
	public static function gdImage(): void
	{
		self::response("image/gd");
	}

	/**
	 * Set html content type
	 * 
	 * @return void
	 */
	public static function json(): void
	{
		self::response("application/json");
	}
}
