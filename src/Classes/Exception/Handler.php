<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Exception;

/**
 * Exception Handler class
 */
class Handler
{
	/**
	 * Set a custom error handler for E_ERROR level errors
	 * 
	 * @param callable $callback
	 * 
	 * @return void
	 */
	public static function setError(callable $callback): void
	{
		$previous = self::setErrorHandler(function ($errorRaised, $errorMessage, $fileName, $lineNumber, $context) use (&$previous, $callback) {
			if ($previous && is_callable($callback)) {
				$callback($errorRaised, $errorMessage, $fileName, $lineNumber, $context);
			} else {
				return false;
			}
		}, E_ERROR);
	}

	/**
	 * Set a custom error handler
	 * 
	 * @param callable $callback
	 * @param int $error_level
	 * 
	 * @return callable|null
	 */
	public static function setErrorHandler(callable $callback, int $error_level = E_ALL): callable|null
	{
		return set_error_handler($callback, $error_level);
	}

	/**
	 * Register a shutdown function
	 * 
	 * @param callable $callback
	 * @param mixed ...$args
	 * 
	 * @return void
	 */
	public static function registerShutdownFunction(callable $callback, mixed ...$args): void
	{
		register_shutdown_function($callback, ...$args);
	}

	/**
	 * Clear the last error
	 * 
	 * @return void
	 */
	public static function clearLastError(): void
	{
		error_clear_last();
	}

	/**
	 * Get the last error
	 * 
	 * @return array
	 */
	public static function getLastError(): array
	{
		return error_get_last();
	}

	/**
	 * Set a custom exception handler
	 * 
	 * @param callable $exceptionFunction
	 * 
	 * @return void
	 */
	public static function setExceptionHandler(callable $exceptionFunction): void
	{
		set_exception_handler($exceptionFunction);
	}

	/**
	 * Generates a backtrace
	 * 
	 * @param int $options
	 * @param int $limit
	 * 
	 * @return array
	 */
	public static function generatesBacktrace(int $options = DEBUG_BACKTRACE_PROVIDE_OBJECT, int $limit = 0): array
	{
		return debug_backtrace($options, $limit);
	}

	/**
	 * Restore the previous error handler
	 * 
	 * @return void
	 */
	public static function restorePreviousErrorStack(): void
	{
		restore_error_handler();
	}

	/**
	 * Restore the previous exception handler
	 * 
	 * @return void
	 */
	public static function restorePreviousExceptionStack(): void
	{
		restore_exception_handler();
	}

	/**
	 * Trigger a user error
	 * 
	 * @param string $errorMessage
	 * 
	 * @return void
	 */
	public static function trigger(string $errorMessage): void
	{
		trigger_error($errorMessage);
	}
}
