<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Trait\Json;

use Clover\Enumeration\JsonErrorMessage;

/**
 * JSON Error Trait
 */
trait JSONError
{
	/**
	 * Get the last JSON error code
	 * 
	 * @return int<0, max>
	 */
	public function getLastError(): int
	{
		return json_last_error();
	}

	/**
	 * Get the last JSON error message
	 * 
	 * @return string
	 */
	public function getLastErrorMessage(): string
	{
		return json_last_error_msg();
	}

	/**
	 * Check if there is a JSON error
	 * 
	 * @return bool
	 */
	public function hasError(): bool
	{
		return $this->getLastError() !== JSON_ERROR_NONE;
	}

	/**
	 * Check if there is a JSON syntax error
	 * 
	 * @return bool
	 */
	public function hasSyntaxError(): bool
	{
		return $this->getLastError() === JSON_ERROR_SYNTAX;
	}

	/**
	 * Check if there is a malformed JSON error
	 * 
	 * @return bool
	 */
	public function isMalformed(): bool
	{
		return $this->getLastError() === JSON_ERROR_STATE_MISMATCH;
	}

	/**
	 * Check if there is an invalid JSON error
	 * 
	 * @return bool
	 */
	public function isInvalid(): bool
	{
		return $this->getLastError() === JSON_ERROR_STATE_MISMATCH;
	}

	/**
	 * Check if there is a stack depth exceeded error
	 * 
	 * @return bool
	 */
	public function isStackDepthExceeded(): bool
	{
		return $this->getLastError() === JSON_ERROR_DEPTH;
	}

	/**
	 * Check if there is a malformed UTF-8 characters error (PHP 7)
	 * 
	 * @return bool
	 */
	public function isMalformedUTF8(): bool
	{
		return $this->getLastError() === JSON_ERROR_UTF8;
	}

	/**
	 * Get JSON error message based on the last error code
	 * 
	 * @return string
	 */
	public function getMessage(): string
	{
		return match ($this->getLastError()) {
			JSON_ERROR_DEPTH => JsonErrorMessage::JSON_ERROR_DEPTH,
			JSON_ERROR_STATE_MISMATCH => JsonErrorMessage::JSON_ERROR_STATE_MISMATCH,
			JSON_ERROR_CTRL_CHAR => JsonErrorMessage::JSON_ERROR_CTRL_CHAR,
			JSON_ERROR_SYNTAX => JsonErrorMessage::JSON_ERROR_SYNTAX,
			JSON_ERROR_UTF8 => JsonErrorMessage::JSON_ERROR_UTF8,

			default => JsonErrorMessage::JSON_ERROR_UNKNOWN
		};
	}
}
