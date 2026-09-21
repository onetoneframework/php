<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Data;

use Clover\Classes\OperationSystem;
use Clover\Enumeration\Encoding;
use Clover\Exception\FileHandler\MemoryAllocatedException;
use Clover\Validation\PHPValidation;
use function defined;
use function strlen;
use function chr;

/**
 * Class StringHandler
 *
 * Provides various string manipulation and utility functions.
 */
class StringHandler
{
	/**
	 * Check if PCRE Unicode support is available.
	 *
	 * @return bool True if PCRE Unicode support is available, false otherwise.
	 */
	public static function isPcreUnicodeSupported(): bool
	{
		return defined('PREG_BAD_UTF8_OFFSET_ERROR') && preg_match('/\pL/u', 'a') === 1;
	}

	/**
	 * Check that contains string.
	 *
	 * @param string $haystack
	 * @param string $needle
	 *
	 * @return bool
	 */
	public static function contains(string $haystack, string $needle): bool
	{
		$isGreaterThanRequiredVersion = PHPValidation::versionGreaterThanCurrent("8.0");

		if ($isGreaterThanRequiredVersion && function_exists("str_contains")) {
			return str_contains($haystack, $needle);
		}

		$position = strpos($haystack, $needle);

		return $position !== false;
	}

	/**
	 * Convert a string to CamelCase.
	 *
	 * @param string $string The input string.
	 *
	 * @return string The CamelCase version of the input string.
	 */
	public static function camelize($string): string
	{
		return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
	}

	/**
	 * Pad a string to a certain length with another string.
	 *
	 * @param string          $string    The input string.
	 * @param int             $length    The desired length of the output string.
	 * @param string          $padString The string to pad with.
	 * @param int             $type      The type of padding (STR_PAD_LEFT, STR_PAD_RIGHT, STR_PAD_BOTH).
	 * @param string|Encoding $encoding  The character encoding.
	 *
	 * @return string The padded string.
	 */
	public static function pad(string $string = "", int $length = 0, string $padString = " ", int $type = STR_PAD_BOTH, string|Encoding $encoding = Encoding::UTF_8): string
	{
		if (function_exists("mb_str_pad")) {
			return mb_str_pad($string, $length, $padString, $type, $encoding);
		}

		return str_pad($string, $length, $padString, $type);
	}

	/**
	 * Pad a string to a certain length with another string on both sides.
	 *
	 * @param string $string    The input string.
	 * @param int    $length    The desired length of the output string.
	 * @param string $padString The string to pad with.
	 *
	 * @return string The padded string.
	 */
	public static function padBoth(string $string, int $length, string $padString = " "): string
	{
		return self::pad($string, $length, $padString, \STR_PAD_BOTH);
	}

	/**
	 * Pad a string to a certain length with another string on the right side.
	 *
	 * @param string $string    The input string.
	 * @param int    $length    The desired length of the output string.
	 * @param string $padString The string to pad with.
	 *
	 * @return string The padded string.
	 */
	public static function padRight(string $string, int $length, string $padString = " "): string
	{
		return self::pad($string, $length, $padString, \STR_PAD_RIGHT);
	}

	/**
	 * Pad a string to a certain length with another string on the left side.
	 *
	 * @param string $string    The input string.
	 * @param int    $length    The desired length of the output string.
	 * @param string $padString The string to pad with.
	 *
	 * @return string The padded string.
	 */
	public static function padLeft(string $string, int $length, string $padString = " "): string
	{
		return self::pad($string, $length, $padString, \STR_PAD_LEFT);
	}

	/**
	 * Check that string ends with specified substring.
	 *
	 * @param string $haystack
	 * @param string $needle
	 *
	 * @return bool
	 */
	public static function endsWith(string $haystack, string $needle): bool
	{
		$isGreaterThanRequiredVersion = PHPValidation::versionGreaterThanCurrent("8.0");

		if ($isGreaterThanRequiredVersion && function_exists("str_ends_with")) {
			return str_ends_with($haystack, $needle);
		}

		return self::indexOf($haystack, $needle) === (strlen($haystack) - strlen($needle));
	}

	/**
	 * Check that string starts with specified substring.
	 *
	 * @param string $haystack
	 * @param string $needle
	 *
	 * @return bool
	 */
	public static function startsWith(string $haystack, string $needle): bool
	{
		$isGreaterThanRequiredVersion = PHPValidation::versionGreaterThanCurrent("8.0");

		if ($isGreaterThanRequiredVersion && function_exists("str_contains")) {
			return str_starts_with($haystack, $needle);
		}

		$offset = self::indexOf($haystack, $needle);
		return $offset === true || $offset === 0;
	}

	/**
	 * Find the position of the first occurrence of a substring in a string.
	 *
	 * @param string          $haystack The string to search in.
	 * @param string          $needle   The substring to search for.
	 * @param int             $offset   The search offset.
	 * @param string|Encoding $encoding The character encoding.
	 *
	 * @return bool|int The position of the first occurrence of the substring, or false if not found.
	 */
	public static function indexOf(string $haystack, string $needle, int $offset = 0, string|Encoding $encoding = Encoding::UTF_8): bool|int
	{
		if (function_exists('mb_strpos')) {
			return mb_strpos($haystack, $needle, $offset, $encoding);
		}

		return strpos($haystack, $needle, $offset);
	}

	/**
	 * Get a substring of a string with multibyte support.
	 *
	 * @param string $string The input string.
	 * @param int    $start  The starting position.
	 * @param int    $length The length of the substring.
	 * @param string $prefix The prefix to append if the string is truncated.
	 *
	 * @return string The resulting substring.
	 */
	public static function substringMultibyte(string $string, int $start, int $length, string $prefix = '...'): string
	{
		if (mb_strlen($string) > (int) $length) {
			return mb_substr($string, $start, (int) $length) . $prefix;
		} else {
			return mb_substr($string, $start, (int) $length);
		}
	}

	/**
	 * Find the position of a substring relative to another substring.
	 *
	 * @param string $text         The main text.
	 * @param string $searchString The substring to search for.
	 * @param string $behindString The reference substring.
	 *
	 * @return bool|int The position of the search string if found after/before the behind string, -1 otherwise.
	 */
	public static function indexBehindOf(string $text, string $searchString, string $behindString): bool|int
	{
		$aheadIndex = strpos($text, $behindString);

		$findedIndex = strpos($text, $searchString);

		return ($findedIndex < $aheadIndex) ? -1 : $findedIndex;
	}

	/**
	 * Find the position of a substring relative to another substring.
	 *
	 * @param string $text         The main text.
	 * @param string $searchString The substring to search for.
	 * @param string $behindString The reference substring.
	 *
	 * @return bool|int The position of the search string if found before/after the behind string, -1 otherwise.
	 */
	public static function indexHeadOf(string $text, string $searchString, string $behindString): bool|int
	{
		$aheadIndex = strpos($text, $behindString);

		$findedIndex = strpos($text, $searchString);

		return ($findedIndex > $aheadIndex) ? -1 : $findedIndex;
	}

	/**
	 * Remove Byte Order Mark (BOM) from the beginning of a string based on encoding.
	 *
	 * @param string $text     The input string.
	 * @param string $encoding The encoding type (default is UTF-8).
	 *
	 * @return array|string|null The string without BOM, or null if no BOM was found.
	 */
	public static function removeByteOrderMark(string $text, string $encoding = Encoding::UTF_8): array|string|null
	{
		$byteOrderMark = "EFBBBF";
		$result = "";

		switch ($encoding) {
			case Encoding::UTF_8:
				$byteOrderMark = "EFBBBF";
				break;
			case Encoding::UTF_16_BIG_ENDIAN:
				$byteOrderMark = "FEFF";
				break;
			case Encoding::UTF_16_LITTLE_ENDIAN:
				$byteOrderMark = "FFFE";
				break;
			case Encoding::UTF_32_BIG_ENDIAN:
				$byteOrderMark = "0000FEFF";
				break;
			case Encoding::UTF_32_LITTLE_ENDIAN:
				$byteOrderMark = "FFFE0000";
				break;
			default:
				break;
		}

		$hexString = self::substring(self::binaryToHex($text), 0, 6);

		if ($hexString === $byteOrderMark) {
			$find = pack('H*', $byteOrderMark);
			$result = preg_replace("/^$find/", '', $text);
		}

		return $result;
	}

	/**
	 * Convert a string to uppercase.
	 *
	 * @param string          $text     The input string.
	 * @param string|Encoding $encoding The character encoding (default is UTF-8).
	 *
	 * @return array|bool|string|null The uppercase version of the input string.
	 */
	public static function toUpperCase(string $text, string|Encoding $encoding = Encoding::UTF_8): array|bool|string|null
	{
		if (function_exists('mb_strupper')) {
			return mb_strtoupper($text, $encoding);
		}

		return strtoupper($text);
	}

	/**
	 * Convert a string to lowercase.
	 *
	 * @param string          $text     The input string.
	 * @param string|Encoding $encoding The character encoding (default is UTF-8).
	 *
	 * @return array|bool|string|null The lowercase version of the input string.
	 */
	public static function toLowerCase(string $text, ?string $encoding = null): array|bool|string|null
	{
		if (function_exists('mb_strtolower')) {
			return mb_strtolower($text, $encoding);
		}

		return strtolower($text);
	}

	/**
	 * Convert spaces in a string to underscores.
	 *
	 * @param string $text The input string.
	 *
	 * @return string The modified string with spaces replaced by underscores.
	 */
	public static function toUnderScore(string $text): string
	{
		return strtr($text, ' ', '_');
	}

	/**
	 * Remove null byte characters from a string.
	 *
	 * @param string $input The input string.
	 *
	 * @return array|string The string without null byte characters.
	 */
	public static function removeNullByte(string $input): array|string
	{
		$clean = str_replace("\x00", '', $input);
		$clean = str_replace("\0", '', $input);
		$clean = str_replace(chr(0), '', $input);

		return $clean;
	}

	/**
	 * Remove dot before the version number in a string.
	 *
	 * @param string $text The input string.
	 *
	 * @return array|string|null The modified string with the dot removed before the version number.
	 */
	public static function removeDot(string $text): array|string|null
	{
		return preg_replace("#(.*)-(.*)-(.*).(\d)-(.*)#", "$1-$2-$3$4-$5", $text);
	}

	/**
	 * Get a substring of a string.
	 *
	 * @param string $string The input string.
	 * @param int    $start  The starting position.
	 * @param int|null $length The length of the substring (optional).
	 *
	 * @return string The resulting substring.
	 */
	public static function substring(string $string, int $start, int|null $length = null): string
	{
		return substr($string, $start, $length);
	}

	/**
	 * Convert binary data to its hexadecimal representation.
	 *
	 * @param string $binaryText The binary data.
	 *
	 * @return string The hexadecimal representation of the binary data.
	 */
	public static function binaryToHex(string $binaryText): string
	{
		return bin2hex($binaryText);
	}

	/**
	 * Calculate the maximum allocation size for repeating a string based on memory limit.
	 *
	 * @param string $string The input string.
	 *
	 * @return int The maximum number of times the string can be repeated without exceeding memory limit.
	 */
	public static function getMaxAllocationSize(string $string): int
	{
		$memory_limit = ini_get('memory_limit');

		if (preg_match('/^(\d+)(.)$/', $memory_limit, $matches)) {
			if ($matches[2] == 'M') {
				$memory_limit = $matches[1] * 1024 * 1024;
			} else if ($matches[2] == 'K') {
				$memory_limit = $matches[1] * 1024;
			}
		}

		$maxAllocationSize = $memory_limit - 2097184;

		return (int) ($maxAllocationSize / strlen($string));
	}

	/**
	 * Repeat a string a specified number of times.
	 *
	 * @param string $string     The input string.
	 * @param int    $multiplier The number of times to repeat the string.
	 *
	 * @return string The resulting repeated string.
	 *
	 * @throws MemoryAllocatedException If the repetition exceeds memory allocation limits.
	 */
	public static function repeat(string $string, int $multiplier): string
	{
		if (self::getMaxAllocationSize($string) > $multiplier) {
			// Memory allocated error
			throw new MemoryAllocatedException("Memory Allocated");
		}

		return str_repeat($string, $multiplier);
	}

	/**
	 * Generate a random string of specified length.
	 *
	 * @param int $length The length of the random string (default is 1).
	 *
	 * @return string The generated random string.
	 */
	public static function getRandomString(int $length = 1): string
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';

		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}

		return $randomString;
	}

	/**
	 * Remove non-breaking space characters from a string.
	 *
	 * @param string $string The input string.
	 *
	 * @return array|string|null The string without non-breaking space characters.
	 */
	public static function removeNonBreakingSpace(string $string): array|string|null
	{
		$source = preg_replace('/^\xc2\xa0/', '', $string);

		return $source;
	}

	/**
	 * Remove zero-width space characters from a string.
	 *
	 * @param string $string The input string.
	 *
	 * @return array|string|null The string without zero-width space characters.
	 */
	public static function removeZeroWidthSpace(string $string): array|string|null
	{
		$source = preg_replace('/^\xe2\x80\x8b/', '', $string);

		return $source;
	}

	/**
	 * Remove UTF-8 Byte Order Mark (BOM) from the beginning of a string.
	 *
	 * @param string $string The input string.
	 *
	 * @return array|string|null The string without UTF-8 BOM.
	 */
	public static function removeUtf8Bom(string $string): array|string|null
	{
		$source = preg_replace('/^\xEF\xBB\xBF/', '', $string);

		return $source;
	}

	/**
	 * Generate an MD5 hash of a string with specified length.
	 *
	 * @param string $string The input string.
	 * @param int    $length The length of the MD5 hash (default is 32).
	 *
	 * @return string The generated MD5 hash.
	 */
	public static function getMD5String(string $string, int $length = 32): string
	{
		return $string == '' ? '' : substr(md5($string), -$length);
	}

	/**
	 * Generate a tripcode from a name string.
	 *
	 * @param string $name   The input name string.
	 * @param int    $length The length of the tripcode (default is 10).
	 *
	 * @return array|string The name with tripcode appended or modified.
	 */
	public function entrip(string $name, int $length = 10): array|string
	{
		if (preg_match('/^(.+?)#(.+)$/', $name, $match)) {
			list(, $name, $pass) = $match;
			$salt = substr($pass . 'H.', 1, 2);
			$salt = preg_replace('/[^\.-z]/', '.', $salt);
			$salt = strtr($salt, ':;<=>?@[\\]^_`', 'ABCDEFGabcdef');
			$trip = crypt($pass, $salt);
			$trip = substr($trip, -$length);
			$name = $name . '◆' . $trip;
		} else {
			$name = str_replace('◆', '◇', $name);
		}
		return $name;
	}

	/**
	 * Validate if a string is a valid PHP variable name.
	 *
	 * @param string $name The input string.
	 *
	 * @return bool True if the string is a valid PHP variable name, false otherwise.
	 */
	public static function isValidPhpVariableName(string $name): bool
	{
		return (bool) preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name, $matches);
	}

	/**
	 * Generate a unique MD5 hash based on uniqid.
	 *
	 * @param int    $length The length of the MD5 hash (default is 20).
	 * @param string $prefix The prefix for uniqid (default is empty string).
	 *
	 * @return string The generated unique MD5 hash.
	 */
	public static function getMd5Uniqid(int $length = 20, string $prefix = ''): string
	{
		$id = md5(uniqid($prefix, true));
		$id = substr($id, -$length);

		return $id;
	}

	/**
	 * Get the length of a string.
	 *
	 * @param string $data The input string.
	 *
	 * @return int The length of the string.
	 */
	public static function length(string $data): int
	{
		return strlen($data);
	}

	/**
	 * Convert an integer string to its byte representation.
	 *
	 * @param string $string The input integer string.
	 *
	 * @return string The byte representation of the integer.
	 */
	public static function intergerToBytes(string $string): string
	{
		$length = strlen($string);
		$result = '';

		for ($i = $length - 1; $i >= 0; $i--) {
			$result .= chr((int) floor($string / pow(256, $i)));
		}

		return $result;
	}

	/**
	 * Convert a hexadecimal string to its binary representation.
	 *
	 * @param string $string The input hexadecimal string.
	 *
	 * @return string The binary representation of the hexadecimal string.
	 */
	public static function hexToBinary(string $string): string
	{
		$length = strlen($string);
		$result = '';

		for ($i = 0; $i < $length; $i += 2) {
			$result .= chr(hexdec(substr($string, $i, 2)));
		}

		return $result;
	}

	/**
	 * Remove null byte characters from a string.
	 *
	 * @param string $string The input string.
	 *
	 * @return string The string without null byte characters.
	 */
	public static function removeNullBytes(string $string): string
	{
		$clean = str_replace("\x00", '', $string);
		$clean = str_replace("\0", '', $string);
		$clean = str_replace(chr(0), '', $string);

		return $clean;
	}

	/**
	 * Generate a random hexadecimal string of specified length.
	 *
	 * @param int $length The length of the random hexadecimal string (default is 32).
	 *
	 * @return string The generated random hexadecimal string.
	 */
	public static function getRandomHex(int $length = 32): string
	{
		$output = self::getRandomBytes($length);

		return bin2hex($output);
	}

	/**
	 * Check if a string is empty.
	 *
	 * @param mixed $string The input string.
	 *
	 * @return bool True if the string is empty, false otherwise.
	 */
	public static function isEmpty(mixed $string): bool
	{
		return empty($string);
	}

	/**
	 * Check if a string is null.
	 *
	 * @param mixed $string The input string.
	 *
	 * @return bool True if the string is null, false otherwise.
	 */
	public static function isNull(mixed $string): bool
	{
		return is_null($string);
	}

	/**
	 * Generate random bytes of specified length.
	 *
	 * @param int $length The length of the random bytes (default is 32).
	 *
	 * @return bool|string The generated random bytes or false on failure.
	 */
	public static function getRandomBytes(int $length = 32): bool|string
	{
		$bytes = min(32, $length);

		$isWindows = OperationSystem::isWindows();

		if (function_exists('random_bytes')) {
			try {
				$output = random_bytes($bytes);
			} catch (\Exception $e) {
				$output = false;
			}
		}

		if ($output === false) {
			if (function_exists('mcrypt_create_iv') && !$isWindows) {
				$output = mcrypt_create_iv($length, \MCRYPT_DEV_URANDOM);
			} else if (function_exists('openssl_random_pseudo_bytes') && !$isWindows) {
				$output = openssl_random_pseudo_bytes($length);
			} else if (function_exists('mcrypt_create_iv') && $isWindows) {
				$output = mcrypt_create_iv($bytes, \MCRYPT_RAND);
			}
		}

		return $output;
	}
}
