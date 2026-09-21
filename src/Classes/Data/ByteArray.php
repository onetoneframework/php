<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Data;

use Clover\Classes\Data as Data;
use Clover\Classes\Reflection\Handler as ReflectionHandler;
use function strlen;
use function ord;
use function chr;

/**
 * Class ByteArray
 *
 * A class for handling byte array data and conversions.
 */
class ByteArray extends Data
{
	/**
	 * Converts the internal byte array to a string.
	 * 
	 * @return mixed
	 */
	public static function toString(): mixed
	{
		return ReflectionHandler::callMethodArray("pack", array_merge(["C*"], parent::$data));
	}

	/**
	 * Converts the internal data to a byte array.
	 * 
	 * @return void
	 */
	public static function toByteArray(): void
	{
		parent::$data = unpack('C*', parent::$data);
	}

	/**
	 * Converts a byte string to an integer.
	 *
	 * @param string $bytes The byte string to convert.
	 * 
	 * @return int The resulting integer.
	 */
	public static function bytesToInt(string $bytes): int
	{
		$len = strlen($bytes);
		$res = 0;
		for ($i = 0; $i < $len; $i++) {
			$res = ($res << 8) | ord($bytes[$i]);
		}

		return $res;
	}

	/**
	 * Converts a syncsafe byte string to an integer.
	 *
	 * @param string $bytes The syncsafe byte string to convert.
	 * 
	 * @return int The resulting integer.
	 */
	public static function syncsafeToInt(string $bytes): int
	{
		$byte = unpack('C4', $bytes);

		return ($byte[1] << 21) | ($byte[2] << 14) | ($byte[3] << 7) | $byte[4];
	}

	/**
	 * Encode an integer as a 4-byte syncsafe integer.
	 *
	 * @param int $value
	 * @return string
	 */
	public static function intToSyncsafe(int $value): string
	{
		return chr(($value >> 21) & 0x7F) . chr(($value >> 14) & 0x7F) . chr(($value >> 7) & 0x7F) . chr($value & 0x7F);
	}
}
