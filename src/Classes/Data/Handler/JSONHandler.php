<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Data;

use Clover\Classes\Data\ArrayObject;
use Clover\Classes\Data\StringObject;
use Exception;

use function is_string;
use function is_array;

/**
 * JSON Handler Class
 * 
 * @package Clover\Classes\Data
 */
class JSONHandler
{
	/**
	 * Decodes a JSON string to an object
	 * 
	 * @param string|StringObject $string
	 * @param bool|null $associative
	 * @param int|null $depth
	 * @param int|null $flags
	 * 
	 * @return ArrayObject
	 * 
	 * @throws Exception
	 */
	public static function decodeToArray(string|StringObject $string, ?bool $associative = null, ?int $depth = 512, ?int $flags = 0): ArrayObject
	{
		$flags |= JSON_OBJECT_AS_ARRAY;

		return self::decode($string, $associative, $depth, $flags);
	}

	/**
	 * Decodes a JSON string to object
	 * 
	 * @param string|StringObject $string
	 * @param bool|null $associative
	 * @param int|null $depth
	 * @param int|null $flags
	 * 
	 * @return ArrayObject
	 * 
	 * @throws Exception
	 */
	public static function decodeToObject(string|StringObject $string, ?bool $associative = null, ?int $depth = 512, ?int $flags = 0): ArrayObject
	{
		$flags &= ~JSON_OBJECT_AS_ARRAY;

		return self::decode($string, $associative, $depth, $flags);
	}

	/**
	 * Decodes a pretty-printed JSON string
	 * 
	 * @param string|StringObject $string
	 * @param bool|null $associative
	 * @param int|null $depth
	 * @param int|null $flags
	 * 
	 * @return ArrayObject
	 * 
	 * @throws Exception
	 */
	public static function decodePretty(string|StringObject $string, ?bool $associative = null, ?int $depth = 512, ?int $flags = 0): ArrayObject
	{
		$flags |= JSON_PRETTY_PRINT;

		return self::decode($string, $associative, $depth, $flags);
	}

	/**
	 * Decodes a JSON string
	 * 
	 * @param string|StringObject $string
	 * @param bool|null $associative
	 * @param int|null $depth
	 * @param int|null $flags
	 * @param bool $raw
	 * 
	 * @return mixed|ArrayObject
	 * 
	 * @throws Exception
	 */
	public static function decode(string|StringObject $string, ?bool $associative = null, ?int $depth = 512, ?int $flags = 0, bool $raw = false): mixed
	{
		if ($string instanceof StringObject) {
			$string = $string->__toString();
		}

		if ($flags === 0) {
			$flags = JSON_UNESCAPED_UNICODE;

			if (PHP_MAJOR_VERSION >= 7) {
				if (PHP_MAJOR_VERSION === 7 && PHP_MINOR_VERSION >= 3) {
					$flags |= JSON_THROW_ON_ERROR;
				} elseif (PHP_MAJOR_VERSION >= 8) {
					$flags |= JSON_THROW_ON_ERROR;
				}
			}
		}

		$decoded = json_decode($string, $associative, $depth, $flags);

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new Exception(self::getLastErrorMessage());
		}

		return $raw ? $decoded : new ArrayObject($decoded);
	}

	/**
	 * Encodes a value to JSON with unescaped slashes
	 * 
	 * @param mixed $string
	 * @param int $depth
	 * 
	 * @return bool|string
	 * 
	 * @throws Exception
	 */
	public static function encodeWithUnscapedSlashes(mixed $string, int $depth = 512): bool|string
	{
		return self::encode($string, JSON_UNESCAPED_SLASHES, $depth);
	}

	/**
	 * Encodes a value to JSON
	 * 
	 * @param array|ArrayObject $value
	 * @param int|null $flags
	 * @param int $depth
	 * 
	 * @return bool|string
	 * 
	 * @throws Exception
	 */
	public static function encode(array|ArrayObject $value, ?int $flags = null, int $depth = 512): bool|string
	{
		if ($value instanceof ArrayObject) {
			$value = $value->toPHPObject();
		}

		if (null === $flags) {
			$flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR;
		}

		$encoded = json_encode($value, $flags, $depth);

		if ($encoded === false && json_last_error() !== JSON_ERROR_NONE) {
			return false;
		}

		return $encoded;
	}

	/**
	 * Returns the last error message occurred
	 * 
	 * @return string
	 */
	public static function getLastErrorMessage(): string
	{
		$lastError = self::getLastError();
		if (is_string($lastError)) {
			return $lastError;
		}

		switch ($lastError) {
			default:
				return 'Unknown Error';
			case JSON_ERROR_NONE:
				return 'No error has occurred';
			case JSON_ERROR_DEPTH:
				return 'The maximum stack depth has been exceeded';
			case JSON_ERROR_STATE_MISMATCH:
				return 'Invalid or malformed JSON';
			case JSON_ERROR_CTRL_CHAR:
				return 'Control character error, possibly incorrectly encoded';
			case JSON_ERROR_SYNTAX:
				return 'Syntax error';
			case JSON_ERROR_UTF8:
				return 'Malformed UTF-8 characters, possibly incorrectly encoded';
			case JSON_ERROR_RECURSION:
				return 'One or more recursive references in the value to be encoded';
			case JSON_ERROR_INF_OR_NAN:
				return 'One or more NAN or INF values in the value to be encoded';
			case JSON_ERROR_UNSUPPORTED_TYPE:
				return 'A value of a type that cannot be encoded was given';
			case JSON_ERROR_INVALID_PROPERTY_NAME:
				return 'A property name that cannot be encoded was given';
			case JSON_ERROR_UTF16:
				return 'Malformed UTF-16 characters, possibly incorrectly encoded';
		}
	}

	/**
	 * Returns the last error occurred
	 * 
	 * @return int|string
	 */
	public static function getLastError(): int|string
	{
		if (function_exists('json_last_error_msg')) {
			return json_last_error_msg();
		} else {
			return json_last_error();
		}
	}

	/**
	 * Checks if a string contains valid JSON Returns whether the given string is syntactically valid JSON
	 * 
	 * @param string|StringObject $string
	 * @param int $depth
	 * @param int $flags
	 * 
	 * @return bool
	 */
	public static function isJSON(string|StringObject $string, int $depth = 512, int $flags = 0): bool
	{
		if ($string instanceof StringObject) {
			$string = $string->__toString();
		}

		if (function_exists('json_validate')) {
			return json_validate($string, $depth, $flags);
		}

		return is_string($string) && is_array(json_decode($string, true, $depth, $flags)) && (json_last_error() === JSON_ERROR_NONE);
	}
}
