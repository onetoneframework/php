<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Traits\Regex;

use function preg_last_error_constant;
use function in_array;

/**
 * Regex Error Trait
 */
trait RegexError
{
	/**
	 * List of error codes
	 * 
	 * @var array
	 */
	private static $errorCodes = [
		PREG_INTERNAL_ERROR,
		PREG_BACKTRACK_LIMIT_ERROR,
		PREG_RECURSION_LIMIT_ERROR,
		PREG_BAD_UTF8_ERROR,
		PREG_BAD_UTF8_OFFSET_ERROR,
		PREG_JIT_STACKLIMIT_ERROR
	];

	/**
	 * Get the error string for a given error code
	 * 
	 * @param int $errorCode
	 * @return string|null
	 */
	private function getErrorString(int $errorCode): ?string
	{
		$pcreConstants = get_defined_constants(true)['pcre'] ?? [];

		$pcreConstants = array_filter($pcreConstants, function ($code) {
			return is_int($code);
		});

		$errorStrings = array_flip($pcreConstants);

		return $errorStrings[$errorCode] ?? null;
	}

	/**
	 * Get the last regex error constant
	 * 
	 * @return string
	 */
	public static function getErrorConstant(): string
	{
		if (function_exists("preg_last_error_constant")) {
			return preg_last_error_constant();
		}

		return match (self::getErrorCode()) {
			PREG_NO_ERROR => 'PREG_NO_ERROR',
			PREG_INTERNAL_ERROR => 'PREG_INTERNAL_ERROR',
			PREG_BACKTRACK_LIMIT_ERROR => 'PREG_BACKTRACK_LIMIT_ERROR',
			PREG_RECURSION_LIMIT_ERROR => 'PREG_RECURSION_LIMIT_ERROR',
			PREG_BAD_UTF8_ERROR => 'PREG_BAD_UTF8_ERROR',
			PREG_BAD_UTF8_OFFSET_ERROR => 'PREG_BAD_UTF8_OFFSET_ERROR',
			PREG_JIT_STACKLIMIT_ERROR => 'PREG_JIT_STACKLIMIT_ERROR'
		};
	}

	/**
	 * Get the last regex error message
	 * 
	 * @return string
	 */
	public static function getErrorMessage(): string
	{
		if (function_exists("preg_last_error_msg")) {
			return preg_last_error_msg();
		}

		$message = false;
		$errorCode = self::getErrorCode();

		return match ($errorCode) {
			PREG_NO_ERROR => 'No errors occurred',
			PREG_INTERNAL_ERROR => 'An internal error occurred in the PCRE engine.',
			PREG_BACKTRACK_LIMIT_ERROR => 'Backtrack limit was exhausted!',
			PREG_RECURSION_LIMIT_ERROR => 'Recursion limit was exhausted!',
			PREG_BAD_UTF8_ERROR => 'The input string contains invalid UTF-8 data.',
			PREG_BAD_UTF8_OFFSET_ERROR => 'Invalid UTF-8 offset detected!',
			PREG_JIT_STACKLIMIT_ERROR => 'JIT stack limit exceeded!',
			default => $message
		};
	}

	/**
	 * Get the last regex error code
	 * 
	 * @return int
	 */
	public static function getErrorCode(): int
	{
		return preg_last_error();
	}

	/**
	 * Check if there is a JIT stack limit error
	 * 
	 * @return bool
	 */
	public static function hasJITStackLimitError(): bool
	{
		return PREG_JIT_STACKLIMIT_ERROR === self::getErrorCode();
	}

	/**
	 * Check if there is a bad UTF-8 offset error
	 * 
	 * @return bool
	 */
	public static function hasBadUTF8OffsetError(): bool
	{
		return PREG_BAD_UTF8_OFFSET_ERROR === self::getErrorCode();
	}

	/**
	 * Check if there is a bad UTF-8 error
	 * 
	 * @return bool
	 */
	public static function hasBadUTF8Error(): bool
	{
		return PREG_BAD_UTF8_ERROR === self::getErrorCode();
	}

	/**
	 * Check if there is a recursion limit error
	 * 
	 * @return bool
	 */
	public static function hasRecursionLimitEror(): bool
	{
		return PREG_RECURSION_LIMIT_ERROR === self::getErrorCode();
	}

	/**
	 * Check if there is a backtrack limit error
	 * 
	 * @return bool
	 */
	public static function hasBacktrackLimitError(): bool
	{
		return PREG_BACKTRACK_LIMIT_ERROR === self::getErrorCode();
	}

	/**
	 * Check if there is an internal error
	 * 
	 * @return bool
	 */
	public static function hasInternalError(): bool
	{
		return PREG_INTERNAL_ERROR === self::getErrorCode();
	}

	/**
	 * Check if there is no error
	 * 
	 * @return bool
	 */
	public static function noError(): bool
	{
		return PREG_NO_ERROR === self::getErrorCode();
	}

	/**
	 * Check if there is any regex error
	 * 
	 * @return bool
	 */
	public static function hasError(): bool
	{
		$errorCode = self::getErrorCode();

		return !in_array($errorCode, self::$errorCodes);
	}
}
