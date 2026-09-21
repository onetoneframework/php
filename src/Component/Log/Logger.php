<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Component\Log;

use DateTimeImmutable;
use DateTimeZone;
use JsonException;
use RuntimeException;
use Stringable;
use function sprintf;
use function dirname;
use function file_put_contents;
use function get_debug_type;
use function is_scalar;
use function is_array;
use function is_object;
use function is_dir;
use function json_encode;
use function method_exists;
use function mkdir;
use function str_replace;
use function strtoupper;

/**
 * Logger Class
 *
 * Provides a simple file-based logging mechanism.
 * Formats messages and appends them to a specified log file.
 */
class Logger
{
	/**
	 * Directory permissions for newly created log folders.
	 */
	private const DIRECTORY_PERMISSIONS = 0777;

	/**
	 * @var string The path to the log file.
	 */
	protected string $logFile;

	/**
	 * Logger constructor.
	 *
	 * @param string $logFile The path to the log file.
	 */
	public function __construct(string $logFile)
	{
		$this->logFile = $logFile;
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed $level
	 * @param string|Stringable $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function log(mixed $level, string|Stringable $message, array $context = []): void
	{
		$this->ensureLogDirectoryExists();

		$timestamp = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
		$formattedMessage = $this->formatMessage($message, $context);
		$entry = sprintf("[%s] %s: %s%s", $timestamp, strtoupper((string) $level), $formattedMessage, PHP_EOL);

		$result = file_put_contents($this->logFile, $entry, FILE_APPEND | LOCK_EX);
		if ($result === false) {
			throw new RuntimeException(sprintf('Unable to write log entry to "%s".', $this->logFile));
		}
	}

	/**
	 * Write a DEBUG level log entry.
	 *
	 * @param string|Stringable $message The log message.
	 * @param array $context The interpolation context.
	 *
	 * @return void
	 */
	public function debug(string|Stringable $message, array $context = []): void
	{
		$this->log('debug', $message, $context);
	}

	/**
	 * Write an INFO level log entry.
	 *
	 * @param string|Stringable $message The log message.
	 * @param array $context The interpolation context.
	 *
	 * @return void
	 */
	public function info(string|Stringable $message, array $context = []): void
	{
		$this->log('info', $message, $context);
	}

	/**
	 * Write a WARNING level log entry.
	 *
	 * @param string|Stringable $message The log message.
	 * @param array $context The interpolation context.
	 *
	 * @return void
	 */
	public function warning(string|Stringable $message, array $context = []): void
	{
		$this->log('warning', $message, $context);
	}

	/**
	 * Write an ERROR level log entry.
	 *
	 * @param string|Stringable $message The log message.
	 * @param array $context The interpolation context.
	 *
	 * @return void
	 */
	public function error(string|Stringable $message, array $context = []): void
	{
		$this->log('error', $message, $context);
	}

	/**
	 * Format the log message by replacing placeholders with context values.
	 *
	 * @param string|Stringable $message The log message with optional placeholders.
	 * @param array $context The contextual values to interpolate.
	 * @return string The formatted log message.
	 */
	protected function formatMessage(string|Stringable $message, array $context): string
	{
		$message = (string) $message;

		foreach ($context as $key => $val) {
			$message = str_replace('{' . $key . '}', $this->stringifyContextValue($val), $message);
		}

		return $message;
	}

	/**
	 * Ensure the log directory exists before appending entries.
	 *
	 * @return void
	 */
	protected function ensureLogDirectoryExists(): void
	{
		$directory = dirname($this->logFile);
		if ($directory === '' || $directory === '.') {
			return;
		}

		if (!is_dir($directory)) {
			mkdir($directory, self::DIRECTORY_PERMISSIONS, true);
		}

		if (!is_dir($directory)) {
			throw new RuntimeException(sprintf('Unable to create log directory "%s".', $directory));
		}
	}

	/**
	 * Convert a context value into a stable string representation.
	 *
	 * @param mixed $value The context value.
	 *
	 * @return string The stringified value.
	 */
	protected function stringifyContextValue(mixed $value): string
	{
		if ($value === null || is_scalar($value)) {
			return (string) $value;
		}

		if (is_object($value) && method_exists($value, '__toString')) {
			return (string) $value;
		}

		if (is_array($value) || is_object($value)) {
			try {
				return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
			} catch (JsonException $exception) {
				return sprintf('[unserializable:%s]', get_debug_type($value));
			}
		}

		return sprintf('[unsupported:%s]', get_debug_type($value));
	}
}
