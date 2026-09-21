<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Data;
use function ord;

/**
 * Class Unicode
 *
 * @package Clover\Classes\Data
 */
class Unicode
{

	/**
	 * Get the Unicode code point of a character
	 *
	 * @param string $character The character to get the code point for
	 * @param bool $multibyte Whether to treat the character as multibyte (default: false)
	 * @param string $characterSet The character set to use (default: 'UTF-8')
	 * 
	 * @return int|bool The Unicode code point of the character
	 */
	public static function getCodePoint(string $character, bool $multibyte = false, string $characterSet = 'UTF-8'): mixed
	{
		if (!$multibyte) {
			return ord($character);
		}

		return mb_ord($character, $characterSet);
	}

	/**
	 * Split a string into an array of Unicode characters
	 *
	 * @param string $string The string to split
	 * 
	 * @return array|bool An array of Unicode characters
	 */
	public static function split(string $string): array|bool
	{
		return preg_split('//u', $string, -1, \PREG_SPLIT_NO_EMPTY);
	}

	public static function isValid(string $text): bool|int
	{
		return preg_match("/[^\w$\x{0080}-\x[FFFF]]+//u", $text);
	}
}
